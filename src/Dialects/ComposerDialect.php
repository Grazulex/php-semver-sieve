<?php

declare(strict_types=1);

namespace Grazulex\SemverSieve\Dialects;

use Grazulex\SemverSieve\Contracts\DialectInterface;
use Grazulex\SemverSieve\Parsers\RangeParser;
use Grazulex\SemverSieve\Parsers\VersionParser;
use Grazulex\SemverSieve\ValueObjects\ParsedRange;
use Grazulex\SemverSieve\ValueObjects\ParsedVersion;

/**
 * Composer-specific version dialect.
 *
 * Implements Composer's version constraint format as documented at:
 * https://getcomposer.org/doc/articles/versions.md
 *
 * Key differences from generic SemVer:
 * - More lenient version parsing (allows missing minor/patch)
 * - Support for additional operators like "~>"
 * - Specific handling of stability flags (@stable, @dev, etc.)
 */
final class ComposerDialect implements DialectInterface
{
    private readonly VersionParser $versionParser;

    private readonly RangeParser $rangeParser;

    public function __construct()
    {
        $this->versionParser = new VersionParser();
        $this->rangeParser = new RangeParser($this->versionParser);
    }

    /**
     * Parse a version string according to Composer rules.
     */
    public function parseVersion(string $version, array $options = []): ParsedVersion
    {
        // Composer allows more lenient version formats
        $composerOptions = array_merge($options, [
            'strict_segments' => false,  // Allow 1.2 instead of 1.2.0
            'allow_v_prefix' => true,    // Allow v1.2.3
            'allow_leading_zeros' => false,
        ]);

        // Handle Composer stability flags
        $version = $this->normalizeComposerVersion($version);

        return $this->versionParser->parse($version, $composerOptions);
    }

    /**
     * Parse a range string according to Composer rules.
     */
    public function parseRange(string $range, array $options = []): ParsedRange
    {
        // Handle Composer-specific operators
        $range = $this->normalizeComposerRange($range);

        $composerOptions = array_merge($options, [
            'strict_segments' => false,
            'allow_v_prefix' => true,
        ]);

        return $this->rangeParser->parse($range, $composerOptions);
    }

    /**
     * Get the name of this dialect.
     */
    public function getName(): string
    {
        return 'composer';
    }

    /**
     * Get supported operators for this dialect.
     *
     * @return array<string>
     */
    public function getSupportedOperators(): array
    {
        return [
            '=',      // Exact match
            '==',     // Exact match (alias)
            '!=',     // Not equal
            '<',      // Less than
            '<=',     // Less than or equal
            '>',      // Greater than
            '>=',     // Greater than or equal
            '^',      // Caret range (compatible within major)
            '~',      // Tilde range (compatible within minor)
            '~>',     // Pessimistic operator (Composer extension)
            '-',      // Hyphen range (from - to)
            '||',     // OR operator
            '|',      // OR operator (Composer alias)
            'x',      // Wildcard
            '*',      // Wildcard (alias)
            'as',     // Version alias (inline alias)
        ];
    }

    /**
     * Normalize Composer version format.
     *
     * Handles stability flags and other Composer-specific features.
     */
    private function normalizeComposerVersion(string $version): string
    {
        // Remove stability flags (@stable, @dev, @alpha, @beta, @RC)
        $version = preg_replace('/@(stable|dev|alpha|beta|RC)$/i', '', $version);

        // Handle version aliases (e.g., "dev-master as 1.0.x-dev")
        if (str_contains((string) $version, ' as ')) {
            [$actual, $alias] = explode(' as ', (string) $version, 2);
            $version = trim($alias);
        }

        // Handle dev branches (e.g., "dev-feature-branch")
        if (str_starts_with((string) $version, 'dev-')) {
            // Convert to a high version for comparison purposes
            $version = '999999.999999.999999-dev';
        }

        return trim((string) $version);
    }

    /**
     * Normalize Composer range format.
     *
     * Handles Composer-specific range operators and syntax.
     */
    private function normalizeComposerRange(string $range): string
    {
        // Convert single | to ||
        $range = preg_replace('/(?<!\|)\|(?!\|)/', '||', $range);

        // Handle pessimistic operator ~> (convert to tilde)
        $range = str_replace('~>', '~', $range);

        // Handle inline aliases
        $range = preg_replace('/\s+as\s+[^\s,|]+/', '', $range);

        return trim((string) $range);
    }
}
