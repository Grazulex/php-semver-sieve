<?php

declare(strict_types=1);

use Grazulex\SemverSieve\Dialects\GenericSemverDialect;
use Grazulex\SemverSieve\Sieve;

test('basic sieve functionality works', function (): void {
    $sieve = new Sieve(new GenericSemverDialect());
    expect($sieve)->toBeInstanceOf(Sieve::class);
});

test('exact version matching works', function (): void {
    $sieve = new Sieve(new GenericSemverDialect());
    $result = $sieve->includes('1.2.3', ['1.2.3']);
    expect($result)->toBeTrue();
});
