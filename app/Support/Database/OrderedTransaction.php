<?php

namespace App\Support\Database;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use LogicException;
use Throwable;

final class OrderedTransaction
{
    private ?LockContext $context = null;

    private ?Throwable $nestedFailure = null;

    /**
     * @param  array<class-string<Model>, int>  $order
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly array $order,
    ) {}

    /**
     * @template TResult
     *
     * @param  Closure(LockContext): TResult  $callback
     * @return TResult
     */
    public function run(
        Closure $callback,
        int $attempts = 1,
    ): mixed {
        if ($attempts < 1) {
            throw new InvalidArgumentException(
                'Transaction attempts must be at least one.',
            );
        }

        if ($this->context !== null) {
            try {
                if ($attempts !== 1) {
                    throw new LogicException(
                        'Configure retries on the outermost transaction only.',
                    );
                }

                return $callback($this->current());
            } catch (Throwable $exception) {
                $this->nestedFailure ??= $exception;

                throw $exception;
            }
        }

        if ($this->connection->transactionLevel() !== 0) {
            throw new LogicException(
                'OrderedTransaction must own the outermost transaction.',
            );
        }

        return $this->connection->transaction(
            function () use ($callback): mixed {
                $context = new LockContext(
                    $this->connection,
                    $this->order,
                );

                $this->context = $context;
                $this->nestedFailure = null;

                try {
                    $result = $callback($context);

                    if ($this->nestedFailure !== null) {
                        throw $this->nestedFailure;
                    }

                    $context->assertActive();

                    return $result;
                } finally {
                    $context->close();

                    $this->context = null;
                    $this->nestedFailure = null;
                }
            },
            $attempts,
        );
    }

    public function current(): LockContext
    {
        if ($this->context === null) {
            throw new LogicException(
                'No managed transaction is active.',
            );
        }

        $this->context->assertActive();

        return $this->context;
    }
}
