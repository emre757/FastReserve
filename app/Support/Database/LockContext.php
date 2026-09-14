<?php

namespace App\Support\Database;

use App\Enums\RowLockMode;
use App\Exceptions\LockOrderViolation;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use LogicException;

final class LockContext
{
    private bool $active = true;

    /** @var array<string, RowLockMode> */
    private array $heldLocks = [];

    /** @var array{rank: int, id: int, model: class-string<Model>}|null */
    private ?array $lastLock = null;

    /**
     * @param  array<class-string<Model>, int>  $order
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly array $order,
    ) {
        $ranks = [];

        foreach ($order as $modelClass => $rank) {
            if (
                ! is_a($modelClass, Model::class, true) // @phpstan-ignore function.alreadyNarrowedType (Validate configuration at runtime.)
                || ! is_int($rank) // @phpstan-ignore function.alreadyNarrowedType (Validate configuration at runtime.)
                || $rank < 0
            ) {
                throw new InvalidArgumentException(
                    'Lock ordering requires model classes with non-negative integer ranks.',
                );
            }

            if (in_array($rank, $ranks, true)) {
                throw new InvalidArgumentException(
                    'Each model must have a unique lock rank.',
                );
            }

            $ranks[] = $rank;
        }
    }

    /**
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $modelClass
     * @return TModel
     */
    public function findOrFail(
        string $modelClass,
        int $id,
        RowLockMode $mode = RowLockMode::ForUpdate,
    ): Model {
        $this->assertActive();

        if ($id < 1) {
            throw new InvalidArgumentException(
                'A lock requires a positive integer model ID.',
            );
        }

        if (! array_key_exists($modelClass, $this->order)) {
            throw new LockOrderViolation(
                "{$modelClass} is missing from config/locking.php.",
            );
        }

        $model = new $modelClass;

        if ($model->getConnection() !== $this->connection) {
            throw new LockOrderViolation(
                'All locked models must use the managed database connection.',
            );
        }

        $rank = $this->order[$modelClass];
        $key = "{$modelClass}:{$id}";
        $heldMode = $this->heldLocks[$key] ?? null;

        if (
            $heldMode === RowLockMode::Shared
            && $mode === RowLockMode::ForUpdate
        ) {
            throw new LockOrderViolation(
                "Cannot upgrade {$modelClass} #{$id} from Shared to ForUpdate. "
                .'Acquire ForUpdate initially.',
            );
        }

        if ($heldMode === null && $this->lastLock !== null) {
            $last = $this->lastLock;

            if (
                $rank < $last['rank']
                || ($rank === $last['rank'] && $id < $last['id'])
            ) {
                throw new LockOrderViolation(sprintf(
                    'Cannot lock %s #%d after %s #%d. '
                    .'Acquire new locks by model rank, then ascending ID.',
                    $modelClass,
                    $id,
                    $last['model'],
                    $last['id'],
                ));
            }
        }

        /** @var Builder<TModel> $query */
        $query = $model->newQuery()->whereKey($id);

        match ($heldMode ?? $mode) {
            RowLockMode::Shared => $query->sharedLock(),
            RowLockMode::ForUpdate => $query->lockForUpdate(),
        };

        $lockedModel = $query->firstOrFail();

        if ($heldMode === null) {
            $this->heldLocks[$key] = $mode;
            $this->lastLock = [
                'rank' => $rank,
                'id' => $id,
                'model' => $modelClass,
            ];
        }

        return $lockedModel;
    }

    public function assertActive(): void
    {
        if (! $this->active) {
            throw new LogicException(
                'This lock context is no longer active.',
            );
        }

        if ($this->connection->transactionLevel() !== 1) {
            throw new LogicException(
                'Use OrderedTransaction for transaction boundaries. '
                .'Manual transactions and nested savepoints are unsupported.',
            );
        }
    }

    /** @internal Called by OrderedTransaction. */
    public function close(): void
    {
        $this->active = false;
    }
}
