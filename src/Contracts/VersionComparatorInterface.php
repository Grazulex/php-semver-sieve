<?php

declare(strict_types=1);

namespace Grazulex\SemverSieve\Contracts;

use Grazulex\SemverSieve\ValueObjects\ParsedRange;
use Grazulex\SemverSieve\ValueObjects\ParsedVersion;

/**
 * Interface for version comparison operations.
 *
 * Handles the core logic of comparing versions according to SemVer 2.0.0 rules.
 */
interface VersionComparatorInterface
{
    /**
     * Compare two parsed versions.
     *
     * @return int -1 if $a < $b, 0 if $a == $b, 1 if $a > $b
     */
    public function compare(ParsedVersion $a, ParsedVersion $b): int;

    /**
     * Check if a version satisfies a range constraint.
     */
    public function satisfies(ParsedVersion $version, ParsedRange $range): bool;

    /**
     * Check if a version is greater than another.
     */
    public function greaterThan(ParsedVersion $a, ParsedVersion $b): bool;

    /**
     * Check if a version is greater than or equal to another.
     */
    public function greaterThanOrEqual(ParsedVersion $a, ParsedVersion $b): bool;

    /**
     * Check if a version is less than another.
     */
    public function lessThan(ParsedVersion $a, ParsedVersion $b): bool;

    /**
     * Check if a version is less than or equal to another.
     */
    public function lessThanOrEqual(ParsedVersion $a, ParsedVersion $b): bool;

    /**
     * Check if two versions are equal.
     */
    public function equal(ParsedVersion $a, ParsedVersion $b): bool;
}
