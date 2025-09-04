<?php

declare(strict_types=1);

use Grazulex\SemverSieve\Dialects\GenericSemverDialect;
use Grazulex\SemverSieve\Sieve;

beforeEach(function (): void {
    $this->sieve = new Sieve(new GenericSemverDialect());
});

describe('Sieve Integration', function (): void {
    describe('basic version checking', function (): void {
        it('should match exact versions', function (): void {
            expect($this->sieve->includes('1.2.3', ['1.2.3']))->toBeTrue();
            expect($this->sieve->includes('1.2.3', ['1.2.4']))->toBeFalse();
        });

        it('should handle comparison operators', function (): void {
            expect($this->sieve->includes('1.2.3', ['>=1.0.0']))->toBeTrue();
            expect($this->sieve->includes('1.2.3', ['<2.0.0']))->toBeTrue();
            expect($this->sieve->includes('1.2.3', ['>1.2.3']))->toBeFalse();
            expect($this->sieve->includes('1.2.3', ['<=1.2.3']))->toBeTrue();
        });

        it('should handle caret ranges', function (): void {
            expect($this->sieve->includes('1.2.4', ['^1.2.3']))->toBeTrue();
            expect($this->sieve->includes('1.3.0', ['^1.2.3']))->toBeTrue();
            expect($this->sieve->includes('2.0.0', ['^1.2.3']))->toBeFalse();
            expect($this->sieve->includes('1.2.2', ['^1.2.3']))->toBeFalse();
        });

        it('should handle tilde ranges', function (): void {
            expect($this->sieve->includes('1.2.4', ['~1.2.3']))->toBeTrue();
            expect($this->sieve->includes('1.3.0', ['~1.2.3']))->toBeFalse();
            expect($this->sieve->includes('1.2.2', ['~1.2.3']))->toBeFalse();
        });

        it('should handle multiple ranges with OR logic', function (): void {
            expect($this->sieve->includes('1.0.0', ['^1.0 || ^2.0']))->toBeTrue();
            expect($this->sieve->includes('2.0.0', ['^1.0 || ^2.0']))->toBeTrue();
            expect($this->sieve->includes('3.0.0', ['^1.0 || ^2.0']))->toBeFalse();
        });

        it('should handle compound ranges with AND logic', function (): void {
            expect($this->sieve->includes('1.5.0', ['>=1.0.0 <2.0.0']))->toBeTrue();
            expect($this->sieve->includes('2.0.0', ['>=1.0.0 <2.0.0']))->toBeFalse();
            expect($this->sieve->includes('0.9.0', ['>=1.0.0 <2.0.0']))->toBeFalse();
        });
    });

    describe('detailed matching', function (): void {
        it('should return detailed match information', function (): void {
            $result = $this->sieve->match('1.2.3', ['^1.0', '>=1.2.0 <1.3.0']);

            expect($result['matched'])->toBeTrue();
            expect($result['matched_ranges'])->toContain('^1.0');
            expect($result['matched_ranges'])->toContain('>=1.2.0 <1.3.0');
            expect($result['normalized_ranges'])->toHaveCount(2);
        });

        it('should return empty matches when no ranges match', function (): void {
            $result = $this->sieve->match('2.0.0', ['^1.0', '~1.5']);

            expect($result['matched'])->toBeFalse();
            expect($result['matched_ranges'])->toBe([]);
            expect($result['normalized_ranges'])->toHaveCount(2);
        });
    });

    describe('prerelease handling', function (): void {
        it('should exclude prereleases by default', function (): void {
            expect($this->sieve->includes('1.0.0-alpha', ['>=1.0.0']))->toBeFalse();
        });

        it('should include prereleases when explicitly allowed', function (): void {
            $sieve = new Sieve(
                new GenericSemverDialect(),
                \Grazulex\SemverSieve\Configuration\SieveConfiguration::lenient(),
            );

            // Prereleases should satisfy ranges when they meet the criteria
            expect($sieve->includes('1.0.0-alpha', ['>=0.9.0']))->toBeTrue();
            expect($sieve->includes('1.0.0-beta', ['<1.1.0']))->toBeTrue();
        });
    });

    describe('version parsing', function (): void {
        it('should parse valid versions', function (): void {
            $version = $this->sieve->parseVersion('1.2.3-alpha+build');

            expect($version->major)->toBe(1);
            expect($version->minor)->toBe(2);
            expect($version->patch)->toBe(3);
            expect($version->prerelease)->toBe(['alpha']);
            expect($version->build)->toBe(['build']);
        });

        it('should throw exception for invalid versions', function (): void {
            expect(fn () => $this->sieve->parseVersion('invalid'))
                ->toThrow(\Grazulex\SemverSieve\Exceptions\InvalidVersionException::class);
        });
    });

    describe('range parsing', function (): void {
        it('should parse valid ranges', function (): void {
            $range = $this->sieve->parseRange('^1.2.3');

            expect($range->hasConstraints())->toBeTrue();
            expect($range->getConstraints())->toHaveCount(2); // >= and <
        });

        it('should throw exception for invalid ranges', function (): void {
            expect(fn () => $this->sieve->parseRange(''))
                ->toThrow(\Grazulex\SemverSieve\Exceptions\InvalidRangeException::class);
        });
    });
});
