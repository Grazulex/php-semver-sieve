<?php

declare(strict_types=1);

namespace Grazulex\SemverSieve\Comparators;

use Grazulex\SemverSieve\Contracts\VersionComparatorInterface;
use Grazulex\SemverSieve\ValueObjects\ParsedRange;
use Grazulex\SemverSieve\ValueObjects\ParsedVersion;
use Grazulex\SemverSieve\ValueObjects\VersionConstraint;

/**
 * SemVer 2.0.0 compliant version comparator.
 *
 * Implements the official Semantic Versioning comparison rules:
 * 1. Compare major, minor, patch numerically
 * 2. Pre-release versions have lower precedence than normal versions
 * 3. Compare pre-release identifiers lexically or numerically
 * 4. Build metadata is ignored in comparisons
 */
final class SemverComparator implements VersionComparatorInterface
{
    /**
     * Compare two parsed versions according to SemVer 2.0.0 rules.
     *
     * @return int -1 if $a < $b, 0 if $a == $b, 1 if $a > $b
     */
    public function compare(ParsedVersion $a, ParsedVersion $b): int
    {
        // Compare core version (major.minor.patch)
        $coreComparison = $this->compareCoreVersion($a, $b);
        if ($coreComparison !== 0) {
            return $coreComparison;
        }

        // If core versions are equal, compare pre-release
        return $this->comparePrerelease($a, $b);
    }

    /**
     * Check if a version satisfies a range constraint.
     */
    public function satisfies(ParsedVersion $version, ParsedRange $range): bool
    {
        if (!$range->hasConstraints()) {
            return true; // Empty range matches everything
        }

        $constraints = $range->getConstraints();

        // For AND logic, all constraints must be satisfied
        if ($range->operator === 'AND') {
            foreach ($constraints as $constraint) {
                if (!$this->satisfiesConstraint($version, $constraint)) {
                    return false;
                }
            }

            return true;
        }

        // For OR logic, at least one constraint group must be satisfied
        if ($range->operator === 'OR') {
            $constraintGroups = $range->getConstraintGroups();

            if ($constraintGroups !== null) {
                // Use constraint groups for proper OR logic
                foreach ($constraintGroups as $group) {
                    $groupSatisfied = true;
                    foreach ($group as $constraint) {
                        if (!$this->satisfiesConstraint($version, $constraint)) {
                            $groupSatisfied = false;

                            break;
                        }
                    }
                    if ($groupSatisfied) {
                        return true;
                    }
                }

                return false;
            }
            // Fallback to individual constraint OR logic
            foreach ($constraints as $constraint) {
                if ($this->satisfiesConstraint($version, $constraint)) {
                    return true;
                }
            }

            return false;
        }

        return false;
    }

    /**
     * Check if a version is greater than another.
     */
    public function greaterThan(ParsedVersion $a, ParsedVersion $b): bool
    {
        return $this->compare($a, $b) > 0;
    }

    /**
     * Check if a version is greater than or equal to another.
     */
    public function greaterThanOrEqual(ParsedVersion $a, ParsedVersion $b): bool
    {
        return $this->compare($a, $b) >= 0;
    }

    /**
     * Check if a version is less than another.
     */
    public function lessThan(ParsedVersion $a, ParsedVersion $b): bool
    {
        return $this->compare($a, $b) < 0;
    }

    /**
     * Check if a version is less than or equal to another.
     */
    public function lessThanOrEqual(ParsedVersion $a, ParsedVersion $b): bool
    {
        return $this->compare($a, $b) <= 0;
    }

    /**
     * Check if two versions are equal.
     */
    public function equal(ParsedVersion $a, ParsedVersion $b): bool
    {
        return $this->compare($a, $b) === 0;
    }

    /**
     * Compare core version numbers (major.minor.patch).
     */
    private function compareCoreVersion(ParsedVersion $a, ParsedVersion $b): int
    {
        // Compare major version
        if ($a->major !== $b->major) {
            return $a->major <=> $b->major;
        }

        // Compare minor version
        if ($a->minor !== $b->minor) {
            return $a->minor <=> $b->minor;
        }

        // Compare patch version
        return $a->patch <=> $b->patch;
    }

    /**
     * Compare pre-release versions according to SemVer rules.
     */
    private function comparePrerelease(ParsedVersion $a, ParsedVersion $b): int
    {
        $aHasPrerelease = $a->isPrerelease();
        $bHasPrerelease = $b->isPrerelease();

        // Normal version has higher precedence than pre-release
        if (!$aHasPrerelease && $bHasPrerelease) {
            return 1;
        }
        if ($aHasPrerelease && !$bHasPrerelease) {
            return -1;
        }
        if (!$aHasPrerelease && !$bHasPrerelease) {
            return 0;
        }

        // Both have pre-release, compare identifiers
        return $this->comparePrereleaseIdentifiers($a->prerelease, $b->prerelease);
    }

    /**
     * Compare pre-release identifier arrays.
     *
     * @param array<string> $a
     * @param array<string> $b
     */
    private function comparePrereleaseIdentifiers(array $a, array $b): int
    {
        $maxLength = max(count($a), count($b));

        for ($i = 0; $i < $maxLength; $i++) {
            // If one array is shorter, it has lower precedence
            if (!isset($a[$i])) {
                return -1;
            }
            if (!isset($b[$i])) {
                return 1;
            }

            $comparison = $this->compareIdentifier($a[$i], $b[$i]);
            if ($comparison !== 0) {
                return $comparison;
            }
        }

        return 0;
    }

    /**
     * Compare individual pre-release identifiers.
     */
    private function compareIdentifier(string $a, string $b): int
    {
        $aIsNumeric = is_numeric($a);
        $bIsNumeric = is_numeric($b);

        // Numeric identifiers are compared numerically
        if ($aIsNumeric && $bIsNumeric) {
            return (int) $a <=> (int) $b;
        }

        // Numeric identifiers have lower precedence than non-numeric
        if ($aIsNumeric && !$bIsNumeric) {
            return -1;
        }
        if (!$aIsNumeric && $bIsNumeric) {
            return 1;
        }

        // Both non-numeric, compare lexically
        return $a <=> $b;
    }

    /**
     * Check if a version satisfies a single constraint.
     */
    private function satisfiesConstraint(ParsedVersion $version, VersionConstraint $constraint): bool
    {
        $comparison = $this->compare($version, $constraint->getVersion());

        return match ($constraint->getOperator()) {
            '<' => $comparison < 0,
            '<=' => $comparison <= 0,
            '>' => $comparison > 0,
            '>=' => $comparison >= 0,
            '=', '==' => $comparison === 0,
            '!=', '!==' => $comparison !== 0,
            default => false,
        };
    }
}
