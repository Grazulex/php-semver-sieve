<?php

declare(strict_types=1);

namespace Grazulex\SemverSieve\Parsers;

use Grazulex\SemverSieve\Contracts\ParserInterface;
use Grazulex\SemverSieve\Exceptions\InvalidVersionException;
use Grazulex\SemverSieve\ValueObjects\ParsedVersion;

/**
 * Parser for semantic version strings.
 *
 * Handles parsing of version strings according to SemVer 2.0.0 specification:
 * MAJOR.MINOR.PATCH[-PRERELEASE][+BUILD]
 */
final class VersionParser implements ParserInterface
{
    private const VERSION_PATTERN = '/^v?(?P<major>0|[1-9]\d*)\.(?P<minor>0|[1-9]\d*)\.(?P<patch>0|[1-9]\d*)(?:-(?P<prerelease>(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+(?P<build>[0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/';

    private const LOOSE_VERSION_PATTERN = '/^v?(?P<major>\d+)(?:\.(?P<minor>\d+))?(?:\.(?P<patch>\d+))?(?:-(?P<prerelease>[0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?(?:\+(?P<build>[0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/';

    /**
     * Parse a version string into a ParsedVersion object.
     *
     * @param string $input The version string to parse
     * @param array<string, mixed> $options Configuration options
     *
     * @throws InvalidVersionException
     *
     * @return ParsedVersion The parsed version
     */
    public function parse(string $input, array $options = []): ParsedVersion
    {
        $input = trim($input);

        // Validate input
        $this->validateInput($input, $options);

        // Choose pattern based on strict mode
        $pattern = ($options['strict_segments'] ?? false)
            ? self::VERSION_PATTERN
            : self::LOOSE_VERSION_PATTERN;

        if (!preg_match($pattern, $input, $matches)) {
            throw InvalidVersionException::forVersion($input, 'Does not match semantic version pattern');
        }

        // Extract version components
        $major = (int) $matches['major'];
        $minor = (int) ($matches['minor'] ?? 0);
        $patch = (int) ($matches['patch'] ?? 0);

        // Parse prerelease identifiers
        $prerelease = [];
        if (isset($matches['prerelease']) && ($matches['prerelease'] !== '' && $matches['prerelease'] !== '0')) {
            $prerelease = $this->parsePrereleaseIdentifiers($matches['prerelease'], $options);
        }

        // Parse build metadata
        $build = [];
        if (isset($matches['build']) && ($matches['build'] !== '' && $matches['build'] !== '0')) {
            $build = $this->parseBuildIdentifiers($matches['build']);
        }

        // Additional validation
        $this->validateVersionNumbers($input, $major, $minor, $patch, $options);

        return new ParsedVersion($major, $minor, $patch, $prerelease, $build, $input);
    }

    /**
     * Validate input without full parsing.
     */
    public function validate(string $input): bool
    {
        try {
            $this->parse($input);

            return true;
        } catch (InvalidVersionException) {
            return false;
        }
    }

    /**
     * Get supported patterns for this parser.
     *
     * @return array<string>
     */
    public function getSupportedPatterns(): array
    {
        return [
            'MAJOR.MINOR.PATCH',
            'MAJOR.MINOR.PATCH-PRERELEASE',
            'MAJOR.MINOR.PATCH+BUILD',
            'MAJOR.MINOR.PATCH-PRERELEASE+BUILD',
            'vMAJOR.MINOR.PATCH (with v prefix)',
            'MAJOR.MINOR (loose mode)',
            'MAJOR (loose mode)',
        ];
    }

    /**
     * Validate input string.
     *
     * @param array<string, mixed> $options
     */
    private function validateInput(string $input, array $options): void
    {
        if ($input === '') {
            throw InvalidVersionException::empty();
        }

        $maxLength = $options['max_version_length'] ?? 256;
        if (strlen($input) > $maxLength) {
            throw InvalidVersionException::tooLong($input, $maxLength);
        }

        // Check v prefix if not allowed
        if (!($options['allow_v_prefix'] ?? true) && str_starts_with($input, 'v')) {
            throw InvalidVersionException::forVersion($input, 'Version prefix "v" is not allowed');
        }
    }

    /**
     * Parse prerelease identifiers.
     *
     * @param array<string, mixed> $options
     *
     * @return array<string>
     */
    private function parsePrereleaseIdentifiers(string $prerelease, array $options): array
    {
        $identifiers = explode('.', $prerelease);
        $caseInsensitive = $options['case_insensitive'] ?? true;

        foreach ($identifiers as &$identifier) {
            if ($identifier === '') {
                throw InvalidVersionException::invalidPrerelease($prerelease, 'Empty prerelease identifier');
            }

            // Validate identifier format
            if (!preg_match('/^[0-9a-zA-Z-]+$/', $identifier)) {
                throw InvalidVersionException::invalidPrerelease($prerelease, "Invalid characters in identifier: {$identifier}");
            }

            // Normalize case if needed
            if ($caseInsensitive) {
                $identifier = strtolower($identifier);
            }
        }

        return $identifiers;
    }

    /**
     * Parse build metadata identifiers.
     *
     * @return array<string>
     */
    private function parseBuildIdentifiers(string $build): array
    {
        $identifiers = explode('.', $build);

        foreach ($identifiers as $identifier) {
            if ($identifier === '') {
                throw InvalidVersionException::invalidBuild($build, 'Empty build identifier');
            }

            // Validate identifier format
            if (!preg_match('/^[0-9a-zA-Z-]+$/', $identifier)) {
                throw InvalidVersionException::invalidBuild($build, "Invalid characters in identifier: {$identifier}");
            }
        }

        return $identifiers;
    }

    /**
     * Validate version numbers.
     *
     * @param array<string, mixed> $options
     */
    private function validateVersionNumbers(string $input, int $major, int $minor, int $patch, array $options): void
    {
        // Check for negative numbers (should be caught by regex, but extra safety)
        if ($major < 0 || $minor < 0 || $patch < 0) {
            throw InvalidVersionException::negativeNumber($input, 'Version numbers cannot be negative');
        }

        // Check for leading zeros if not allowed
        if (!($options['allow_leading_zeros'] ?? false)) {
            $this->validateNoLeadingZeros($input, $major, $minor, $patch);
        }
    }

    /**
     * Validate that version numbers don't have leading zeros.
     */
    private function validateNoLeadingZeros(string $input, int $major, int $minor, int $patch): void
    {
        // Extract the numeric parts from the original string to check for leading zeros
        if (preg_match('/^v?(\d+)\.(\d+)\.(\d+)/', $input, $matches)) {
            if ($matches[1] !== (string) $major && $matches[1] !== '0') {
                throw InvalidVersionException::leadingZeros($input, $matches[1]);
            }
            if ($matches[2] !== (string) $minor && $matches[2] !== '0') {
                throw InvalidVersionException::leadingZeros($input, $matches[2]);
            }
            if ($matches[3] !== (string) $patch && $matches[3] !== '0') {
                throw InvalidVersionException::leadingZeros($input, $matches[3]);
            }
        }
    }
}
