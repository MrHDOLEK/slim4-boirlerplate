<?php

declare(strict_types=1);

namespace Slim4\PhpStan;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\TraitUse;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<TraitUse>
 */
final class NoTraitUseRule implements Rule
{
    private const ALLOWED_TRAITS = [];

    public function getNodeType(): string
    {
        return TraitUse::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        foreach ($node->traits as $trait) {
            if (!in_array($this->resolveName($trait), self::ALLOWED_TRAITS, true)) {
                return [
                    RuleErrorBuilder::message(
                        "No PHP traits — compose behavior with a base class, a service, or composition instead.",
                    )->identifier("slim4.noTraitUse")->build(),
                ];
            }
        }

        return [];
    }

    private function resolveName(Name $name): string
    {
        $resolved = $name->getAttribute("resolvedName");

        return $resolved instanceof Name ? $resolved->toString() : $name->toString();
    }
}
