<?php

declare(strict_types=1);

namespace Grazulex\SemverSieve\ValueObjects;

use InvalidArgumentException;

/**
 * Immutable value object representing a parsed version range.
 *
 * A range consists of one or more constraints that must be satisfied.
 * Examples: ">=1.0.0 <2.0.0", "^1.2.3", "1.x"
 */
final readonly class ParsedRange
{
    /**
     * @param array<VersionConstraint> $constraints List of constraints for this range
     * @param string $raw Original range string
     * @param string $operator The logical operator between constraints ('AND', 'OR')
     * @param array<array<VersionConstraint>>|null $constraintGroups For OR logic, groups of AND constraints
     */
    public function __construct(
        public array $constraints,
        public string $raw = '',
        public string $operator = 'AND',
        public ?array $constraintGroups = null,
    ) {
        $this->validateConstraints($constraints);
        $this->validateOperator($operator);
    }

    /**
     * Check if this range has any constraints.
     */
    public function hasConstraints(): bool
    {
        return $this->constraints !== [];
    }

    /**
     * Check if this range allows prerelease versions.
     */
    public function allowsPrereleases(): bool
    {
        if ($this->constraints === []) {
            return false;
        }

        // If any constraint explicitly allows prereleases, the range allows them
        foreach ($this->constraints as $constraint) {
            if ($constraint->allowsPrereleases()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all constraints.
     *
     * @return array<VersionConstraint>
     */
    public function getConstraints(): array
    {
        return $this->constraints;
    }

    /**
     * Get constraint groups (for OR logic).
     *
     * @return array<array<VersionConstraint>>|null
     */
    public function getConstraintGroups(): ?array
    {
        return $this->constraintGroups;
    }

    /**
     * Get the first constraint (useful for simple ranges).
     */
    public function getFirstConstraint(): ?VersionConstraint
    {
        return $this->constraints[0] ?? null;
    }

    /**
     * Get a normalized string representation.
     */
    public function toNormalizedString(): string
    {
        if ($this->constraints === []) {
            return '*';
        }

        $constraintStrings = array_map(
            static fn (VersionConstraint $constraint): string => $constraint->toString(),
            $this->constraints,
        );

        $separator = $this->operator === 'OR' ? ' || ' : ' ';

        return implode($separator, $constraintStrings);
    }

    /**
     * Create a new range with additional constraints.
     *
     * @param array<VersionConstraint> $additionalConstraints
     */
    public function withAdditionalConstraints(array $additionalConstraints): self
    {
        return new self(
            array_merge($this->constraints, $additionalConstraints),
            $this->raw,
            $this->operator,
        );
    }

    /**
     * Create a simple range with a single constraint.
     */
    public static function simple(VersionConstraint $constraint): self
    {
        return new self([$constraint]);
    }

    /**
     * Create an empty range that matches everything.
     */
    public static function any(): self
    {
        return new self([]);
    }

    /**
     * Validate that all items in the array are VersionConstraint instances.
     *
     * @param array<VersionConstraint> $constraints
     */
    private function validateConstraints(array $constraints): void
    {
        foreach ($constraints as $constraint) {
            if (!$constraint instanceof VersionConstraint) {
                throw new InvalidArgumentException('All constraints must be VersionConstraint instances');
            }
        }
    }

    /**
     * Validate the logical operator.
     */
    private function validateOperator(string $operator): void
    {
        if (!in_array($operator, ['AND', 'OR'], true)) {
            throw new InvalidArgumentException("Invalid logical operator: {$operator}");
        }
    }
}
