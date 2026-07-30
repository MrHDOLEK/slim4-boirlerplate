<?php

declare(strict_types=1);

namespace Slim4\PhpStan;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @implements Rule<MethodCall>
 */
final class NoRawRequestAccessInActionRule implements Rule
{
    private const RAW_BODY_METHODS = ["getBody", "getParsedBody"];
    private const RAW_QUERY_METHODS = ["getQueryParams"];

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $scope->getClassReflection();

        if ($classReflection === null || !str_ends_with($classReflection->getName(), "Action")) {
            return [];
        }

        if (!$node->name instanceof Identifier) {
            return [];
        }

        $methodName = $node->name->toString();

        if (
            !in_array($methodName, self::RAW_BODY_METHODS, true)
            && !in_array($methodName, self::RAW_QUERY_METHODS, true)
        ) {
            return [];
        }

        if (!(new ObjectType(ServerRequestInterface::class))->isSuperTypeOf($scope->getType($node->var))->yes()) {
            return [];
        }

        if (in_array($methodName, self::RAW_QUERY_METHODS, true)) {
            return [
                RuleErrorBuilder::message(
                    "Raw query params in an action — go through the Action base helpers or a validated DTO, not \$request->getQueryParams().",
                )->identifier("slim4.rawRequestQuery")->build(),
            ];
        }

        return [
            RuleErrorBuilder::message(
                "Raw request body in an action — go through the Action base helpers or a validated DTO, not \$request directly.",
            )->identifier("slim4.rawRequestBody")->build(),
        ];
    }
}
