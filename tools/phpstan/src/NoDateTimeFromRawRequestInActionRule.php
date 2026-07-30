<?php

declare(strict_types=1);

namespace Slim4\PhpStan;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Name;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @implements Rule<New_>
 */
final class NoDateTimeFromRawRequestInActionRule implements Rule
{
    private const DATE_CLASSES = ["datetime", "datetimeimmutable"];

    public function getNodeType(): string
    {
        return New_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $scope->getClassReflection();

        if ($classReflection === null || !str_ends_with($classReflection->getName(), "Action")) {
            return [];
        }

        if (!$node->class instanceof Name || !in_array($node->class->toLowerString(), self::DATE_CLASSES, true)) {
            return [];
        }

        $requestType = new ObjectType(ServerRequestInterface::class);
        $nodeFinder = new NodeFinder();

        foreach ($node->getArgs() as $argument) {
            $receivers = $nodeFinder->find(
                $argument->value,
                static fn(Node $inner): bool => $inner instanceof MethodCall || $inner instanceof PropertyFetch,
            );

            foreach ($receivers as $receiver) {
                /** @var MethodCall|PropertyFetch $receiver */
                if ($requestType->isSuperTypeOf($scope->getType($receiver->var))->yes()) {
                    return [
                        RuleErrorBuilder::message(
                            "Date built from the raw request — parse a validated DTO field, not \$request.",
                        )->identifier("slim4.dateFromRawRequest")->build(),
                    ];
                }
            }
        }

        return [];
    }
}
