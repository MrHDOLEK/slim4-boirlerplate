<?php

declare(strict_types=1);

namespace Slim4\PhpStan;

use PhpParser\Node;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<String_>
 */
final class NoRawSqlStringRule implements Rule
{
    private const SQL_PATTERN = '/^\s*(?:SELECT\b.*\bFROM\b|INSERT\s+INTO\b|UPDATE\b.*\bSET\b|DELETE\s+FROM\b|ALTER\s+TABLE\b|CREATE\s+(?:TABLE|INDEX|VIEW|SCHEMA|DATABASE)\b|DROP\s+(?:TABLE|INDEX|VIEW|SCHEMA|DATABASE)\b|TRUNCATE\s+(?:TABLE\s+)?[a-z_]+\b)/i';

    public function getNodeType(): string
    {
        return String_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (preg_match(self::SQL_PATTERN, $node->value) !== 1) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                "Raw SQL string — go through Doctrine (repository, DQL or QueryBuilder) instead.",
            )->identifier("slim4.rawSql")->build(),
        ];
    }
}
