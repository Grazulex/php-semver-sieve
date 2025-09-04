<?php

declare(strict_types=1);

namespace Grazulex\SemverSieve;

use Grazulex\SemverSieve\Comparators\SemverComparator;
use Grazulex\SemverSieve\Configuration\SieveConfiguration;
use Grazulex\SemverSieve\Contracts\DialectInterface;
use Grazulex\SemverSieve\Contracts\VersionComparatorInterface;
use Grazulex\SemverSieve\Evaluators\RangeEvaluator;
use Grazulex\SemverSieve\ValueObjects\ParsedRange;
use Grazulex\SemverSieve\ValueObjects\ParsedVersion;

/**
 * Main entry point for version range checking operations.
 *
 * This class follows the Single Responsibility Principle by focusing solely
 * on coordinating version checking operations between its dependencies.
 */
final class Sieve
{
    private readonly VersionComparatorInterface $comparator;

    private readonly RangeEvaluator $evaluator;

    /**
     * @param DialectInterface $dialect The version dialect to use for parsing
     * @param SieveConfiguration|null $configuration Configuration options
     * @param VersionComparatorInterface|null $comparator Custom version comparator
     */
    public function __construct(
        private readonly DialectInterface $dialect,
        private readonly ?SieveConfiguration $configuration = null,
        ?VersionComparatorInterface $comparator = null,
    ) {
        $this->comparator = $comparator ?? new SemverComparator();
        $this->evaluator = new RangeEvaluator($this->comparator, $this->configuration);
    }

    /**
     * Check if a version string is included in any of the provided ranges.
     *
     * @param string $version The version string to check
     * @param array<string> $ranges Array of range strings to check against
     *
     * @return bool True if the version matches any range, false otherwise
     */
    public function includes(string $version, array $ranges): bool
    {
        $result = $this->match($version, $ranges);

        return $result['matched'];
    }

    /**
     * Get detailed matching information for a version against ranges.
     *
     * @param string $version The version string to check
     * @param array<string> $ranges Array of range strings to check against
     *
     * @return array{matched: bool, matched_ranges: array<string>, normalized_ranges: array<string>}
     */
    public function match(string $version, array $ranges): array
    {
        $parsedVersion = $this->parseVersion($version);
        $matchedRanges = [];
        $normalizedRanges = [];

        foreach ($ranges as $range) {
            $parsedRange = $this->parseRange($range);
            $normalizedRanges[] = $parsedRange->toNormalizedString();

            if ($this->evaluator->satisfies($parsedVersion, $parsedRange)) {
                $matchedRanges[] = $range;
            }
        }

        return [
            'matched' => count($matchedRanges) > 0,
            'matched_ranges' => $matchedRanges,
            'normalized_ranges' => $normalizedRanges,
        ];
    }

    /**
     * Parse a version string using the configured dialect.
     */
    public function parseVersion(string $version): ParsedVersion
    {
        $options = $this->getParsingOptions();

        return $this->dialect->parseVersion($version, $options);
    }

    /**
     * Parse a range string using the configured dialect.
     */
    public function parseRange(string $range): ParsedRange
    {
        $options = $this->getParsingOptions();

        return $this->dialect->parseRange($range, $options);
    }

    /**
     * Get the current dialect.
     */
    public function getDialect(): DialectInterface
    {
        return $this->dialect;
    }

    /**
     * Get the current configuration.
     */
    public function getConfiguration(): SieveConfiguration
    {
        return $this->configuration ?? SieveConfiguration::default();
    }

    /**
     * Get the version comparator.
     */
    public function getComparator(): VersionComparatorInterface
    {
        return $this->comparator;
    }

    /**
     * Convert configuration to parsing options array.
     *
     * @return array<string, mixed>
     */
    private function getParsingOptions(): array
    {
        $config = $this->getConfiguration();

        return [
            'include_prereleases' => $config->includePreReleases,
            'strict_segments' => $config->strictSegments,
            'allow_v_prefix' => $config->allowVPrefix,
            'case_insensitive' => $config->caseInsensitive,
            'allow_leading_zeros' => $config->allowLeadingZeros,
            'max_version_length' => $config->maxVersionLength,
        ];
    }
}
