<?php

declare(strict_types=1);

namespace Grazulex\SemverSieve\Configuration;

use InvalidArgumentException;

/**
 * Immutable configuration value object for Sieve operations.
 *
 * This class encapsulates all configuration options in a single, immutable object,
 * following the Value Object pattern for better maintainability and type safety.
 */
final readonly class SieveConfiguration
{
    /**
     * @param bool $includePreReleases Include prerelease versions (alpha, beta, rc) when evaluating ranges
     * @param bool $strictSegments If false, treat "1.2" as "1.2.0"
     * @param bool $allowVPrefix Accept versions prefixed with "v" (e.g. "v1.2.3")
     * @param bool $caseInsensitive For prerelease identifiers ("RC" vs "rc")
     * @param bool $allowLeadingZeros Accept leading zeros in version numbers ("01.02.03")
     * @param int $maxVersionLength Maximum allowed length for version strings (security limit)
     * @param array<string, string> $customOperators Custom operator definitions
     */
    public function __construct(
        public bool $includePreReleases = false,
        public bool $strictSegments = false,
        public bool $allowVPrefix = true,
        public bool $caseInsensitive = true,
        public bool $allowLeadingZeros = false,
        public int $maxVersionLength = 256,
        public array $customOperators = [],
    ) {
        $this->validateConfiguration();
    }

    /**
     * Create a default configuration instance.
     */
    public static function default(): self
    {
        return new self();
    }

    /**
     * Create a strict configuration instance.
     *
     * Strict mode enforces more rigorous parsing rules:
     * - Requires exact segment counts
     * - Disallows "v" prefix
     * - Disallows leading zeros
     */
    public static function strict(): self
    {
        return new self(
            includePreReleases: false,
            strictSegments: true,
            allowVPrefix: false,
            caseInsensitive: false,
            allowLeadingZeros: false,
        );
    }

    /**
     * Create a lenient configuration instance.
     *
     * Lenient mode allows more flexible parsing:
     * - Includes prerelease versions by default
     * - Allows leading zeros
     * - More permissive overall
     */
    public static function lenient(): self
    {
        return new self(
            includePreReleases: true,
            strictSegments: false,
            allowVPrefix: true,
            caseInsensitive: true,
            allowLeadingZeros: true,
        );
    }

    /**
     * Create a new configuration with modified prerelease handling.
     */
    public function withPrereleases(bool $includePreReleases): self
    {
        return new self(
            $includePreReleases,
            $this->strictSegments,
            $this->allowVPrefix,
            $this->caseInsensitive,
            $this->allowLeadingZeros,
            $this->maxVersionLength,
            $this->customOperators,
        );
    }

    /**
     * Create a new configuration with modified strictness.
     */
    public function withStrictness(bool $strictSegments): self
    {
        return new self(
            $this->includePreReleases,
            $strictSegments,
            $this->allowVPrefix,
            $this->caseInsensitive,
            $this->allowLeadingZeros,
            $this->maxVersionLength,
            $this->customOperators,
        );
    }

    /**
     * Create a new configuration with custom operators.
     *
     * @param array<string, string> $customOperators
     */
    public function withCustomOperators(array $customOperators): self
    {
        return new self(
            $this->includePreReleases,
            $this->strictSegments,
            $this->allowVPrefix,
            $this->caseInsensitive,
            $this->allowLeadingZeros,
            $this->maxVersionLength,
            $customOperators,
        );
    }

    /**
     * Check if this configuration allows a specific feature.
     */
    public function allows(string $feature): bool
    {
        return match ($feature) {
            'prereleases' => $this->includePreReleases,
            'v_prefix' => $this->allowVPrefix,
            'leading_zeros' => $this->allowLeadingZeros,
            'case_insensitive' => $this->caseInsensitive,
            default => false,
        };
    }

    /**
     * Get configuration as an array for compatibility.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'include_prereleases' => $this->includePreReleases,
            'strict_segments' => $this->strictSegments,
            'allow_v_prefix' => $this->allowVPrefix,
            'case_insensitive' => $this->caseInsensitive,
            'allow_leading_zeros' => $this->allowLeadingZeros,
            'max_version_length' => $this->maxVersionLength,
            'custom_operators' => $this->customOperators,
        ];
    }

    /**
     * Validate configuration values.
     */
    private function validateConfiguration(): void
    {
        if ($this->maxVersionLength < 1) {
            throw new InvalidArgumentException('Maximum version length must be at least 1');
        }

        if ($this->maxVersionLength > 1024) {
            throw new InvalidArgumentException('Maximum version length cannot exceed 1024 characters');
        }

        // Validate custom operators
        foreach ($this->customOperators as $operator => $class) {
            if (!is_string($operator) || trim($operator) === '') {
                throw new InvalidArgumentException('Custom operator keys must be non-empty strings');
            }

            if (!is_string($class) || trim($class) === '') {
                throw new InvalidArgumentException('Custom operator values must be non-empty class names');
            }
        }
    }
}
