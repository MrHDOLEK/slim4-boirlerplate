<?php

declare(strict_types=1);

namespace Slim4\PhpStan;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Type;

/**
 * @implements Rule<InClassMethodNode>
 */
final class DomainTypedCollectionRule implements Rule
{
    public function getNodeType(): string
    {
        return InClassMethodNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $scope->getClassReflection();

        if ($classReflection === null || !str_contains($classReflection->getName(), "\\Domain\\")) {
            return [];
        }

        $shortName = $this->shortName($classReflection->getName());

        if (str_ends_with($shortName, "Collection") || str_ends_with($shortName, "Catalog")) {
            return [];
        }

        $method = $node->getMethodReflection();

        if (!$method->isPublic()) {
            return [];
        }

        if (!$this->isArrayOfObjects($method->getVariants()[0]->getReturnType())) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                "Domain method returns array<object> — return a typed Collection value object, not a raw array of objects.",
            )->identifier("slim4.domainTypedCollection")->build(),
        ];
    }

    private function shortName(string $className): string
    {
        $position = strrpos($className, "\\");

        return $position === false ? $className : substr($className, $position + 1);
    }

    private function isArrayOfObjects(Type $type): bool
    {
        foreach ($type->getArrays() as $arrayType) {
            if ($arrayType instanceof ConstantArrayType) {
                continue;
            }

            $itemType = $arrayType->getItemType();

            if (!$itemType->isObject()->yes() || $itemType->isEnum()->yes() || $this->isIdentifier($itemType)) {
                continue;
            }

            return true;
        }

        return false;
    }

    private function isIdentifier(Type $type): bool
    {
        foreach ($type->getObjectClassNames() as $className) {
            $shortName = $this->shortName($className);

            if (str_ends_with($shortName, "Id") || str_ends_with($shortName, "Uid")) {
                return true;
            }
        }

        return false;
    }
}
