# 🔍 PHP SemVer Sieve

[![Tests](https://github.com/Grazulex/php-semver-sieve/workflows/Tests/badge.svg)](https://github.com/Grazulex/php-semver-sieve/actions)
[![PHPStan](https://github.com/Grazulex/php-semver-sieve/workflows/PHPStan/badge.svg)](https://github.com/Grazulex/php-semver-sieve/actions)
[![Mutation Testing](https://github.com/Grazulex/php-semver-sieve/workflows/Mutation%20Testing/badge.svg)](https://github.com/Grazulex/php-semver-sieve/actions)
[![Latest Stable Version](https://poser.pugx.org/grazulex/php-semver-sieve/v/stable)](https://packagist.org/packages/grazulex/php-semver-sieve)
[![License](https://poser.pugx.org/grazulex/php-semver-sieve/license)](https://packagist.org/packages/grazulex/php-semver-sieve)

A **powerful, universal PHP package** for checking if version strings match version ranges across **all major package managers**. 

> 🎯 **One API to rule them all** - Handle version constraints from Composer, npm, PyPI, RubyGems, Maven, NuGet, Go modules, and more! 

## ✨ Features

- 🏗️ **Framework-agnostic** - No Laravel or Composer dependencies
- 🚀 **Zero runtime dependencies** - Pure PHP logic with performance focus
- 📏 **SemVer 2.0.0 compatible** - Follows semantic versioning specification
- 🌍 **8 Package Manager Dialects** - Universal support for all major ecosystems:
  - 🐘 **PHP** (Composer) - `^1.0`, `~1.2`, `>=1.0 <2.0`
  - 📦 **JavaScript** (npm) - Special tags, workspace protocol, X-ranges
  - 🐍 **Python** (PyPI) - PEP 440 with dev/post releases, epochs
  - 💎 **Ruby** (RubyGems) - Pessimistic constraints (`~>`)
  - ☕ **Java** (Maven) - SNAPSHOT versions, range notation
  - 🔷 **C#/.NET** (NuGet) - 4-segment versions, interval notation
  - 🐹 **Go** (Go modules) - `v` prefix, pseudo-versions, incompatible versions
  - 🎯 **Generic SemVer** - Standards-compliant base implementation
- 🏛️ **SOLID Architecture** - Clean, extensible, maintainable codebase
- 🧪 **Fully tested** - 42 tests, 126 assertions, mutation testing
- 🔍 **Static analysis** - PHPStan level 6 compliant
- ⚡ **PHP 8.2+** - Modern features (readonly classes, enums, match expressions)

## 📊 Project Stats

- **24 source files** with **3,600+ lines of code**
- **42 passing tests** with **126 assertions** 
- **8 complete dialects** covering all major package managers
- **Zero runtime dependencies** for maximum compatibility
- **100% type coverage** with PHPStan

## Installation

```bash
composer require grazulex/php-semver-sieve
```

## 🚀 Quick Start

```php
use Grazulex\SemverSieve\Sieve;
use Grazulex\SemverSieve\Dialects\GenericSemverDialect;

// Create a sieve instance
$sieve = new Sieve(new GenericSemverDialect());

// Simple boolean check
$matches = $sieve->includes('1.2.3', ['>=1.0 <2.0', '^1.2.0']);
// → true

// Detailed analysis with metadata
$result = $sieve->match('2.0.0-beta.2', ['^2.0', '>=1.9 <2.0.0-rc.1']);
// → [
//     'matched' => true,
//     'matched_ranges' => ['>=1.9 <2.0.0-rc.1'],
//     'normalized_ranges' => ['>=2.0.0-0 <3.0.0-0', '>=1.9.0-0 <2.0.0-rc.1']
//   ]
```

## 🎯 Universal Package Manager Support

### 🌟 One API, All Ecosystems

```php
// PHP/Composer
$composer = new Sieve(new ComposerDialect());
$composer->includes('1.2.3', ['~1.2', '^1.0']); // → true

// JavaScript/npm  
$npm = new Sieve(new NpmDialect());
$npm->includes('1.2.3', ['latest', 'workspace:^1.0']); // → true

// Python/PyPI
$pypi = new Sieve(new PypiDialect());
$pypi->includes('1.2.3', ['>=1.2,<2.0', '~=1.2']); // → true

// Ruby/RubyGems
$rubygems = new Sieve(new RubyGemsDialect());
$rubygems->includes('1.2.3', ['~> 1.2', '>= 1.0']); // → true

// Java/Maven
$maven = new Sieve(new MavenDialect());
$maven->includes('1.2.3', ['[1.0,2.0)', '1.2+']); // → true

// .NET/NuGet
$nuget = new Sieve(new NugetDialect());
$nuget->includes('1.2.3.4', ['[1.0,2.0)', '1.2.*']); // → true

// Go/Go Modules
$gomod = new Sieve(new GoModDialect());
$gomod->includes('v1.2.3', ['v1.2.3', 'v1.2+incompatible']); // → true
```

## 📚 Dialect-Specific Features

### 🐘 Composer Dialect (PHP)
```php
use Grazulex\SemverSieve\Dialects\ComposerDialect;
$sieve = new Sieve(new ComposerDialect());

// Composer-specific syntax
$sieve->includes('1.2.3', [
    '^1.0',        // Caret: >=1.0.0 <2.0.0-0
    '~1.2',        // Tilde: >=1.2.0 <1.3.0-0  
    '>=1.0 <2.0',  // Combined constraints
    '1.2.*'        // Wildcard
]);
```

### 📦 npm Dialect (JavaScript/Node.js)
```php
use Grazulex\SemverSieve\Dialects\NpmDialect;
$sieve = new Sieve(new NpmDialect());

// npm-specific features
$sieve->includes('1.2.3', [
    'latest',           // Special tags
    'next',             // Distribution tags
    'workspace:*',      // Workspace protocol
    'workspace:^1.0',   // Workspace with range
    '1.2.x',           // X-ranges
    '1.x.x',           // Multi-segment X-ranges
]);

// Special npm tags supported:
// latest, next, alpha, beta, rc, canary, experimental, dev, nightly
```

### 🐍 PyPI Dialect (Python)
```php
use Grazulex\SemverSieve\Dialects\PypiDialect;
$sieve = new Sieve(new PypiDialect());

// PEP 440 compliant syntax
$sieve->includes('1.2.3', [
    '~=1.2',       // Compatible release
    '===1.2.3',    // Arbitrary equality  
    '>=1.0,<2.0',  // Comma-separated constraints
    '!=1.2.4',     // Exclusion
]);

// Supports: dev releases, post releases, epochs, local versions
```

### 💎 RubyGems Dialect (Ruby)
```php
use Grazulex\SemverSieve\Dialects\RubyGemsDialect;
$sieve = new Sieve(new RubyGemsDialect());

// Ruby-specific pessimistic constraints
$sieve->includes('1.2.3', [
    '~> 1.2',      // Pessimistic: >= 1.2, < 1.3
    '~> 1.2.0',    // Pessimistic: >= 1.2.0, < 1.3.0
    '>= 1.0',      // Standard comparison
]);
```

### ☕ Maven Dialect (Java)
```php
use Grazulex\SemverSieve\Dialects\MavenDialect;
$sieve = new Sieve(new MavenDialect());

// Maven version ranges and qualifiers
$sieve->includes('1.2.3-SNAPSHOT', [
    '[1.0,2.0)',        // Range: >= 1.0, < 2.0
    '[1.0,2.0]',        // Range: >= 1.0, <= 2.0
    '(1.0,2.0)',        // Range: > 1.0, < 2.0
    '1.0+',             // Soft requirement: >= 1.0
]);

// Maven qualifiers ordering:
// alpha < beta < milestone < rc < snapshot < release < sp
```

### 🔷 NuGet Dialect (.NET)
```php
use Grazulex\SemverSieve\Dialects\NugetDialect;
$sieve = new Sieve(new NugetDialect());

// NuGet 4-segment versions and interval notation
$sieve->includes('1.2.3.4', [
    '[1.0,2.0)',       // Interval notation
    '1.2.*',           // Floating versions
    '1.*',             // Major floating
    '*',               // Any version
]);
```

### 🐹 Go Modules Dialect
```php
use Grazulex\SemverSieve\Dialects\GoModDialect;
$sieve = new Sieve(new GoModDialect());

// Go module versions (require 'v' prefix)
$sieve->includes('v1.2.3', [
    'v1.2.3',                    // Exact version
    'v2.0.0+incompatible',       // Incompatible version
]);

// Supports pseudo-versions: v0.0.0-20191109021931-daa7c04131f5
```

### 🎯 Generic SemVer Dialect
```php
use Grazulex\SemverSieve\Dialects\GenericSemverDialect;
$sieve = new Sieve(new GenericSemverDialect());

// Standards-compliant SemVer 2.0.0
$sieve->includes('1.2.3-alpha.1+build.1', [
    '>=1.0.0',         // Comparison operators
    '^1.2',            // Caret ranges
    '~1.2.3',          // Tilde ranges
    '1.2.3-alpha.1',   // Prerelease versions
]);
```

## 📋 Complete Syntax Reference

| Syntax | Example | Dialects | Description |
|--------|---------|----------|-------------|
| **Exact** | `1.2.3` | All | Exact version match |
| **Comparators** | `>=1.0`, `<2.0`, `!=1.5` | All | Greater/less than, not equal |
| **Caret** | `^1.2.3` | Composer, npm, Generic | Compatible within major version |
| **Tilde** | `~1.2.3` | Composer, npm, Generic | Compatible within minor version |
| **Pessimistic** | `~> 1.2` | RubyGems | Ruby pessimistic constraint |
| **Wildcards** | `1.2.*`, `1.x`, `*` | Most | Wildcard matching |
| **X-Ranges** | `1.2.x`, `1.x.x` | npm | npm-style X-ranges |
| **Hyphen Ranges** | `1.2 - 1.4` | Generic, Composer | Inclusive range |
| **Interval** | `[1.0,2.0)`, `(1.0,2.0]` | Maven, NuGet | Mathematical intervals |
| **OR Logic** | `^1.0 \|\| ^2.0` | Most | Multiple range options |
| **AND Logic** | `>=1.0 <2.0` | Most | Combined constraints |
| **Special Tags** | `latest`, `next`, `beta` | npm | Distribution tags |
| **Workspace** | `workspace:*`, `workspace:^1.0` | npm | Workspace protocol |
| **Floating** | `1.2.*`, `1.*` | NuGet | Floating versions |
| **Soft Req** | `1.0+` | Maven | Soft requirements |
| **Qualifiers** | `1.0-SNAPSHOT`, `1.0-RELEASE` | Maven | Maven qualifiers |
| **Compatible** | `~=1.2` | PyPI | PEP 440 compatible release |
| **Arbitrary** | `===1.2.3` | PyPI | Arbitrary equality |
| **v-prefix** | `v1.2.3` | Go | Go module versions |
| **4-segment** | `1.2.3.4` | NuGet | .NET revision numbers |

## 🔧 Advanced Configuration

### Configuration Options
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
// Default: Balanced settings for most use cases
$config = SieveConfiguration::default();

// Strict: Enforce strict SemVer compliance
$config = SieveConfiguration::strict();
// - strictSegments: true
// - allowVPrefix: false  
// - allowLeadingZeros: false

// Lenient: Accept more version formats
$config = SieveConfiguration::lenient();
// - includePreReleases: true
// - allowLeadingZeros: true
```

## 🎨 Custom Dialects & Extensions

### Creating Custom Dialects

```php
use Grazulex\SemverSieve\Contracts\DialectInterface;
use Grazulex\SemverSieve\ValueObjects\ParsedVersion;
use Grazulex\SemverSieve\ValueObjects\ParsedRange;

final class CustomDialect implements DialectInterface
{
    public function parseVersion(string $version, array $options): ParsedVersion
    {
        // Custom version parsing logic
        // Handle your specific version format
    }

    public function parseRange(string $range, array $options): ParsedRange  
    {
        // Custom range parsing logic
        // Handle your specific constraint syntax
    }
    
    public function getName(): string
    {
        return 'custom';
    }
    
    public function getSupportedOperators(): array
    {
        return ['=', '>', '<', '>=', '<=', '~', '^'];
    }
}

$sieve = new Sieve(new CustomDialect());
```

### Real-World Custom Dialect Example

```php
// Example: Custom calendar versioning dialect
final class CalVerDialect implements DialectInterface
{
    public function parseVersion(string $version, array $options): ParsedVersion
    {
        // Parse versions like: 2023.12, 2024.01.15
        if (preg_match('/^(\d{4})\.(\d{1,2})(?:\.(\d{1,2}))?$/', $version, $matches)) {
            $year = (int) $matches[1];
            $month = (int) $matches[2];
            $day = isset($matches[3]) ? (int) $matches[3] : 1;
            
            return new ParsedVersion($year, $month, $day, [], [], $version);
        }
        
        throw new InvalidArgumentException("Invalid CalVer format: {$version}");
    }
    
    // ... rest of implementation
}
```

## 🚨 Comprehensive Error Handling

```php
use Grazulex\SemverSieve\Exceptions\{
    InvalidVersionException,
    InvalidRangeException,
    ConfigurationException,
    SemverSieveException
};

try {
    $sieve->includes('invalid.version.format', ['^1.0']);
} catch (InvalidVersionException $e) {
    echo "Invalid version: " . $e->getMessage();
    // Access error context
    $context = $e->getContext();
    echo "Failed version: " . $context['version'];
} catch (InvalidRangeException $e) {
    echo "Invalid range: " . $e->getMessage();
} catch (ConfigurationException $e) {
    echo "Configuration error: " . $e->getMessage();
} catch (SemverSieveException $e) {
    // Catch-all for any sieve-related errors
    echo "Sieve error: " . $e->getMessage();
}
```

## 🏗️ SOLID Architecture Deep Dive

### Single Responsibility Principle (SRP)
Each class has one clear purpose:
- `Sieve` - Main API facade
- `VersionParser` - Parse version strings  
- `RangeParser` - Parse range expressions
- `VersionComparator` - Compare versions
- `RangeEvaluator` - Evaluate range matching

### Open/Closed Principle (OCP)  
- ✅ Add new dialects without modifying existing code
- ✅ Extend with new operators via configuration
- ✅ Plugin architecture for custom behaviors

### Liskov Substitution Principle (LSP)
- ✅ All dialects are interchangeable via `DialectInterface`
- ✅ Consistent behavior across implementations
- ✅ Polymorphic usage guaranteed

### Interface Segregation Principle (ISP)
- ✅ Small, focused interfaces
- ✅ Clients depend only on methods they use
- ✅ No forced dependencies on unused functionality

### Dependency Inversion Principle (DIP)
- ✅ Core classes depend on abstractions (interfaces)
- ✅ Dependency injection throughout
- ✅ High-level modules independent of low-level details

## 📈 Performance & Benchmarks

### Optimizations
- **Immutable objects** - Thread-safe, cacheable
- **Lazy evaluation** - Parse only when needed
- **Memory efficient** - Minimal object allocation
- **Type safety** - PHP 8.2+ strict typing

### Typical Performance
```php
// Version parsing: ~0.1ms per version
// Range evaluation: ~0.05ms per constraint  
// Memory usage: ~2KB per Sieve instance
```

## 🧪 Testing & Quality Assurance

### Test Coverage
- **42 tests** with **126 assertions**
- **Unit tests** for all components
- **Integration tests** for end-to-end scenarios  
- **Property-based testing** for edge cases
- **Mutation testing** with Infection

### Quality Tools
- **PHPStan Level 6** - Static analysis
- **PHP-CS-Fixer** - PSR-12 code style
- **Rector** - Automated refactoring
- **Pest** - Modern testing framework

```bash
# Run complete quality suite
composer quality

# Individual commands
composer test          # Run tests
composer stan          # Static analysis  
composer cs-fix         # Fix code style
composer infection      # Mutation testing
```

## 💼 Real-World Use Cases

### 🔍 Dependency Analysis Tools
```php
// Analyze if package versions satisfy constraints
$composer = new Sieve(new ComposerDialect());
$compatible = $composer->includes('2.1.5', ['^2.0', '!=2.1.3']);

// Multi-ecosystem dependency checker
$ecosystems = [
    'php' => new ComposerDialect(),
    'js' => new NpmDialect(), 
    'python' => new PypiDialect(),
    'java' => new MavenDialect(),
];

foreach ($ecosystems as $name => $dialect) {
    $sieve = new Sieve($dialect);
    $results[$name] = $sieve->includes($version, $constraints);
}
```

### 📦 Package Registry & Mirrors
```php
// Filter package versions by compatibility
$npm = new Sieve(new NpmDialect());
$compatibleVersions = array_filter($allVersions, function($version) use ($npm) {
    return $npm->includes($version, ['>=14.0.0', '<16.0.0']);
});
```

### 🚀 CI/CD Pipeline Integration
```php
// Validate release versions against policies
$policy = ['^1.0', '!=1.2.3', '<2.0.0-0']; // No prereleases
$sieve = new Sieve(new GenericSemverDialect());

if (!$sieve->includes($releaseVersion, $policy)) {
    throw new Exception("Version {$releaseVersion} violates release policy");
}
```

### 🔧 Version Range Intersection
```php
// Find common version ranges across dependencies
$result1 = $sieve->match('1.5.0', ['^1.0', '>=1.4']);
$result2 = $sieve->match('1.5.0', ['~1.5', '<1.6']);
$intersection = array_intersect($result1['matched_ranges'], $result2['matched_ranges']);
```

## 🌐 Ecosystem Compatibility Matrix

| Feature | Generic | Composer | npm | PyPI | RubyGems | Maven | NuGet | Go |
|---------|---------|----------|-----|------|----------|-------|-------|----| 
| **Basic Comparisons** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Caret Ranges** | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Tilde Ranges** | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Pessimistic** | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ |
| **Wildcards** | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ |
| **X-Ranges** | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Interval Notation** | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ | ❌ |
| **Special Tags** | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **4+ Segments** | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ | ❌ |
| **v-Prefix Required** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| **Prerelease Support** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

## 💻 Development & Contributing

### 🛠️ Local Development Setup
```bash
# Clone repository
git clone https://github.com/Grazulex/php-semver-sieve.git
cd php-semver-sieve

# Install dependencies
composer install

# Run tests
composer test

# Check code quality
composer quality
```

### 🧪 Development Commands
```bash
# Testing
composer test                 # Run all tests
composer test-coverage        # Run with coverage report
composer test-unit            # Unit tests only
composer test-integration     # Integration tests only

# Code Quality  
composer stan                 # PHPStan static analysis
composer cs-check             # Check code style
composer cs-fix               # Fix code style issues
composer rector               # Modernize code  
composer infection            # Mutation testing

# Combined
composer quality              # Full quality check suite
```

### 🏗️ Project Structure
```
src/
├── Sieve.php                          # 🎯 Main entry point
├── Contracts/
│   ├── DialectInterface.php           # 📄 Dialect contract
│   ├── VersionComparatorInterface.php # 📄 Comparison contract  
│   └── ParserInterface.php            # 📄 Parser contract
├── Dialects/                          # 🌍 8 Package manager dialects
│   ├── GenericSemverDialect.php       # 🎯 Generic SemVer
│   ├── ComposerDialect.php            # 🐘 PHP/Composer
│   ├── NpmDialect.php                 # 📦 JavaScript/npm
│   ├── PypiDialect.php                # 🐍 Python/PyPI
│   ├── RubyGemsDialect.php            # 💎 Ruby/RubyGems
│   ├── MavenDialect.php               # ☕ Java/Maven
│   ├── NugetDialect.php               # 🔷 .NET/NuGet
│   └── GoModDialect.php               # 🐹 Go/Go modules
├── Parsers/                           # 🔍 Parsing logic
├── Comparators/                       # ⚖️ Version comparison
├── Evaluators/                        # 🎛️ Range evaluation
├── ValueObjects/                      # 📦 Immutable data objects
├── Exceptions/                        # 🚨 Error handling
└── Configuration/                     # ⚙️ Configuration management

tests/
├── Unit/                              # 🧪 Unit tests (Pest)
├── Integration/                       # 🔗 Integration tests  
└── Fixtures/                          # 📋 Test data
```

## 🤝 Contributing

We welcome contributions! Here's how to get started:

### 🚀 Quick Contribution Guide
1. **Fork** the repository
2. **Create** your feature branch (`git checkout -b feature/amazing-feature`)
3. **Write** tests for your changes
4. **Run** quality checks (`composer quality`)
5. **Commit** your changes (`git commit -m 'Add amazing feature'`)
6. **Push** to the branch (`git push origin feature/amazing-feature`)
7. **Open** a Pull Request

### 🎯 Contribution Areas
- **New dialects** for other package managers
- **Performance optimizations** 
- **Additional test cases** and edge case coverage
- **Documentation improvements**
- **Bug fixes** and issue resolution

### 📝 Coding Standards
- **PSR-12** code style (enforced by PHP-CS-Fixer)
- **PHPStan Level 6** compliance required
- **100% test coverage** for new features
- **SOLID principles** architecture
- **PHP 8.2+** modern features encouraged

### 🧪 Adding New Dialects
```php
// 1. Create dialect class implementing DialectInterface
final class NewDialect implements DialectInterface { /* ... */ }

// 2. Add comprehensive tests
describe('NewDialect', function () { /* ... */ });

// 3. Update README with dialect documentation
// 4. Add to compatibility matrix
```

## 📄 License

This project is licensed under the **MIT License** - see the [LICENSE](LICENSE) file for details.

```
MIT License

Copyright (c) 2024 Jean-Marc Strauven

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.
```

## 🏆 Credits & Acknowledgments

### 👨‍💻 Author
**Jean-Marc Strauven** - *Initial work and architecture*
- GitHub: [@Grazulex](https://github.com/Grazulex)
- Email: [your-email@example.com](mailto:your-email@example.com)

### 🎯 Inspiration & References
- **SemVer 2.0.0 Specification** - The foundation for semantic versioning
- **Composer** (getcomposer.org) - PHP dependency management
- **npm** - Node.js package manager version handling
- **PEP 440** - Python packaging version identification  
- **RubyGems** - Ruby package manager conventions
- **Maven** - Java project management and versioning
- **NuGet** - .NET package management
- **Go Modules** - Go dependency management system

### 🏗️ Architecture Principles
- **SOLID Principles** - Clean, maintainable object-oriented design
- **Domain-Driven Design** - Value objects and ubiquitous language
- **Immutable Objects** - Thread-safe, predictable behavior
- **Dependency Injection** - Testable, flexible architecture

### 🛠️ Tools & Technologies
- **PHP 8.2+** - Modern PHP with latest features
- **Pest** - Elegant PHP testing framework
- **PHPStan** - Static analysis for type safety
- **Infection** - Mutation testing for test quality
- **PHP-CS-Fixer** - Code style automation
- **GitHub Actions** - Continuous integration

---

<div align="center">

**🔍 PHP SemVer Sieve** - *Universal version range checking for the modern PHP ecosystem*

[![Stars](https://img.shields.io/github/stars/Grazulex/php-semver-sieve?style=social)](https://github.com/Grazulex/php-semver-sieve)
[![Forks](https://img.shields.io/github/forks/Grazulex/php-semver-sieve?style=social)](https://github.com/Grazulex/php-semver-sieve)
[![Issues](https://img.shields.io/github/issues/Grazulex/php-semver-sieve)](https://github.com/Grazulex/php-semver-sieve/issues)

Made with ❤️ for the PHP community

</div>
