# Project Instructions: php-semver-sieve

## Overview
This project is a **pure PHP package** called `grazulex/php-semver-sieve`.  
It provides a reliable way to check if a **given version string** is included in one or more **version ranges**.  
It is designed to be:
- Framework-agnostic (not tied to Laravel or Composer).
- Dependency-free (no runtime dependencies).
- Based on pure logic (tokenization, parsing, normalization, evaluation).
- Compatible with SemVer 2.0.0 and common notations (Composer-like, npm-like, generic semver).

The package **does not parse composer.json or package.json files**.  
It only evaluates **one version string vs one or multiple version range strings**, returning true/false.

---

## API Goals

### Core
```php
use Grazulex\SemverSieve\Sieve;
use Grazulex\SemverSieve\Dialects\GenericSemverDialect;

$sieve = new Sieve(new GenericSemverDialect(), [
    'include_prereleases' => false,
    'strict_segments'     => false,
    'allow_v_prefix'      => true,
]);

// Boolean check
$sieve->includes('1.2.3', ['>=1.0 <2.0', '1.2.x']); // true/false

// Detailed check
$result = $sieve->match('2.0.0-beta.2', ['^2.0', '>=1.9 <2.0.0-rc.1']);
/* $result = [
  'matched' => true,
  'matched_ranges' => ['>=1.9, <2.0.0-rc.1'],
  'normalized_ranges' => ['>=2.0.0-0,<3.0.0-0', '>=1.9.0-0,<2.0.0-rc.1'],
] */
```

### Options
- `include_prereleases`: bool (default false). Include prerelease versions (`-alpha`, `-beta`, `-rc`) when evaluating ranges.
- `strict_segments`: bool (default false). If false, treat `1.2` as `1.2.0`.
- `allow_v_prefix`: bool (default true). Accept versions prefixed with `v` (e.g. `v1.2.3`).
- `case_insensitive`: bool (default true). For prerelease identifiers (`RC` vs `rc`).

---

## Supported Syntax

### Comparators
- `<`, `<=`, `>`, `>=`, `=`, `==`

### Exact version
- `1.2.3`

### Wildcards
- `1.2.x`, `1.2.*`, `1.x`, `*`

### Caret
- `^1.2.3` → `>=1.2.3 <2.0.0-0`

### Tilde
- `~1.2` → `>=1.2.0 <1.3.0-0`

### Hyphen ranges
- `1.2 - 1.4.5` → `>=1.2.0-0 <=1.4.5`

### OR / AND
- `^1.0 || ^2.0`
- `>=1.0 <2.0`

### Pre-releases
- `1.0.0-alpha`, `1.0.0-beta`, `1.0.0-rc.1`
- By default, excluded unless explicitly included or `include_prereleases=true`.

---

## Architecture & Design Principles

### SOLID Principles Implementation

#### Single Responsibility Principle (SRP)
Each class has a single, well-defined responsibility:
- `Sieve`: Main entry point for version checking operations
- `VersionParser`: Parses and normalizes version strings
- `RangeParser`: Parses and normalizes range expressions
- `VersionComparator`: Compares versions according to SemVer rules
- `RangeEvaluator`: Evaluates if a version matches a range
- `DialectInterface`: Defines parsing behavior for different version formats

#### Open/Closed Principle (OCP)
- New dialects can be added by implementing `DialectInterface`
- New operators can be added by extending the operator system
- Configuration options can be extended without modifying core classes

#### Liskov Substitution Principle (LSP)
- All dialect implementations are interchangeable
- All parser implementations follow the same contracts

#### Interface Segregation Principle (ISP)
- Small, focused interfaces for different concerns
- Clients depend only on methods they actually use

#### Dependency Inversion Principle (DIP)
- Core classes depend on abstractions (interfaces), not concrete implementations
- Dependency injection for all external dependencies

### Core Architecture

```php
// Main interfaces
interface DialectInterface
{
    public function parseVersion(string $version, array $options): ParsedVersion;
    public function parseRange(string $range, array $options): ParsedRange;
}

interface VersionComparatorInterface
{
    public function compare(ParsedVersion $a, ParsedVersion $b): int;
    public function satisfies(ParsedVersion $version, ParsedRange $range): bool;
}

interface ParserInterface
{
    public function parse(string $input, array $options): ParsedResult;
}

// Value objects
final readonly class ParsedVersion
{
    public function __construct(
        public int $major,
        public int $minor,
        public int $patch,
        public array $prerelease,
        public array $build,
        public string $raw
    ) {}
}

final readonly class ParsedRange
{
    public function __construct(
        public array $constraints,
        public string $raw
    ) {}
}

final readonly class VersionConstraint
{
    public function __construct(
        public string $operator,
        public ParsedVersion $version
    ) {}
}
```

### Error Handling

```php
// Custom exceptions hierarchy
abstract class SemverSieveException extends \Exception {}

final class InvalidVersionException extends SemverSieveException {}
final class InvalidRangeException extends SemverSieveException {}
final class UnsupportedDialectException extends SemverSieveException {}
final class ConfigurationException extends SemverSieveException {}
```

---

## Project Structure

```
src/
├── Sieve.php                          # Main entry point
├── Contracts/
│   ├── DialectInterface.php           # Dialect contract
│   ├── VersionComparatorInterface.php # Version comparison contract
│   └── ParserInterface.php            # Parser contract
├── Dialects/
│   ├── GenericSemverDialect.php       # Generic SemVer implementation
│   ├── ComposerDialect.php            # Composer-specific rules (getcomposer.org)
│   ├── NpmDialect.php                 # NPM-specific rules
│   ├── PypiDialect.php                # Python PyPI version rules
│   ├── RubyGemsDialect.php            # RubyGems version rules
│   ├── NugetDialect.php               # .NET NuGet version rules
│   ├── MavenDialect.php               # Java Maven version rules
│   └── GoModDialect.php               # Go modules version rules
├── Parsers/
│   ├── VersionParser.php              # Version string parsing
│   ├── RangeParser.php                # Range expression parsing
│   └── TokenParser.php                # Low-level tokenization
├── Comparators/
│   └── SemverComparator.php           # SemVer comparison logic
├── Evaluators/
│   └── RangeEvaluator.php             # Range evaluation logic
├── ValueObjects/
│   ├── ParsedVersion.php              # Immutable version representation
│   ├── ParsedRange.php                # Immutable range representation
│   └── VersionConstraint.php          # Single constraint representation
├── Exceptions/
│   ├── SemverSieveException.php       # Base exception
│   ├── InvalidVersionException.php    # Version parsing errors
│   ├── InvalidRangeException.php      # Range parsing errors
│   └── ConfigurationException.php     # Configuration errors
└── Configuration/
    ├── SieveConfiguration.php         # Configuration value object
    └── ConfigurationFactory.php       # Configuration factory

tests/
├── Unit/                              # Unit tests with Pest
│   ├── Dialects/
│   ├── Parsers/
│   ├── Comparators/
│   ├── Evaluators/
│   └── ValueObjects/
├── Integration/                       # Integration tests
│   └── SieveTest.php
├── Fixtures/                          # Test data
│   ├── versions.php
│   └── ranges.php
└── Pest.php                          # Pest configuration

```

---

## Testing Strategy

### Unit Tests with Pest
- **Dialect Tests**: Test each dialect's parsing behavior
- **Parser Tests**: Test version and range parsing logic
- **Comparator Tests**: Test version comparison edge cases
- **Evaluator Tests**: Test range evaluation logic
- **Value Object Tests**: Test immutability and validation

### Integration Tests
- **End-to-end scenarios**: Full sieve operations
- **Real-world examples**: Common version patterns from npm, Composer
- **Error scenarios**: Invalid inputs and edge cases

### Property-Based Testing
- Generate random valid versions and ranges
- Test properties like transitivity, reflexivity
- Mutation testing with Infection

### Test Categories
```php
// Example Pest test structure
<?php

use Grazulex\SemverSieve\Dialects\GenericSemverDialect;
use Grazulex\SemverSieve\Sieve;

describe('Sieve Core Functionality', function () {
    beforeEach(function () {
        $this->sieve = new Sieve(new GenericSemverDialect());
    });

    it('should match exact versions', function () {
        expect($this->sieve->includes('1.2.3', ['1.2.3']))->toBeTrue();
    });

    it('should handle caret ranges', function () {
        expect($this->sieve->includes('1.2.4', ['^1.2.3']))->toBeTrue();
        expect($this->sieve->includes('2.0.0', ['^1.2.3']))->toBeFalse();
    });
});

dataset('valid_versions', [
    '1.0.0',
    '1.2.3-alpha',
    '1.2.3-alpha.1',
    'v1.2.3',
    '10.2.1-rc.1+build.1'
]);

dataset('invalid_versions', [
    '',
    '1',
    '1.2',
    'invalid',
    '1.2.3.4.5'
]);
```

---

## Configuration & Options

### Configuration Object Pattern
```php
final readonly class SieveConfiguration
{
    public function __construct(
        public bool $includePreReleases = false,
        public bool $strictSegments = false,
        public bool $allowVPrefix = true,
        public bool $caseInsensitive = true,
        public bool $allowLeadingZeros = false,
        public int $maxVersionLength = 256,
        public array $customOperators = []
    ) {}

    public static function default(): self
    {
        return new self();
    }

    public static function strict(): self
    {
        return new self(
            strictSegments: true,
            allowVPrefix: false,
            allowLeadingZeros: false
        );
    }

    public static function lenient(): self
    {
        return new self(
            includePreReleases: true,
            allowLeadingZeros: true
        );
    }
}
```

---

## Development Workflow

### Quality Assurance Tools
- **PHP 8.2+ Required**: Modern PHP features (readonly classes, enums, etc.)
- **PHPStan Level 9**: Maximum static analysis
- **PHP-CS-Fixer**: Code style enforcement (PSR-12)
- **Rector**: Automated refactoring and PHP version updates
- **Infection**: Mutation testing for test quality
- **Composer Normalize**: Normalize composer.json

### Git Hooks & CI/CD
- **Pre-commit**: Run tests and static analysis
- **Pre-push**: Full test suite
- **GitHub Actions**: 
  - Matrix testing (PHP 8.2, 8.3, 8.4)
  - Multiple OS (Ubuntu, Windows, macOS)
  - Code coverage reporting
  - Security analysis (Psalm, PHPStan)
  - Quality gates (minimum coverage, mutation score)

### Performance Considerations
- **Immutable objects**: Prevent accidental mutations
- **Lazy loading**: Parse ranges only when needed
- **Caching**: Cache parsed results for repeated operations
- **Memory efficiency**: Use generators for large datasets

---

## Extension Points

### Custom Dialects
```php
final class CustomDialect implements DialectInterface
{
    public function parseVersion(string $version, array $options): ParsedVersion
    {
        // Custom version parsing logic
    }

    public function parseRange(string $range, array $options): ParsedRange
    {
        // Custom range parsing logic
    }
}
```

### Custom Operators
```php
// Register custom operators
$configuration = new SieveConfiguration(
    customOperators: [
        '~>' => TildeGreaterOperator::class,
        '≥' => GreaterEqualOperator::class,
    ]
);
```
