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
final class EventHandlerContractRule implements Rule
{
    private const HANDLER_INTERFACE = "App\\Infrastructure\\Events\\EventHandler\\EventHandler";
    private const HANDLER_ATTRIBUTE = "App\\Infrastructure\\Attribute\\AsEventHandler";

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
        $implementsHandler = $classReflection->implementsInterface(self::HANDLER_INTERFACE);

        if (!$implementsHandler && !str_ends_with($name, "EventHandler")) {
            return [];
        }

        $classNode = $node->getOriginalNode();

        if (!$classNode instanceof Class_) {
            return [];
        }

        $errors = [];

        if ($implementsHandler && !$this->hasHandlerAttribute($classNode)) {
            $errors[] = RuleErrorBuilder::message(
                "Event handler is missing #[AsEventHandler] — EventHandlerCompilerPass discovers handlers by that attribute, so without it the handler is silently never subscribed.",
            )->identifier("slim4.eventHandlerNotSubscribed")->build();
        }

        if (!$implementsHandler && $this->hasHandlerAttribute($classNode)) {
            $errors[] = RuleErrorBuilder::message(
                "Class is tagged #[AsEventHandler] but does not implement App\\Infrastructure\\Events\\EventHandler\\EventHandler.",
            )->identifier("slim4.eventHandlerContract")->build();
        }

        return $errors;
    }

    private function hasHandlerAttribute(Class_ $classNode): bool
    {
        foreach ($classNode->attrGroups as $attributeGroup) {
            foreach ($attributeGroup->attrs as $attribute) {
                if ($this->resolveName($attribute->name) === self::HANDLER_ATTRIBUTE) {
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
