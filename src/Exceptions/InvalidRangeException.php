<?php

declare(strict_types=1);

namespace Grazulex\SemverSieve\Exceptions;

/**
 * Exception thrown when a range string cannot be parsed.
 *
 * This includes malformed ranges, unsupported operators,
 * or ranges that violate the dialect's specifications.
 */
final class InvalidRangeException extends SemverSieveException
{
    /**
     * Create an exception for an invalid range string.
     */
    public static function forRange(string $range, string $reason = ''): self
    {
        $message = "Invalid range string: '{$range}'";

        if ($reason !== '') {
            $message .= ". {$reason}";
        }

        return new self($message, ['range' => $range, 'reason' => $reason]);
    }

    /**
     * Create an exception for an empty range string.
     */
    public static function empty(): self
    {
        return new self('Range string cannot be empty', ['range' => '']);
    }

    /**
     * Create an exception for an unsupported operator.
     */
    public static function unsupportedOperator(string $range, string $operator): self
    {
        return new self(
            "Unsupported operator '{$operator}' in range '{$range}'",
            ['range' => $range, 'operator' => $operator],
        );
    }

    /**
     * Create an exception for malformed hyphen ranges.
     */
    public static function malformedHyphenRange(string $range): self
    {
        return new self(
            "Malformed hyphen range: '{$range}'. Expected format: 'version1 - version2'",
            ['range' => $range],
        );
    }

    /**
     * Create an exception for malformed caret ranges.
     */
    public static function malformedCaretRange(string $range): self
    {
        return new self(
            "Malformed caret range: '{$range}'. Expected format: '^version'",
            ['range' => $range],
        );
    }

    /**
     * Create an exception for malformed tilde ranges.
     */
    public static function malformedTildeRange(string $range): self
    {
        return new self(
            "Malformed tilde range: '{$range}'. Expected format: '~version'",
            ['range' => $range],
        );
    }

    /**
     * Create an exception for invalid wildcard patterns.
     */
    public static function invalidWildcard(string $range, string $pattern): self
    {
        return new self(
            "Invalid wildcard pattern '{$pattern}' in range '{$range}'",
            ['range' => $range, 'pattern' => $pattern],
        );
    }

    /**
     * Create an exception for conflicting constraints.
     *
     * @param array<string> $constraints
     */
    public static function conflictingConstraints(string $range, array $constraints): self
    {
        return new self(
            "Conflicting constraints in range '{$range}': " . implode(', ', $constraints),
            ['range' => $range, 'constraints' => $constraints],
        );
    }

    /**
     * Create an exception for invalid OR/AND combinations.
     */
    public static function invalidLogicalCombination(string $range): self
    {
        return new self(
            "Invalid logical combination in range '{$range}'. Cannot mix AND and OR operators",
            ['range' => $range],
        );
    }

    /**
     * Create an exception for ranges that are too complex.
     */
    public static function tooComplex(string $range, int $maxConstraints): self
    {
        return new self(
            "Range '{$range}' is too complex (exceeds {$maxConstraints} constraints)",
            ['range' => $range, 'maxConstraints' => $maxConstraints],
        );
    }
}
