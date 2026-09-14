<?php

namespace App\PHPStan\Rules;

use App\Contracts\RequiresModelEvents;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/** @implements Rule<Expr> */
final class RequireModelEventsRule implements Rule
{
    private const array QUERY_WRITES = [
        'update',
        'delete',
        'forcedelete',
        'restore',
        'increment',
        'decrement',
    ];

    private const array EVENT_BYPASSES = [
        'insert',
        'insertgetid',
        'insertorignore',
        'insertusing',
        'insertorignoreusing',
        'upsert',
        'updateorinsert',
        'updatefrom',
        'truncate',
        'incrementeach',
        'decrementeach',
        'savequietly',
        'updatequietly',
        'deletequietly',
        'forcedeletequietly',
        'restorequietly',
        'incrementquietly',
        'decrementquietly',
        'pushquietly',
        'createquietly',
        'createorfirstquietly',
        'forcecreatequietly',
        'createmanyquietly',
        'forcecreatemanyquietly',
        'savemanyquietly',
        'withoutevents',
        'flusheventlisteners',
        'unseteventdispatcher',
    ];

    public function getNodeType(): string
    {
        return Expr::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (
            ! ($node instanceof MethodCall || $node instanceof StaticCall)
            || ! $node->name instanceof Identifier
        ) {
            return [];
        }

        $method = strtolower($node->name->toString());

        if (! in_array(
            $method,
            [...self::QUERY_WRITES, ...self::EVENT_BYPASSES],
            true,
        )) {
            return [];
        }

        $receiverType = $node instanceof MethodCall
            ? $scope->getType($node->var)
            : ($node->class instanceof Name
                ? new ObjectType($scope->resolveName($node->class))
                : $scope->getType($node->class)
                    ->getObjectTypeOrClassStringObjectType());

        if ((new ObjectType(Model::class))->isSuperTypeOf($receiverType)->yes()) {
            // Instance writes fire model events; query writes do not.
            if (
                $node instanceof MethodCall
                && in_array($method, self::QUERY_WRITES, true)
            ) {
                return [];
            }

            $modelType = $receiverType;
        } elseif (
            (new ObjectType(Builder::class))->isSuperTypeOf($receiverType)->yes()
        ) {
            $modelType = $receiverType->getTemplateType(
                Builder::class,
                'TModel',
            );
        } elseif (
            (new ObjectType(Relation::class))->isSuperTypeOf($receiverType)->yes()
        ) {
            $modelType = $receiverType->getTemplateType(
                Relation::class,
                'TRelatedModel',
            );
        } else {
            return [];
        }

        foreach ($modelType->getObjectClassReflections() as $model) {
            if (! $model->implementsInterface(RequiresModelEvents::class)) {
                continue;
            }

            return [
                RuleErrorBuilder::message(sprintf(
                    '%s::%s() bypasses required model events. '
                    .'Load model instances and save/update/delete them normally.',
                    $model->getName(),
                    $node->name->toString(),
                ))
                    ->identifier('app.modelEventsRequired')
                    ->build(),
            ];
        }

        return [];
    }
}
