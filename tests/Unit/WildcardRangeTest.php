<?php

declare(strict_types=1);

use Grazulex\SemverSieve\Dialects\GenericSemverDialect;
use Grazulex\SemverSieve\Sieve;

beforeEach(function (): void {
    $this->sieve = new Sieve(new GenericSemverDialect());
});

describe('Wildcard Range Support', function (): void {
    describe('patch-level wildcards (X.Y.x format)', function (): void {
        it('should match versions within 1.2.x range', function (): void {
            expect($this->sieve->includes('1.2.0', ['1.2.x']))->toBeTrue();
            expect($this->sieve->includes('1.2.5', ['1.2.x']))->toBeTrue();
            expect($this->sieve->includes('1.2.99', ['1.2.x']))->toBeTrue();
            expect($this->sieve->includes('1.3.0', ['1.2.x']))->toBeFalse();
            expect($this->sieve->includes('1.1.9', ['1.2.x']))->toBeFalse();
            expect($this->sieve->includes('2.2.0', ['1.2.x']))->toBeFalse();
        });

        it('should match versions within 1.3.x range', function (): void {
            expect($this->sieve->includes('1.3.0', ['1.3.x']))->toBeTrue();
            expect($this->sieve->includes('1.3.5', ['1.3.x']))->toBeTrue();
            expect($this->sieve->includes('1.3.99', ['1.3.x']))->toBeTrue();
            expect($this->sieve->includes('1.4.0', ['1.3.x']))->toBeFalse();
            expect($this->sieve->includes('1.2.9', ['1.3.x']))->toBeFalse();
        });

        it('should match versions within 2.0.x range', function (): void {
            expect($this->sieve->includes('2.0.0', ['2.0.x']))->toBeTrue();
            expect($this->sieve->includes('2.0.5', ['2.0.x']))->toBeTrue();
            expect($this->sieve->includes('2.0.99', ['2.0.x']))->toBeTrue();
            expect($this->sieve->includes('2.1.0', ['2.0.x']))->toBeFalse();
            expect($this->sieve->includes('1.9.9', ['2.0.x']))->toBeFalse();
        });

        it('should match versions within 0.1.x range', function (): void {
            expect($this->sieve->includes('0.1.0', ['0.1.x']))->toBeTrue();
            expect($this->sieve->includes('0.1.5', ['0.1.x']))->toBeTrue();
            expect($this->sieve->includes('0.2.0', ['0.1.x']))->toBeFalse();
        });

        it('should match versions within 10.20.x range', function (): void {
            expect($this->sieve->includes('10.20.0', ['10.20.x']))->toBeTrue();
            expect($this->sieve->includes('10.20.99', ['10.20.x']))->toBeTrue();
            expect($this->sieve->includes('10.21.0', ['10.20.x']))->toBeFalse();
        });
    });

    describe('patch-level wildcards (X.Y.* format)', function (): void {
        it('should match versions within 1.2.* range', function (): void {
            expect($this->sieve->includes('1.2.0', ['1.2.*']))->toBeTrue();
            expect($this->sieve->includes('1.2.5', ['1.2.*']))->toBeTrue();
            expect($this->sieve->includes('1.2.99', ['1.2.*']))->toBeTrue();
            expect($this->sieve->includes('1.3.0', ['1.2.*']))->toBeFalse();
            expect($this->sieve->includes('1.1.9', ['1.2.*']))->toBeFalse();
        });

        it('should match versions within 1.3.* range', function (): void {
            expect($this->sieve->includes('1.3.0', ['1.3.*']))->toBeTrue();
            expect($this->sieve->includes('1.3.5', ['1.3.*']))->toBeTrue();
            expect($this->sieve->includes('1.4.0', ['1.3.*']))->toBeFalse();
        });

        it('should match versions within 5.10.* range', function (): void {
            expect($this->sieve->includes('5.10.0', ['5.10.*']))->toBeTrue();
            expect($this->sieve->includes('5.10.123', ['5.10.*']))->toBeTrue();
            expect($this->sieve->includes('5.11.0', ['5.10.*']))->toBeFalse();
        });
    });

    describe('minor-level wildcards (X.x format)', function (): void {
        it('should match versions within 1.x range', function (): void {
            expect($this->sieve->includes('1.0.0', ['1.x']))->toBeTrue();
            expect($this->sieve->includes('1.5.0', ['1.x']))->toBeTrue();
            expect($this->sieve->includes('1.99.99', ['1.x']))->toBeTrue();
            expect($this->sieve->includes('2.0.0', ['1.x']))->toBeFalse();
            expect($this->sieve->includes('0.9.9', ['1.x']))->toBeFalse();
        });

        it('should match versions within 2.x range', function (): void {
            expect($this->sieve->includes('2.0.0', ['2.x']))->toBeTrue();
            expect($this->sieve->includes('2.10.5', ['2.x']))->toBeTrue();
            expect($this->sieve->includes('3.0.0', ['2.x']))->toBeFalse();
        });
    });

    describe('minor-level wildcards (X.* format)', function (): void {
        it('should match versions within 1.* range', function (): void {
            expect($this->sieve->includes('1.0.0', ['1.*']))->toBeTrue();
            expect($this->sieve->includes('1.5.0', ['1.*']))->toBeTrue();
            expect($this->sieve->includes('1.99.99', ['1.*']))->toBeTrue();
            expect($this->sieve->includes('2.0.0', ['1.*']))->toBeFalse();
        });
    });

    describe('global wildcard', function (): void {
        it('should match any version with * wildcard', function (): void {
            expect($this->sieve->includes('0.0.1', ['*']))->toBeTrue();
            expect($this->sieve->includes('1.2.3', ['*']))->toBeTrue();
            expect($this->sieve->includes('99.99.99', ['*']))->toBeTrue();
        });
    });

    describe('wildcard ranges with prerelease versions', function (): void {
        it('should handle prereleases correctly with wildcards', function (): void {
            // By default, prereleases are excluded
            expect($this->sieve->includes('1.2.0-alpha', ['1.2.x']))->toBeFalse();
            expect($this->sieve->includes('1.2.0-beta', ['1.2.x']))->toBeFalse();
        });

        it('should include prereleases when configured leniently', function (): void {
            $sieve = new Sieve(
                new GenericSemverDialect(),
                \Grazulex\SemverSieve\Configuration\SieveConfiguration::lenient(),
            );

            // Prereleases 1.2.0-alpha is technically >= 1.2.0-0 (the lower bound)
            // but standard SemVer behavior excludes them unless explicitly targeted
            // This is expected behavior - wildcard ranges don't automatically include prereleases
            expect($sieve->includes('1.2.1-alpha', ['1.2.x']))->toBeTrue();
            expect($sieve->includes('1.2.5-beta', ['1.2.x']))->toBeTrue();
            expect($sieve->includes('1.3.0-alpha', ['1.2.x']))->toBeFalse();
        });
    });

    describe('wildcard range parsing', function (): void {
        it('should parse patch-level wildcard ranges correctly', function (): void {
            $range = $this->sieve->parseRange('1.2.x');
            expect($range->hasConstraints())->toBeTrue();
            expect($range->getConstraints())->toHaveCount(2);
        });

        it('should parse minor-level wildcard ranges correctly', function (): void {
            $range = $this->sieve->parseRange('1.x');
            expect($range->hasConstraints())->toBeTrue();
            expect($range->getConstraints())->toHaveCount(2);
        });

        it('should parse global wildcard correctly', function (): void {
            $range = $this->sieve->parseRange('*');
            // Global wildcard (*) has no constraints as it matches everything
            expect($range->hasConstraints())->toBeFalse();
            expect($range)->toBeInstanceOf(\Grazulex\SemverSieve\ValueObjects\ParsedRange::class);
        });
    });

    describe('combined wildcard and other range types', function (): void {
        it('should work with wildcard in OR expressions', function (): void {
            expect($this->sieve->includes('1.2.5', ['1.2.x || 2.0.x']))->toBeTrue();
            expect($this->sieve->includes('2.0.5', ['1.2.x || 2.0.x']))->toBeTrue();
            expect($this->sieve->includes('1.3.0', ['1.2.x || 2.0.x']))->toBeFalse();
        });

        it('should work with wildcard in complex expressions', function (): void {
            expect($this->sieve->includes('1.2.5', ['^1.0', '1.2.x']))->toBeTrue();
            expect($this->sieve->includes('1.5.0', ['^1.0', '1.2.x']))->toBeTrue();
        });
    });
});
