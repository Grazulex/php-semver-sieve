<?php

declare(strict_types=1);

use Grazulex\SemverSieve\ValueObjects\ParsedVersion;

describe('ParsedVersion', function (): void {
    it('should create a valid version', function (): void {
        $version = new ParsedVersion(1, 2, 3, [], [], '1.2.3');

        expect($version->major)->toBe(1);
        expect($version->minor)->toBe(2);
        expect($version->patch)->toBe(3);
        expect($version->prerelease)->toBe([]);
        expect($version->build)->toBe([]);
        expect($version->raw)->toBe('1.2.3');
    });

    it('should handle prerelease versions', function (): void {
        $version = new ParsedVersion(1, 0, 0, ['alpha', '1'], [], '1.0.0-alpha.1');

        expect($version->isPrerelease())->toBeTrue();
        expect($version->prerelease)->toBe(['alpha', '1']);
    });

    it('should handle build metadata', function (): void {
        $version = new ParsedVersion(1, 0, 0, [], ['build', '123'], '1.0.0+build.123');

        expect($version->hasBuildMetadata())->toBeTrue();
        expect($version->build)->toBe(['build', '123']);
    });

    it('should generate normalized string', function (): void {
        $version = new ParsedVersion(1, 2, 3, ['alpha'], ['build'], '1.2.3-alpha+build');

        expect($version->toNormalizedString())->toBe('1.2.3-alpha');
    });

    it('should generate full string', function (): void {
        $version = new ParsedVersion(1, 2, 3, ['alpha'], ['build'], '1.2.3-alpha+build');

        expect($version->toFullString())->toBe('1.2.3-alpha+build');
    });

    it('should reject negative version numbers', function (): void {
        expect(fn () => new ParsedVersion(-1, 0, 0))
            ->toThrow(InvalidArgumentException::class, 'Version numbers cannot be negative');
    });

    it('should create new instance with modified prerelease', function (): void {
        $original = new ParsedVersion(1, 0, 0, [], [], '1.0.0');
        $modified = $original->withPrerelease(['beta']);

        expect($original->prerelease)->toBe([]);
        expect($modified->prerelease)->toBe(['beta']);
        expect($modified->major)->toBe(1);
        expect($modified->minor)->toBe(0);
        expect($modified->patch)->toBe(0);
    });

    it('should get core version array', function (): void {
        $version = new ParsedVersion(1, 2, 3, ['alpha'], ['build'], '1.2.3-alpha+build');

        expect($version->getCoreVersion())->toBe([1, 2, 3]);
    });
});
