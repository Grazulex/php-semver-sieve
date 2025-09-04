<?php

declare(strict_types=1);

namespace Grazulex\SemverSieve\Parsers;

use Grazulex\SemverSieve\Contracts\ParserInterface;
use Grazulex\SemverSieve\Exceptions\InvalidRangeException;
use Grazulex\SemverSieve\ValueObjects\ParsedRange;
use Grazulex\SemverSieve\ValueObjects\ParsedVersion;
use Grazulex\SemverSieve\ValueObjects\VersionConstraint;

/**
 * Parser for version range expressions.
 *
 * Handles parsing of range strings like:
 * - ">=1.0.0 <2.0.0" (compound ranges)
 * - "^1.2.3" (caret ranges)
 * - "~1.2.3" (tilde ranges)
 * - "1.2.x" (wildcard ranges)
 * - "1.2.3 - 1.4.5" (hyphen ranges)
 * - "^1.0 || ^2.0" (OR combinations)
 */
final class RangeParser implements ParserInterface
{
    public function __construct(
        private readonly VersionParser $versionParser,
    ) {
    }

    /**
     * Parse a range string into a ParsedRange object.
     *
     * @param string $input The range string to parse
     * @param array<string, mixed> $options Configuration options
     *
     * @throws InvalidRangeException
     *
     * @return ParsedRange The parsed range
     */
    public function parse(string $input, array $options = []): ParsedRange
    {
        $input = trim($input);

        if ($input === '') {
            throw InvalidRangeException::empty();
        }

        // Handle special cases
        if ($input === '*') {
            return ParsedRange::any();
        }

        // Split on OR operator first
        if (str_contains($input, '||')) {
            return $this->parseOrRange($input, $options);
        }

        // Parse as single range (possibly with AND constraints)
        return $this->parseAndRange($input, $options);
    }

    /**
     * Validate input without full parsing.
     */
    public function validate(string $input): bool
    {
        try {
            $this->parse($input);

            return true;
        } catch (InvalidRangeException) {
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
            'Exact: 1.2.3',
            'Comparison: >=1.0.0, <2.0.0, !=1.5.0',
            'Caret: ^1.2.3 (compatible within major)',
            'Tilde: ~1.2.3 (compatible within minor)',
            'Wildcard: 1.2.x, 1.x, *',
            'Hyphen: 1.2.3 - 1.4.5',
            'Compound: >=1.0.0 <2.0.0',
            'OR: ^1.0 || ^2.0',
        ];
    }

    /**
     * Parse OR range (multiple alternatives).
     *
     * @param array<string, mixed> $options
     */
    private function parseOrRange(string $input, array $options): ParsedRange
    {
        $parts = array_map('trim', explode('||', $input));
        $allConstraints = [];
        $constraintGroups = [];

        foreach ($parts as $part) {
            if ($part === '') {
                throw InvalidRangeException::forRange($input, 'Empty OR clause');
            }

            $andRange = $this->parseAndRange($part, $options);
            $groupConstraints = $andRange->getConstraints();

            $allConstraints = array_merge($allConstraints, $groupConstraints);
            $constraintGroups[] = $groupConstraints;
        }

        return new ParsedRange($allConstraints, $input, 'OR', $constraintGroups);
    }

    /**
     * Parse AND range (all constraints must be satisfied).
     *
     * @param array<string, mixed> $options
     */
    private function parseAndRange(string $input, array $options): ParsedRange
    {
        // Check for hyphen range first
        if (str_contains($input, ' - ')) {
            return $this->parseHyphenRange($input, $options);
        }

        // Check for special prefixes
        if (str_starts_with($input, '^')) {
            return $this->parseCaretRange($input, $options);
        }

        if (str_starts_with($input, '~')) {
            return $this->parseTildeRange($input, $options);
        }

        // Check for wildcards
        if (str_contains($input, 'x') || str_contains($input, '*')) {
            return $this->parseWildcardRange($input, $options);
        }

        // Parse as space-separated constraints
        return $this->parseCompoundRange($input, $options);
    }

    /**
     * Parse hyphen range: "1.2.3 - 1.4.5".
     *
     * @param array<string, mixed> $options
     */
    private function parseHyphenRange(string $input, array $options): ParsedRange
    {
        $parts = array_map('trim', explode(' - ', $input, 2));

        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            throw InvalidRangeException::malformedHyphenRange($input);
        }

        $from = $this->versionParser->parse($parts[0], $options);
        $to = $this->versionParser->parse($parts[1], $options);

        $constraints = [
            new VersionConstraint('>=', $from),
            new VersionConstraint('<=', $to),
        ];

        return new ParsedRange($constraints, $input);
    }

    /**
     * Parse caret range: "^1.2.3".
     *
     * @param array<string, mixed> $options
     */
    private function parseCaretRange(string $input, array $options): ParsedRange
    {
        $versionPart = substr($input, 1);

        if ($versionPart === '') {
            throw InvalidRangeException::malformedCaretRange($input);
        }

        $version = $this->versionParser->parse($versionPart, $options);

        // ^1.2.3 := >=1.2.3 <2.0.0-0
        // ^0.2.3 := >=0.2.3 <0.3.0-0
        // ^0.0.3 := >=0.0.3 <0.0.4-0

        $nextMajor = $version->major > 0
            ? new ParsedVersion($version->major + 1, 0, 0, ['0'])
            : ($version->minor > 0
                ? new ParsedVersion(0, $version->minor + 1, 0, ['0'])
                : new ParsedVersion(0, 0, $version->patch + 1, ['0']));

        $constraints = [
            new VersionConstraint('>=', $version),
            new VersionConstraint('<', $nextMajor),
        ];

        return new ParsedRange($constraints, $input);
    }

    /**
     * Parse tilde range: "~1.2.3".
     *
     * @param array<string, mixed> $options
     */
    private function parseTildeRange(string $input, array $options): ParsedRange
    {
        $versionPart = substr($input, 1);

        if ($versionPart === '') {
            throw InvalidRangeException::malformedTildeRange($input);
        }

        $version = $this->versionParser->parse($versionPart, $options);

        // ~1.2.3 := >=1.2.3 <1.3.0-0
        // ~1.2 := >=1.2.0 <1.3.0-0
        // ~1 := >=1.0.0 <2.0.0-0

        $nextMinor = new ParsedVersion($version->major, $version->minor + 1, 0, ['0']);

        $constraints = [
            new VersionConstraint('>=', $version),
            new VersionConstraint('<', $nextMinor),
        ];

        return new ParsedRange($constraints, $input);
    }

    /**
     * Parse wildcard range: "1.2.x", "1.x", "*".
     *
     * @param array<string, mixed> $options
     */
    private function parseWildcardRange(string $input, array $options): ParsedRange
    {
        if ($input === '*') {
            return ParsedRange::any();
        }

        // Replace wildcards with 0 for parsing
        $normalized = str_replace(['x', '*'], '0', $input);
        $version = $this->versionParser->parse($normalized, $options);

        // Determine range based on wildcard position
        if (str_contains($input, '1.2.x') || str_contains($input, '1.2.*')) {
            // 1.2.x := >=1.2.0 <1.3.0-0
            $nextMinor = new ParsedVersion($version->major, $version->minor + 1, 0, ['0']);
            $constraints = [
                new VersionConstraint('>=', $version),
                new VersionConstraint('<', $nextMinor),
            ];
        } elseif (preg_match('/^\d+\.x$|^\d+\.\*$/', $input)) {
            // 1.x := >=1.0.0 <2.0.0-0
            $nextMajor = new ParsedVersion($version->major + 1, 0, 0, ['0']);
            $constraints = [
                new VersionConstraint('>=', $version),
                new VersionConstraint('<', $nextMajor),
            ];
        } else {
            throw InvalidRangeException::invalidWildcard($input, 'Unsupported wildcard pattern');
        }

        return new ParsedRange($constraints, $input);
    }

    /**
     * Parse compound range with multiple space-separated constraints.
     *
     * @param array<string, mixed> $options
     */
    private function parseCompoundRange(string $input, array $options): ParsedRange
    {
        // Split on spaces and filter empty parts
        $parts = array_filter(array_map('trim', preg_split('/\s+/', $input)));

        if (empty($parts)) {
            throw InvalidRangeException::forRange($input, 'No valid constraints found');
        }

        $constraints = [];

        foreach ($parts as $part) {
            $constraints[] = $this->parseConstraint($part, $options);
        }

        return new ParsedRange($constraints, $input);
    }

    /**
     * Parse a single constraint like ">=1.2.3".
     *
     * @param array<string, mixed> $options
     */
    private function parseConstraint(string $constraint, array $options): VersionConstraint
    {
        // Extract operator and version
        if (preg_match('/^(>=|<=|>|<|!=|!==|=|==)(.+)$/', $constraint, $matches)) {
            $operator = $matches[1];
            $versionString = trim($matches[2]);
        } else {
            // No explicit operator means exact match
            $operator = '=';
            $versionString = $constraint;
        }

        if ($versionString === '') {
            throw InvalidRangeException::forRange($constraint, 'Empty version in constraint');
        }

        $version = $this->versionParser->parse($versionString, $options);

        return new VersionConstraint($operator, $version);
    }
}
