<?php

declare(strict_types=1);

namespace Grazulex\SemverSieve\Dialects;

use Grazulex\SemverSieve\Contracts\DialectInterface;
use Grazulex\SemverSieve\Parsers\RangeParser;
use Grazulex\SemverSieve\Parsers\VersionParser;
use Grazulex\SemverSieve\ValueObjects\ParsedRange;
use Grazulex\SemverSieve\ValueObjects\ParsedVersion;
use Grazulex\SemverSieve\ValueObjects\VersionConstraint;

/**
 * NPM-specific dialect implementation.
 * 
 * Handles NPM's version and range syntax including:
 * - Special tags: latest, next, alpha, beta, rc, canary
 * - Extended range syntax: 1.2.3-alpha.1
 * - Workspace protocol: workspace:*, workspace:^, workspace:~
 * - NPM-specific operators and behaviors
 */
final class NpmDialect implements DialectInterface
{
    private const SPECIAL_TAGS = [
        'latest',
        'next',
        'alpha',
        'beta',
        'rc',
        'canary',
        'experimental',
        'dev',
        'nightly',
    ];

    public function __construct(
        private readonly VersionParser $versionParser = new VersionParser(),
        private readonly RangeParser $rangeParser = new RangeParser(new VersionParser()),
    ) {}

    /**
     * Parse a version string using NPM conventions.
     *
     * @param array<string, mixed> $options
     */
    public function parseVersion(string $version, array $options = []): ParsedVersion
    {
        $version = trim($version);
        
        // Handle special NPM tags
        if (in_array($version, self::SPECIAL_TAGS, true)) {
            return $this->parseSpecialTag($version);
        }
        
        // Use base version parser with NPM-specific options
        $npmOptions = array_merge($options, [
            'allow_v_prefix' => true,
            'strict_segments' => false,
            'case_insensitive' => true,
            'include_prereleases' => true, // NPM includes prereleases by default
        ]);
        
        return $this->versionParser->parse($version, $npmOptions);
    }

    /**
     * Parse a range string using NPM conventions.
     *
     * @param array<string, mixed> $options
     */
    public function parseRange(string $range, array $options = []): ParsedRange
    {
        $range = trim($range);
        
        if ($range === '') {
            return ParsedRange::any();
        }

        // Handle workspace protocol
        if (str_starts_with($range, 'workspace:')) {
            return $this->parseWorkspaceRange($range, $options);
        }

        // Handle X-ranges (1.2.x, 1.x, x)
        if (str_contains($range, 'x') || str_contains($range, 'X')) {
            return $this->parseXRange($range, $options);
        }

        // NPM specific options
        $npmOptions = array_merge($options, [
            'allow_v_prefix' => true,
            'strict_segments' => false,
            'case_insensitive' => true,
            'include_prereleases' => true,
        ]);

        return $this->rangeParser->parse($range, $npmOptions);
    }

    /**
     * Parse special NPM tags.
     */
    private function parseSpecialTag(string $tag): ParsedVersion
    {
        // Map special tags to high version numbers for comparison
        return match ($tag) {
            'latest' => new ParsedVersion(999999, 999999, 999999, [], ['npm-tag', 'latest'], $tag),
            'next' => new ParsedVersion(999999, 999999, 999998, [], ['npm-tag', 'next'], $tag),
            'alpha' => new ParsedVersion(0, 0, 0, ['alpha', '999999'], [], $tag),
            'beta' => new ParsedVersion(0, 0, 0, ['beta', '999999'], [], $tag),
            'rc' => new ParsedVersion(0, 0, 0, ['rc', '999999'], [], $tag),
            'canary' => new ParsedVersion(0, 0, 0, ['canary', '999999'], [], $tag),
            'experimental' => new ParsedVersion(0, 0, 0, ['experimental', '999999'], [], $tag),
            'dev' => new ParsedVersion(0, 0, 0, ['dev', '999999'], [], $tag),
            'nightly' => new ParsedVersion(0, 0, 0, ['nightly', '999999'], [], $tag),
            default => throw new \InvalidArgumentException("Unknown NPM tag: {$tag}"),
        };
    }

    /**
     * Parse workspace protocol ranges.
     *
     * @param array<string, mixed> $options
     */
    private function parseWorkspaceRange(string $range, array $options): ParsedRange
    {
        $workspacePart = substr($range, 10); // Remove 'workspace:'
        
        if ($workspacePart === '*') {
            // workspace:* means any version in workspace
            return ParsedRange::any();
        }
        
        // workspace:^ or workspace:~ - parse the inner range
        return $this->rangeParser->parse($workspacePart, $options);
    }

    /**
     * Parse X-ranges (1.2.x, 1.x, x).
     *
     * @param array<string, mixed> $options
     */
    private function parseXRange(string $range, array $options): ParsedRange
    {
        $range = strtolower($range);
        
        if ($range === 'x' || $range === '*') {
            return ParsedRange::any();
        }
        
        // Handle partial X-ranges like 1.2.x or 1.x
        $parts = explode('.', $range);
        $constraints = [];
        
        // Find the first X
        $xIndex = -1;
        for ($i = 0; $i < count($parts); $i++) {
            if ($parts[$i] === 'x') {
                $xIndex = $i;
                break;
            }
        }
        
        if ($xIndex === -1) {
            // No X found, parse normally
            return $this->rangeParser->parse($range, $options);
        }
        
        // Build range based on X position
        if ($xIndex === 0) {
            // x.y.z or x - any version
            return ParsedRange::any();
        }
        
        // Build lower bound
        $lowerParts = array_slice($parts, 0, $xIndex);
        while (count($lowerParts) < 3) {
            $lowerParts[] = '0';
        }
        $lowerVersion = implode('.', $lowerParts);
        
        // Build upper bound
        $upperParts = array_slice($parts, 0, $xIndex);
        $lastIndex = count($upperParts) - 1;
        $upperParts[$lastIndex] = (string) ((int) $upperParts[$lastIndex] + 1);
        while (count($upperParts) < 3) {
            $upperParts[] = '0';
        }
        $upperVersion = implode('.', $upperParts);
        
        $lower = $this->versionParser->parse($lowerVersion, $options);
        $upper = $this->versionParser->parse($upperVersion, $options);
        
        return new ParsedRange([
            new VersionConstraint('>=', $lower),
            new VersionConstraint('<', $upper),
        ], $range);
    }

    /**
     * Get the dialect name.
     */
    public function getName(): string
    {
        return 'npm';
    }

    /**
     * Get supported operators for NPM dialect.
     *
     * @return array<string>
     */
    public function getSupportedOperators(): array
    {
        return [
            // Standard semver operators
            '=', '!=', '>', '>=', '<', '<=',
            
            // NPM-specific operators
            '^', '~',
            
            // Range operators
            '||', ' ',
            
            // Workspace protocol
            'workspace:',
            
            // X-ranges
            'x', 'X', '*',
        ];
    }

    /**
     * Check if a string is a special NPM tag.
     */
    public function isSpecialTag(string $version): bool
    {
        return in_array($version, self::SPECIAL_TAGS, true);
    }

    /**
     * Check if a range uses workspace protocol.
     */
    public function isWorkspaceRange(string $range): bool
    {
        return str_starts_with($range, 'workspace:');
    }

    /**
     * Check if a range uses X-range syntax.
     */
    public function isXRange(string $range): bool
    {
        return str_contains(strtolower($range), 'x') || str_contains($range, '*');
    }
}
