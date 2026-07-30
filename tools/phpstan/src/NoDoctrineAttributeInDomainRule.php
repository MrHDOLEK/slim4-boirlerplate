<?php

declare(strict_types=1);

namespace Slim4\PhpStan;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Attribute>
 */
final class NoDoctrineAttributeInDomainRule implements Rule
{
    public function getNodeType(): string
    {
        return Attribute::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!str_contains((string)$scope->getNamespace(), "\\Domain")) {
            return [];
        }

        $resolved = $node->name->getAttribute("resolvedName");
        $fullyQualifiedName = $resolved instanceof Name ? $resolved->toString() : $node->name->toString();

        if (!str_starts_with($fullyQualifiedName, "Doctrine\\ORM\\")) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                "No Doctrine attributes on domain classes — map the entity with XML in src/Infrastructure/Persistence/Doctrine/Mapping.",
            )->identifier("slim4.noDoctrineAttributeInDomain")->build(),
        ];
    }
}
