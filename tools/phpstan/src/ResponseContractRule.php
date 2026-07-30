<?php

declare(strict_types=1);

namespace Slim4\PhpStan;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<InClassNode>
 */
final class ResponseContractRule implements Rule
{
    private const RESPONSE_INTERFACE = "JsonSerializable";

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $node->getClassReflection();

        if ($classReflection->isInterface() || $classReflection->isAbstract()) {
            return [];
        }

        if (!str_contains($classReflection->getName(), "App\\Application\\DTO\\Response\\")) {
            return [];
        }

        if ($classReflection->implementsInterface(self::RESPONSE_INTERFACE)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                "Response DTO must implement JsonSerializable — every HTTP payload is serialised through the same contract.",
            )->identifier("slim4.responseContract")->build(),
        ];
    }
}
