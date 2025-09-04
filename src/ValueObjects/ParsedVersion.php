<?php

declare(strict_types=1);

namespace Grazulex\SemverSieve\ValueObjects;

use InvalidArgumentException;

/**
 * Immutable value object representing a parsed semantic version.
 *
 * Follows SemVer 2.0.0 specification: MAJOR.MINOR.PATCH[-PRERELEASE][+BUILD]
 */
final readonly class ParsedVersion
{
    /**
     * @param int $major Major version number
     * @param int $minor Minor version number
     * @param int $patch Patch version number
     * @param array<string> $prerelease Prerelease identifiers (e.g., ['alpha', '1'])
     * @param array<string> $build Build metadata identifiers
     * @param string $raw Original version string
     */
    public function __construct(
        public int $major,
        public int $minor,
        public int $patch,
        public array $prerelease = [],
        public array $build = [],
        public string $raw = '',
    ) {
        if ($this->major < 0 || $this->minor < 0 || $this->patch < 0) {
            throw new InvalidArgumentException('Version numbers cannot be negative');
        }
    }

    /**
     * Check if this is a prerelease version.
     */
    public function isPrerelease(): bool
    {
        return $this->prerelease !== [];
    }

    /**
     * Check if this version has build metadata.
     */
    public function hasBuildMetadata(): bool
    {
        return $this->build !== [];
    }

    /**
     * Get the version as a normalized string without build metadata.
     */
    public function toNormalizedString(): string
    {
        $version = "{$this->major}.{$this->minor}.{$this->patch}";

        if ($this->isPrerelease()) {
            $version .= '-' . implode('.', $this->prerelease);
        }

        return $version;
    }

    /**
     * Get the full version string including build metadata.
     */
    public function toFullString(): string
    {
        $version = $this->toNormalizedString();

        if ($this->hasBuildMetadata()) {
            $version .= '+' . implode('.', $this->build);
        }

        return $version;
    }

    /**
     * Create a new instance with different prerelease identifiers.
     *
     * @param array<string> $prerelease
     */
    public function withPrerelease(array $prerelease): self
    {
        return new self(
            $this->major,
            $this->minor,
            $this->patch,
            $prerelease,
            $this->build,
            $this->raw,
        );
    }

    /**
     * Create a new instance with different build metadata.
     *
     * @param array<string> $build
     */
    public function withBuild(array $build): self
    {
        return new self(
            $this->major,
            $this->minor,
            $this->patch,
            $this->prerelease,
            $build,
            $this->raw,
        );
    }

    /**
     * Get the core version as an array.
     *
     * @return array{int, int, int}
     */
    public function getCoreVersion(): array
    {
        return [$this->major, $this->minor, $this->patch];
    }
}
