<?php

declare(strict_types=1);

namespace Grazulex\SemverSieve\Evaluators;

use Grazulex\SemverSieve\Configuration\SieveConfiguration;
use Grazulex\SemverSieve\Contracts\VersionComparatorInterface;
use Grazulex\SemverSieve\ValueObjects\ParsedRange;
use Grazulex\SemverSieve\ValueObjects\ParsedVersion;

/**
 * Evaluates whether versions satisfy range constraints.
 *
 * This class is responsible for the core logic of determining if a version
 * matches a range, taking into account prerelease handling and other options.
 */
final class RangeEvaluator
{
    public function __construct(
        private readonly VersionComparatorInterface $comparator,
        private readonly ?SieveConfiguration $configuration = null,
    ) {
    }

    /**
     * Check if a version satisfies a range.
     */
    public function satisfies(ParsedVersion $version, ParsedRange $range): bool
    {
        // Empty range matches everything
        if (!$range->hasConstraints()) {
            return true;
        }

        // Handle prerelease logic
        if ($version->isPrerelease() && !$this->shouldIncludePrerelease($version, $range)) {
            return false;
        }

        // Delegate to comparator for actual constraint evaluation
        return $this->comparator->satisfies($version, $range);
    }

    /**
     * Find all ranges that a version satisfies.
     *
     * @param array<ParsedRange> $ranges
     *
     * @return array<ParsedRange>
     */
    public function findSatisfyingRanges(ParsedVersion $version, array $ranges): array
    {
        $satisfying = [];

        foreach ($ranges as $range) {
            if ($this->satisfies($version, $range)) {
                $satisfying[] = $range;
            }
        }

        return $satisfying;
    }

    /**
     * Check if any version in a list satisfies a range.
     *
     * @param array<ParsedVersion> $versions
     */
    public function anySatisfies(array $versions, ParsedRange $range): bool
    {
        foreach ($versions as $version) {
            if ($this->satisfies($version, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Filter versions that satisfy a range.
     *
     * @param array<ParsedVersion> $versions
     *
     * @return array<ParsedVersion>
     */
    public function filterSatisfying(array $versions, ParsedRange $range): array
    {
        $satisfying = [];

        foreach ($versions as $version) {
            if ($this->satisfies($version, $range)) {
                $satisfying[] = $version;
            }
        }

        return $satisfying;
    }

    /**
     * Get the highest version that satisfies a range.
     *
     * @param array<ParsedVersion> $versions
     */
    public function getHighestSatisfying(array $versions, ParsedRange $range): ?ParsedVersion
    {
        $satisfying = $this->filterSatisfying($versions, $range);

        if ($satisfying === []) {
            return null;
        }

        $highest = $satisfying[0];

        foreach (array_slice($satisfying, 1) as $version) {
            if ($this->comparator->greaterThan($version, $highest)) {
                $highest = $version;
            }
        }

        return $highest;
    }

    /**
     * Get the lowest version that satisfies a range.
     *
     * @param array<ParsedVersion> $versions
     */
    public function getLowestSatisfying(array $versions, ParsedRange $range): ?ParsedVersion
    {
        $satisfying = $this->filterSatisfying($versions, $range);

        if ($satisfying === []) {
            return null;
        }

        $lowest = $satisfying[0];

        foreach (array_slice($satisfying, 1) as $version) {
            if ($this->comparator->lessThan($version, $lowest)) {
                $lowest = $version;
            }
        }

        return $lowest;
    }

    /**
     * Determine if a prerelease version should be included.
     *
     * According to SemVer:
     * - Prerelease versions are only included if explicitly allowed by range
     * - Or if the range itself targets the same prerelease series
     */
    private function shouldIncludePrerelease(ParsedVersion $version, ParsedRange $range): bool
    {
        // If configuration explicitly allows prereleases
        if ($this->configuration?->includePreReleases === true) {
            return true;
        }

        // If the range explicitly allows prereleases
        if ($range->allowsPrereleases()) {
            return true;
        }

        // Check if any constraint in the range targets the same prerelease base
        foreach ($range->getConstraints() as $constraint) {
            $constraintVersion = $constraint->getVersion();

            // If constraint version is prerelease and shares same core version
            if ($constraintVersion->isPrerelease() &&
                $this->hasSameCoreVersion($version, $constraintVersion)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if two versions have the same core version (major.minor.patch).
     */
    private function hasSameCoreVersion(ParsedVersion $a, ParsedVersion $b): bool
    {
        return $a->major === $b->major &&
               $a->minor === $b->minor &&
               $a->patch === $b->patch;
    }
}
