<?php

declare(strict_types=1);

namespace Grazulex\SemverSieve\Contracts;

use Grazulex\SemverSieve\ValueObjects\ParsedRange;
use Grazulex\SemverSieve\ValueObjects\ParsedVersion;

/**
 * Interface for different version dialects (Composer, npm, PyPI, etc.).
 *
 * Each dialect can have its own specific rules for parsing versions and ranges.
 * This interface ensures all dialects are interchangeable (LSP principle).
 */
interface DialectInterface
{
    /**
     * Parse a version string according to this dialect's rules.
     *
     * @param string $version The version string to parse (e.g., "1.2.3", "v1.2.3-alpha")
     * @param array<string, mixed> $options Configuration options
     *
     * @throws \Grazulex\SemverSieve\Exceptions\InvalidVersionException
     */
    public function parseVersion(string $version, array $options = []): ParsedVersion;

    /**
     * Parse a range string according to this dialect's rules.
     *
     * @param string $range The range string to parse (e.g., "^1.2.3", ">=1.0 <2.0")
     * @param array<string, mixed> $options Configuration options
     *
     * @throws \Grazulex\SemverSieve\Exceptions\InvalidRangeException
     */
    public function parseRange(string $range, array $options = []): ParsedRange;

    /**
     * Get the name of this dialect.
     */
    public function getName(): string;

    /**
     * Get supported operators for this dialect.
     *
     * @return array<string>
     */
    public function getSupportedOperators(): array;
}
