<?php

declare(strict_types=1);

namespace Slim4\PhpStan;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\TypeCombinator;

/**
 * @implements Rule<InClassNode>
 */
final class DomainEventContractRule implements Rule
{
    private const DOMAIN_EVENT_CLASS = "App\\Infrastructure\\Events\\DomainEvent";

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

        if (!$classReflection->isSubclassOf(self::DOMAIN_EVENT_CLASS)) {
            return [];
        }

        if (!$classReflection->hasConstructor()) {
            return [];
        }

        $errors = [];

        foreach ($classReflection->getConstructor()->getVariants()[0]->getParameters() as $parameter) {
            $type = TypeCombinator::removeNull($parameter->getType());

            if ($type->isArray()->yes() || $type->isIterable()->yes()) {
                $errors[] = RuleErrorBuilder::message(sprintf(
                    'Domain event field "$%s" must not be array/iterable — DomainEvent::jsonSerialize() puts the payload on the wire, so model it as a scalar or a typed value object.',
                    $parameter->getName(),
                ))->identifier("slim4.domainEventPayload")->build();
            }
        }

        return $errors;
    }
}
