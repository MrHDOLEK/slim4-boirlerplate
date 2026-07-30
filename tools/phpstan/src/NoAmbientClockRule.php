<?php

declare(strict_types=1);

namespace Slim4\PhpStan;

use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<New_>
 */
final class NoAmbientClockRule implements Rule
{
    private const EXEMPT_NAMESPACE_MARKERS = ["\\Domain\\Entity"];

    public function getNodeType(): string
    {
        return New_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $namespace = (string)$scope->getNamespace();

        foreach (self::EXEMPT_NAMESPACE_MARKERS as $marker) {
            if (str_contains($namespace, $marker)) {
                return [];
            }
        }

        if (!$node->class instanceof Name || $node->class->toLowerString() !== "datetimeimmutable") {
            return [];
        }

        $argument = $node->getArgs()[0]->value ?? null;

        if ($argument === null || ($argument instanceof String_ && strtolower($argument->value) === "now")) {
            return [
                RuleErrorBuilder::message(
                    "Do not read the ambient clock — inject Lcobucci\\Clock\\Clock and take the current time from its now(). (Domain entities are exempt.)",
                )->identifier("slim4.noAmbientClock")->build(),
            ];
        }

        return [];
    }
}
