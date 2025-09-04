<?php

declare(strict_types=1);

use Grazulex\SemverSieve\Comparators\SemverComparator;
use Grazulex\SemverSieve\ValueObjects\ParsedVersion;

beforeEach(function (): void {
    $this->comparator = new SemverComparator();
});

describe('SemverComparator', function (): void {
    describe('compare method', function (): void {
        it('should compare major versions', function (): void {
            $v1 = new ParsedVersion(1, 0, 0);
            $v2 = new ParsedVersion(2, 0, 0);

            expect($this->comparator->compare($v1, $v2))->toBe(-1);
            expect($this->comparator->compare($v2, $v1))->toBe(1);
        });

        it('should compare minor versions when major is equal', function (): void {
            $v1 = new ParsedVersion(1, 1, 0);
            $v2 = new ParsedVersion(1, 2, 0);

            expect($this->comparator->compare($v1, $v2))->toBe(-1);
            expect($this->comparator->compare($v2, $v1))->toBe(1);
        });

        it('should compare patch versions when major and minor are equal', function (): void {
            $v1 = new ParsedVersion(1, 0, 1);
            $v2 = new ParsedVersion(1, 0, 2);

            expect($this->comparator->compare($v1, $v2))->toBe(-1);
            expect($this->comparator->compare($v2, $v1))->toBe(1);
        });

        it('should consider equal versions', function (): void {
            $v1 = new ParsedVersion(1, 2, 3);
            $v2 = new ParsedVersion(1, 2, 3);

            expect($this->comparator->compare($v1, $v2))->toBe(0);
        });

        it('should handle prerelease precedence', function (): void {
            $stable = new ParsedVersion(1, 0, 0);
            $prerelease = new ParsedVersion(1, 0, 0, ['alpha']);

            expect($this->comparator->compare($prerelease, $stable))->toBe(-1);
            expect($this->comparator->compare($stable, $prerelease))->toBe(1);
        });

        it('should compare prerelease identifiers', function (): void {
            $alpha = new ParsedVersion(1, 0, 0, ['alpha']);
            $beta = new ParsedVersion(1, 0, 0, ['beta']);

            expect($this->comparator->compare($alpha, $beta))->toBe(-1);
        });

        it('should compare numeric prerelease identifiers', function (): void {
            $alpha1 = new ParsedVersion(1, 0, 0, ['alpha', '1']);
            $alpha2 = new ParsedVersion(1, 0, 0, ['alpha', '2']);

            expect($this->comparator->compare($alpha1, $alpha2))->toBe(-1);
        });

        it('should prioritize numeric over non-numeric identifiers', function (): void {
            $numeric = new ParsedVersion(1, 0, 0, ['alpha', '1']);
            $text = new ParsedVersion(1, 0, 0, ['alpha', 'beta']);

            expect($this->comparator->compare($numeric, $text))->toBe(-1);
        });

        it('should handle different prerelease length', function (): void {
            $short = new ParsedVersion(1, 0, 0, ['alpha']);
            $long = new ParsedVersion(1, 0, 0, ['alpha', '1']);

            expect($this->comparator->compare($short, $long))->toBe(-1);
        });
    });

    describe('convenience methods', function (): void {
        it('should check if version is greater than', function (): void {
            $v1 = new ParsedVersion(1, 0, 0);
            $v2 = new ParsedVersion(2, 0, 0);

            expect($this->comparator->greaterThan($v2, $v1))->toBeTrue();
            expect($this->comparator->greaterThan($v1, $v2))->toBeFalse();
        });

        it('should check if version is greater than or equal', function (): void {
            $v1 = new ParsedVersion(1, 0, 0);
            $v2 = new ParsedVersion(1, 0, 0);
            $v3 = new ParsedVersion(2, 0, 0);

            expect($this->comparator->greaterThanOrEqual($v2, $v1))->toBeTrue();
            expect($this->comparator->greaterThanOrEqual($v3, $v1))->toBeTrue();
            expect($this->comparator->greaterThanOrEqual($v1, $v3))->toBeFalse();
        });

        it('should check if version is less than', function (): void {
            $v1 = new ParsedVersion(1, 0, 0);
            $v2 = new ParsedVersion(2, 0, 0);

            expect($this->comparator->lessThan($v1, $v2))->toBeTrue();
            expect($this->comparator->lessThan($v2, $v1))->toBeFalse();
        });

        it('should check if version is less than or equal', function (): void {
            $v1 = new ParsedVersion(1, 0, 0);
            $v2 = new ParsedVersion(1, 0, 0);
            $v3 = new ParsedVersion(2, 0, 0);

            expect($this->comparator->lessThanOrEqual($v1, $v2))->toBeTrue();
            expect($this->comparator->lessThanOrEqual($v1, $v3))->toBeTrue();
            expect($this->comparator->lessThanOrEqual($v3, $v1))->toBeFalse();
        });

        it('should check if versions are equal', function (): void {
            $v1 = new ParsedVersion(1, 2, 3);
            $v2 = new ParsedVersion(1, 2, 3);
            $v3 = new ParsedVersion(1, 2, 4);

            expect($this->comparator->equal($v1, $v2))->toBeTrue();
            expect($this->comparator->equal($v1, $v3))->toBeFalse();
        });
    });
});
