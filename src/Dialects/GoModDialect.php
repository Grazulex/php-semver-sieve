<?php

declare(strict_types=1);

namespace Grazulex\SemverSieve\Dialects;

use Grazulex\SemverSieve\Contracts\DialectInterface;
use Grazulex\SemverSieve\Parsers\RangeParser;
use Grazulex\SemverSieve\Parsers\VersionParser;
use Grazulex\SemverSieve\ValueObjects\ParsedRange;
use Grazulex\SemverSieve\ValueObjects\ParsedVersion;

/**
 * Go Modules dialect implementation.
 */
final class GoModDialect implements DialectInterface
{
    public function __construct(
        private readonly VersionParser $versionParser = new VersionParser(),
        private readonly RangeParser $rangeParser = new RangeParser(new VersionParser()),
    ) {}

    /**
     * @param array<string, mixed> $options
     */
    public function parseVersion(string $version, array $options = []): ParsedVersion
    {
        // Go modules require 'v' prefix
        if (!str_starts_with($version, 'v')) {
            throw new \InvalidArgumentException("Go module version must start with 'v': {$version}");
        }
        
        return $this->versionParser->parse($version, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function parseRange(string $range, array $options = []): ParsedRange
    {
        return $this->rangeParser->parse($range, $options);
    }

    public function getName(): string
    {
        return 'gomod';
    }

    /**
     * @return array<string>
     */
    public function getSupportedOperators(): array
    {
        return ['=', '!=', '>', '>=', '<', '<='];
    }
}
