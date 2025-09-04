<?php

declare(strict_types=1);

namespace Grazulex\SemverSieve\ValueObjects;

use InvalidArgumentException;

/**
 * Immutable value object representing a single version constraint.
 *
 * A constraint consists of an operator and a target version.
 * Examples: ">=1.2.3", "<2.0.0", "=1.0.0"
 */
final readonly class VersionConstraint
{
    /**
     * @param string $operator The comparison operator (>=, <, =, etc.)
     * @param ParsedVersion $version The target version for comparison
     */
    public function __construct(
        public string $operator,
        public ParsedVersion $version,
    ) {
        $this->validateOperator($operator);
    }

    /**
     * Get a string representation of this constraint.
     */
    public function toString(): string
    {
        return $this->operator . $this->version->toNormalizedString();
    }

    /**
     * Check if this constraint allows prerelease versions.
     */
    public function allowsPrereleases(): bool
    {
        return $this->version->isPrerelease() ||
               in_array($this->operator, ['>', '>='], true);
    }

    /**
     * Get the constraint operator.
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * Get the target version.
     */
    public function getVersion(): ParsedVersion
    {
        return $this->version;
    }

    /**
     * Validate that the operator is supported.
     */
    private function validateOperator(string $operator): void
    {
        $validOperators = ['<', '<=', '>', '>=', '=', '==', '!=', '!=='];

        if (!in_array($operator, $validOperators, true)) {
            throw new InvalidArgumentException("Unsupported operator: {$operator}");
        }
    }
}
