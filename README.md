# PHP SemVer Sieve

[![Tests](https://github.com/Grazulex/php-semver-sieve/workflows/Tests/badge.svg)](https://github.com/Grazulex/php-semver-sieve/actions)
[![PHPStan](https://github.com/Grazulex/php-semver-sieve/workflows/PHPStan/badge.svg)](https://github.com/Grazulex/php-semver-sieve/actions)
[![Mutation Testing](https://github.com/Grazulex/php-semver-sieve/workflows/Mutation%20Testing/badge.svg)](https://github.com/Grazulex/php-semver-sieve/actions)
[![Latest Stable Version](https://poser.pugx.org/grazulex/php-semver-sieve/v/stable)](https://packagist.org/packages/grazulex/php-semver-sieve)
[![License](https://poser.pugx.org/grazulex/php-semver-sieve/license)](https://packagist.org/packages/grazulex/php-semver-sieve)

A **pure PHP package** for checking if a version string is included in one or more version ranges. 

## Features

- ✅ **Framework-agnostic** - No Laravel or Composer dependencies
- ✅ **Zero runtime dependencies** - Pure PHP logic
- ✅ **SemVer 2.0.0 compatible** - Follows semantic versioning specification
- ✅ **Multiple dialects support** - Composer, npm, PyPI, RubyGems, Maven, Go modules, etc.
- ✅ **SOLID principles** - Clean, extensible architecture
- ✅ **Fully tested** - Unit tests with Pest, mutation testing with Infection
- ✅ **Static analysis** - PHPStan level 9 compliant
- ✅ **PHP 8.1+** - Modern PHP features (readonly properties, enums)

## Installation

```bash
composer require grazulex/php-semver-sieve
```

## Quick Start

```php
use Grazulex\SemverSieve\Sieve;
use Grazulex\SemverSieve\Dialects\GenericSemverDialect;

// Create a sieve instance
$sieve = new Sieve(new GenericSemverDialect());

// Simple boolean check
$matches = $sieve->includes('1.2.3', ['>=1.0 <2.0', '^1.2.0']);
// true

// Detailed analysis
$result = $sieve->match('2.0.0-beta.2', ['^2.0', '>=1.9 <2.0.0-rc.1']);
/*
[
    'matched' => true,
    'matched_ranges' => ['>=1.9 <2.0.0-rc.1'],
    'normalized_ranges' => ['>=2.0.0-0 <3.0.0-0', '>=1.9.0-0 <2.0.0-rc.1']
]
*/
```

## Supported Dialects

### Generic SemVer
```php
use Grazulex\SemverSieve\Dialects\GenericSemverDialect;
$sieve = new Sieve(new GenericSemverDialect());
```

### Composer (PHP)
```php
use Grazulex\SemverSieve\Dialects\ComposerDialect;
$sieve = new Sieve(new ComposerDialect());
$sieve->includes('1.2.3', ['~1.2', '^1.0']);
```

### npm (JavaScript)
```php
use Grazulex\SemverSieve\Dialects\NpmDialect;
$sieve = new Sieve(new NpmDialect());
$sieve->includes('1.2.3', ['~1.2.0', '^1.0.0']);
```

### PyPI (Python)
```php
use Grazulex\SemverSieve\Dialects\PypiDialect;
$sieve = new Sieve(new PypiDialect());
$sieve->includes('1.2.3', ['>=1.2,<2.0', '~=1.2']);
```

## Supported Syntax

| Syntax | Example | Description |
|--------|---------|-------------|
| Exact | `1.2.3` | Exact version match |
| Comparators | `>=1.0`, `<2.0` | Greater/less than |
| Caret | `^1.2.3` | Compatible within major |
| Tilde | `~1.2.3` | Compatible within minor |
| Wildcards | `1.2.*`, `1.x` | Wildcard matching |
| Ranges | `1.2 - 1.4` | Hyphen ranges |
| OR | `^1.0 \|\| ^2.0` | Multiple ranges |
| AND | `>=1.0 <2.0` | Combined constraints |

## Configuration

```php
use Grazulex\SemverSieve\Configuration\SieveConfiguration;

$config = new SieveConfiguration(
    includePreReleases: true,      // Include alpha, beta, rc versions
    strictSegments: false,         // Treat "1.2" as "1.2.0"
    allowVPrefix: true,            // Accept "v1.2.3" format
    caseInsensitive: true,         // "RC" vs "rc"
    allowLeadingZeros: false,      // "01.02.03" format
    maxVersionLength: 256          // Security limit
);

$sieve = new Sieve(new GenericSemverDialect(), $config);
```

### Preset Configurations

```php
// Default configuration
$config = SieveConfiguration::default();

// Strict mode
$config = SieveConfiguration::strict();

// Lenient mode  
$config = SieveConfiguration::lenient();
```

## Custom Dialects

Create your own dialect by implementing `DialectInterface`:

```php
use Grazulex\SemverSieve\Contracts\DialectInterface;

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

$sieve = new Sieve(new CustomDialect());
```

## Error Handling

```php
use Grazulex\SemverSieve\Exceptions\InvalidVersionException;
use Grazulex\SemverSieve\Exceptions\InvalidRangeException;

try {
    $sieve->includes('invalid-version', ['^1.0']);
} catch (InvalidVersionException $e) {
    echo "Invalid version: " . $e->getMessage();
} catch (InvalidRangeException $e) {
    echo "Invalid range: " . $e->getMessage();
}
```

## Development

### Requirements
- PHP 8.1+
- Composer

### Setup
```bash
git clone https://github.com/Grazulex/php-semver-sieve.git
cd php-semver-sieve
composer install
```

### Testing
```bash
# Run tests
composer test

# Run tests with coverage
composer test-coverage

# Static analysis
composer stan

# Code style check
composer cs-check

# Fix code style
composer cs-fix

# Mutation testing
composer infection

# Full quality check
composer quality
```

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Run quality checks (`composer quality`)
4. Commit your changes (`git commit -m 'Add amazing feature'`)
5. Push to the branch (`git push origin feature/amazing-feature`)
6. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Credits

- **Author**: Jean-Marc Strauven
- **Inspiration**: Based on SemVer 2.0.0 specification and various package managers' version handling
- **Architecture**: Follows SOLID principles for clean, maintainable code
