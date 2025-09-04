<?php

declare(strict_types=1);

use Grazulex\SemverSieve\Dialects\ComposerDialect;
use Grazulex\SemverSieve\Dialects\GenericSemverDialect;
use Grazulex\SemverSieve\Dialects\GoModDialect;
use Grazulex\SemverSieve\Dialects\MavenDialect;
use Grazulex\SemverSieve\Dialects\NpmDialect;
use Grazulex\SemverSieve\Dialects\NugetDialect;
use Grazulex\SemverSieve\Dialects\PypiDialect;
use Grazulex\SemverSieve\Dialects\RubyGemsDialect;
use Grazulex\SemverSieve\Sieve;

describe('All Dialects Implementation', function (): void {
    it('can instantiate all 8 dialects', function (): void {
        $dialects = [
            'generic' => new GenericSemverDialect(),
            'composer' => new ComposerDialect(),
            'npm' => new NpmDialect(),
            'pypi' => new PypiDialect(),
            'rubygems' => new RubyGemsDialect(),
            'nuget' => new NugetDialect(),
            'maven' => new MavenDialect(),
            'gomod' => new GoModDialect(),
        ];

        expect($dialects)->toHaveCount(8);

        foreach ($dialects as $dialect) {
            expect($dialect)->toBeInstanceOf(Grazulex\SemverSieve\Contracts\DialectInterface::class);
        }
    });

    it('can create Sieve with all dialects', function (): void {
        $dialects = [
            new GenericSemverDialect(),
            new ComposerDialect(),
            new NpmDialect(),
            new PypiDialect(),
            new RubyGemsDialect(),
            new NugetDialect(),
            new MavenDialect(),
            new GoModDialect(),
        ];

        foreach ($dialects as $dialect) {
            $sieve = new Sieve($dialect);
            expect($sieve)->toBeInstanceOf(Sieve::class);
        }
    });

    it('can parse basic versions with each dialect', function (): void {
        $tests = [
            [new GenericSemverDialect(), '1.2.3'],
            [new ComposerDialect(), '1.2.3'],
            [new NpmDialect(), '1.2.3'],
            [new PypiDialect(), '1.2.3'],
            [new RubyGemsDialect(), '1.2.3'],
            [new NugetDialect(), '1.2.3'], // Use 3-segment for now
            [new MavenDialect(), '1.2.3'],
            [new GoModDialect(), 'v1.2.3'],
        ];

        foreach ($tests as [$dialect, $version]) {
            $parsed = $dialect->parseVersion($version);
            expect($parsed->major)->toBeGreaterThanOrEqual(1);
        }
    });

    it('can parse basic ranges with each dialect', function (): void {
        $tests = [
            [new GenericSemverDialect(), '1.2.3'],
            [new ComposerDialect(), '1.2.3'],
            [new NpmDialect(), '1.2.3'],
            [new PypiDialect(), '1.2.3'],
            [new RubyGemsDialect(), '1.2.3'],
            [new NugetDialect(), '1.2.3'],
            [new MavenDialect(), '1.2.3'],
            [new GoModDialect(), 'v1.2.3'],
        ];

        foreach ($tests as [$dialect, $range]) {
            $parsed = $dialect->parseRange($range);
            expect($parsed->constraints)->not->toBeEmpty();
        }
    });
});
