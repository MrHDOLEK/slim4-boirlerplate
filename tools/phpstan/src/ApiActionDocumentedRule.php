<?php

declare(strict_types=1);

namespace Slim4\PhpStan;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<InClassNode>
 */
final class ApiActionDocumentedRule implements Rule
{
    private const OPENAPI_NAMESPACE = "OpenApi\\Attributes\\";

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

        $name = $classReflection->getName();

        if (!str_contains($name, "App\\Application\\Actions\\") || !str_ends_with($name, "Action")) {
            return [];
        }

        $classNode = $node->getOriginalNode();

        if (!$classNode instanceof Class_) {
            return [];
        }

        if ($this->hasOpenApiAttribute($classNode->attrGroups)) {
            return [];
        }

        foreach ($classNode->getMethods() as $method) {
            if ($this->hasOpenApiAttribute($method->attrGroups)) {
                return [];
            }
        }

        return [
            RuleErrorBuilder::message(
                "Routed action must document its endpoint with an #[OA\\...] attribute — the published OpenAPI contract is generated from these.",
            )->identifier("slim4.apiActionDocumented")->build(),
        ];
    }

    /**
     * @param array<Node\AttributeGroup> $attributeGroups
     */
    private function hasOpenApiAttribute(array $attributeGroups): bool
    {
        foreach ($attributeGroups as $attributeGroup) {
            foreach ($attributeGroup->attrs as $attribute) {
                if (str_starts_with($this->resolveName($attribute->name), self::OPENAPI_NAMESPACE)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function resolveName(Name $name): string
    {
        $resolved = $name->getAttribute("resolvedName");

        return $resolved instanceof Name ? $resolved->toString() : $name->toString();
    }
}
