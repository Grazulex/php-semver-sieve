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
 * Maven (Java) dialect implementation.
 * 
 * Handles Maven version and range syntax including:
 * - Qualifiers: 1.0-SNAPSHOT, 1.0-RELEASE, 1.0-alpha-1
 * - Version ranges: [1.0,2.0), (1.0,2.0], [1.0,2.0]
 * - Soft requirements: 1.0, 1.0+
 * - Multiple segments: 1.2.3.4.5
 * - Special qualifiers with ordering: alpha < beta < milestone < rc < snapshot < "" < sp
 */
final class MavenDialect implements DialectInterface
{
    private const QUALIFIER_ORDER = [
        'alpha' => 1,
        'a' => 1,
        'beta' => 2,
        'b' => 2,
        'milestone' => 3,
        'm' => 3,
        'rc' => 4,
        'cr' => 4,
        'snapshot' => 5,
        '' => 6, // Release version
        'ga' => 6,
        'final' => 6,
        'release' => 6,
        'sp' => 7, // Service pack
    ];

    public function __construct(
        private readonly RangeParser $rangeParser = new RangeParser(new VersionParser()),
    ) {}

    /**
     * Parse a version string using Maven conventions.
     */
    public function parseVersion(string $version, array $options = []): ParsedVersion
    {
        $version = trim($version);
        $originalVersion = $version;
        
        // Maven version parsing is quite complex
        // Examples: 1.0, 1.0-SNAPSHOT, 1.0-alpha-1, 1.2.3-beta-4
        
        // Split on the first dash to separate version from qualifier
        $parts = explode('-', $version, 2);
        $versionPart = $parts[0];
        $qualifierPart = $parts[1] ?? '';
        
        // Parse numeric version segments
        $segments = explode('.', $versionPart);
        
        if (empty($segments) || !is_numeric($segments[0])) {
            throw new \InvalidArgumentException("Invalid Maven version format: {$originalVersion}");
        }
        
        $major = (int) $segments[0];
        $minor = isset($segments[1]) && is_numeric($segments[1]) ? (int) $segments[1] : 0;
        $patch = isset($segments[2]) && is_numeric($segments[2]) ? (int) $segments[2] : 0;
        
        // Store additional segments in build metadata
        $build = [];
        if (count($segments) > 3) {
            $build[] = 'segments';
            for ($i = 3; $i < count($segments); $i++) {
                if (is_numeric($segments[$i])) {
                    $build[] = $segments[$i];
                }
            }
        }
        
        // Parse qualifier
        $prerelease = [];
        if ($qualifierPart !== '') {
            $prerelease = $this->parseQualifier($qualifierPart);
        }
        
        return new ParsedVersion($major, $minor, $patch, $prerelease, $build, $originalVersion);
    }

    /**
     * Parse a range string using Maven conventions.
     */
    public function parseRange(string $range, array $options = []): ParsedRange
    {
        $range = trim($range);
        
        if ($range === '') {
            return ParsedRange::any();
        }

        // Handle Maven range notation [1.0,2.0), (1.0,2.0], etc.
        if ((str_starts_with($range, '[') || str_starts_with($range, '(')) && 
            (str_ends_with($range, ']') || str_ends_with($range, ')'))) {
            return $this->parseMavenRange($range, $options);
        }

        // Handle soft requirements with +
        if (str_ends_with($range, '+')) {
            return $this->parseSoftRequirement($range, $options);
        }

        // Maven specific options
        $mavenOptions = array_merge($options, [
            'allow_v_prefix' => false, // Maven doesn't use 'v' prefix
            'strict_segments' => false, // Allow multiple segments
            'case_insensitive' => true, // Qualifiers are case-insensitive
            'include_prereleases' => true, // Maven includes qualifiers by default
        ]);

        // For simple version constraints, treat as exact match
        try {
            $version = $this->parseVersion($range, $mavenOptions);
            return new ParsedRange([
                new VersionConstraint('=', $version),
            ], $range);
        } catch (\InvalidArgumentException) {
            // Fall back to standard range parsing
            return $this->rangeParser->parse($range, $mavenOptions);
        }
    }

    /**
     * Parse Maven range notation [1.0,2.0), (1.0,2.0], etc.
     *
     * @param array<string, mixed> $options
     */
    private function parseMavenRange(string $range, array $options): ParsedRange
    {
        $startInclusive = str_starts_with($range, '[');
        $endInclusive = str_ends_with($range, ']');
        
        // Remove brackets/parentheses
        $inner = substr($range, 1, -1);
        
        // Handle single version in brackets [1.0] (exact match)
        if (!str_contains($inner, ',')) {
            $version = $this->parseVersion($inner, $options);
            return new ParsedRange([
                new VersionConstraint('=', $version),
            ], $range);
        }
        
        $parts = explode(',', $inner, 2);
        
        if (count($parts) !== 2) {
            throw new \InvalidArgumentException("Invalid Maven range notation: {$range}");
        }
        
        $lowerVersion = trim($parts[0]);
        $upperVersion = trim($parts[1]);
        
        $constraints = [];
        
        if ($lowerVersion !== '') {
            $lower = $this->parseVersion($lowerVersion, $options);
            $constraints[] = new VersionConstraint($startInclusive ? '>=' : '>', $lower);
        }
        
        if ($upperVersion !== '') {
            $upper = $this->parseVersion($upperVersion, $options);
            $constraints[] = new VersionConstraint($endInclusive ? '<=' : '<', $upper);
        }
        
        return new ParsedRange($constraints, $range);
    }

    /**
     * Parse soft requirement (1.0+).
     *
     * @param array<string, mixed> $options
     */
    private function parseSoftRequirement(string $range, array $options): ParsedRange
    {
        $versionPart = rtrim($range, '+');
        $version = $this->parseVersion($versionPart, $options);
        
        // 1.0+ means >= 1.0
        return new ParsedRange([
            new VersionConstraint('>=', $version),
        ], $range);
    }

    /**
     * Parse Maven qualifier.
     *
     * @return array<string>
     */
    private function parseQualifier(string $qualifier): array
    {
        $qualifier = strtolower($qualifier);
        
        // Handle compound qualifiers like "alpha-1", "beta-2"
        $parts = explode('-', $qualifier);
        $prerelease = [];
        
        foreach ($parts as $part) {
            if (array_key_exists($part, self::QUALIFIER_ORDER)) {
                // Map known qualifiers
                if ($part !== '') { // Don't add empty release qualifier
                    $prerelease[] = $part;
                }
            } elseif (is_numeric($part)) {
                $prerelease[] = $part;
            } else {
                // Unknown qualifier, add as-is
                $prerelease[] = $part;
            }
        }
        
        return $prerelease;
    }

    /**
     * Get the dialect name.
     */
    public function getName(): string
    {
        return 'maven';
    }

    /**
     * Get supported operators for Maven dialect.
     *
     * @return array<string>
     */
    public function getSupportedOperators(): array
    {
        return [
            // Range notation
            '[', ']', '(', ')', ',',
            
            // Soft requirements
            '+',
            
            // Standard comparison (rare in Maven)
            '=', '!=', '>', '>=', '<', '<=',
        ];
    }

    /**
     * Get qualifier ordering.
     *
     * @return array<string, int>
     */
    public function getQualifierOrder(): array
    {
        return self::QUALIFIER_ORDER;
    }
}
