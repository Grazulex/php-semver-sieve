<?php

declare(strict_types=1);

namespace Grazulex\SemverSieve\Exceptions;

/**
 * Exception thrown when configuration is invalid.
 *
 * This includes invalid options, unsupported settings,
 * or configuration that would lead to inconsistent behavior.
 */
final class ConfigurationException extends SemverSieveException
{
    /**
     * Create an exception for an invalid configuration option.
     */
    public static function invalidOption(string $option, mixed $value, string $reason = ''): self
    {
        $message = "Invalid configuration option '{$option}' with value '" . json_encode($value) . "'";

        if ($reason !== '') {
            $message .= ". {$reason}";
        }

        return new self($message, ['option' => $option, 'value' => $value, 'reason' => $reason]);
    }

    /**
     * Create an exception for missing required configuration.
     */
    public static function missingOption(string $option): self
    {
        return new self(
            "Missing required configuration option: '{$option}'",
            ['option' => $option],
        );
    }

    /**
     * Create an exception for unsupported configuration combination.
     *
     * @param array<string, mixed> $options
     */
    public static function unsupportedCombination(array $options): self
    {
        return new self(
            'Unsupported configuration combination: ' . implode(', ', array_keys($options)),
            ['options' => $options],
        );
    }

    /**
     * Create an exception for invalid dialect configuration.
     */
    public static function invalidDialect(string $dialect, string $reason = ''): self
    {
        $message = "Invalid dialect configuration: '{$dialect}'";

        if ($reason !== '') {
            $message .= ". {$reason}";
        }

        return new self($message, ['dialect' => $dialect, 'reason' => $reason]);
    }

    /**
     * Create an exception for configuration values that are out of range.
     */
    public static function outOfRange(string $option, mixed $value, mixed $min, mixed $max): self
    {
        return new self(
            "Configuration option '{$option}' value {$value} is out of range [{$min}, {$max}]",
            ['option' => $option, 'value' => $value, 'min' => $min, 'max' => $max],
        );
    }
}
