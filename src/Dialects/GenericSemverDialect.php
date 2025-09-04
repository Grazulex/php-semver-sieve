<?php

declare(strict_types=1);

namespace Grazulex\SemverSieve\Dialects;

use Grazulex\SemverSieve\Contracts\DialectInterface;
use Grazulex\SemverSieve\Parsers\RangeParser;
use Grazulex\SemverSieve\Parsers\VersionParser;
use Grazulex\SemverSieve\ValueObjects\ParsedRange;
use Grazulex\SemverSieve\ValueObjects\ParsedVersion;

/**
 * Generic SemVer 2.0.0 dialect implementation.
 *
 * This dialect implements the standard Semantic Versioning specification
 * without any package manager specific extensions or modifications.
 */
final class GenericSemverDialect implements DialectInterface
{
    private readonly VersionParser $versionParser;

    private readonly RangeParser $rangeParser;

    public function __construct()
    {
        $this->versionParser = new VersionParser();
        $this->rangeParser = new RangeParser($this->versionParser);
    }

    /**
     * Parse a version string according to generic SemVer rules.
     */
    public function parseVersion(string $version, array $options = []): ParsedVersion
    {
        return $this->versionParser->parse($version, $options);
    }

    /**
     * Parse a range string according to generic SemVer rules.
     */
    public function parseRange(string $range, array $options = []): ParsedRange
    {
        return $this->rangeParser->parse($range, $options);
    }

    /**
     * Get the name of this dialect.
     */
    public function getName(): string
    {
        return 'generic-semver';
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
            '!==',    // Not equal (alias)
            '<',      // Less than
            '<=',     // Less than or equal
            '>',      // Greater than
            '>=',     // Greater than or equal
            '^',      // Caret range (compatible within major)
            '~',      // Tilde range (compatible within minor)
            '-',      // Hyphen range (from - to)
            '||',     // OR operator
            'x',      // Wildcard
            '*',      // Wildcard (alias)
        ];
    }

    /**
     * Get version parser for advanced usage.
     */
    public function getVersionParser(): VersionParser
    {
        return $this->versionParser;
    }

    /**
     * Get range parser for advanced usage.
     */
    public function getRangeParser(): RangeParser
    {
        return $this->rangeParser;
    }
}
