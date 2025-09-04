<?php

declare(strict_types=1);

namespace Grazulex\SemverSieve\Exceptions;

/**
 * Exception thrown when a version string cannot be parsed.
 *
 * This includes malformed versions, unsupported formats,
 * or versions that violate SemVer specifications.
 */
final class InvalidVersionException extends SemverSieveException
{
    /**
     * Create an exception for an invalid version string.
     */
    public static function forVersion(string $version, string $reason = ''): self
    {
        $message = "Invalid version string: '{$version}'";

        if ($reason !== '') {
            $message .= ". {$reason}";
        }

        return new self($message, ['version' => $version, 'reason' => $reason]);
    }

    /**
     * Create an exception for an empty version string.
     */
    public static function empty(): self
    {
        return new self('Version string cannot be empty', ['version' => '']);
    }

    /**
     * Create an exception for a version that's too long.
     */
    public static function tooLong(string $version, int $maxLength): self
    {
        return new self(
            "Version string is too long ({strlen({$version})} characters, max {$maxLength})",
            ['version' => $version, 'length' => strlen($version), 'maxLength' => $maxLength],
        );
    }

    /**
     * Create an exception for invalid version segments.
     */
    public static function invalidSegments(string $version, string $segment): self
    {
        return new self(
            "Invalid version segment '{$segment}' in version '{$version}'",
            ['version' => $version, 'segment' => $segment],
        );
    }

    /**
     * Create an exception for leading zeros in version numbers.
     */
    public static function leadingZeros(string $version, string $segment): self
    {
        return new self(
            "Version segment '{$segment}' has leading zeros in version '{$version}'",
            ['version' => $version, 'segment' => $segment],
        );
    }

    /**
     * Create an exception for negative version numbers.
     */
    public static function negativeNumber(string $version, string $segment): self
    {
        return new self(
            "Version segment '{$segment}' cannot be negative in version '{$version}'",
            ['version' => $version, 'segment' => $segment],
        );
    }

    /**
     * Create an exception for invalid prerelease identifiers.
     */
    public static function invalidPrerelease(string $version, string $prerelease): self
    {
        return new self(
            "Invalid prerelease identifier '{$prerelease}' in version '{$version}'",
            ['version' => $version, 'prerelease' => $prerelease],
        );
    }

    /**
     * Create an exception for invalid build metadata.
     */
    public static function invalidBuild(string $version, string $build): self
    {
        return new self(
            "Invalid build metadata '{$build}' in version '{$version}'",
            ['version' => $version, 'build' => $build],
        );
    }
}
