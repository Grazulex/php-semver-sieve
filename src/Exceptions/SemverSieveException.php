<?php

declare(strict_types=1);

namespace Grazulex\SemverSieve\Exceptions;

use Exception;
use Throwable;

/**
 * Base exception for all SemVer Sieve related errors.
 *
 * All package-specific exceptions should extend this class
 * to allow easy catching of any package-related errors.
 */
abstract class SemverSieveException extends Exception
{
    /**
     * Create a new exception with context information.
     *
     * @param string $message Error message
     * @param array<string, mixed> $context Additional context data
     * @param int $code Error code
     * @param Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message = '',
        private readonly array $context = [],
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the context data associated with this exception.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get a specific context value.
     */
    public function getContextValue(string $key): mixed
    {
        return $this->context[$key] ?? null;
    }

    /**
     * Get a formatted error message with context.
     */
    public function getDetailedMessage(): string
    {
        $message = $this->getMessage();

        if (!empty($this->context)) {
            $contextString = $this->formatContext();
            $message .= " Context: {$contextString}";
        }

        return $message;
    }

    /**
     * Format context data for display.
     */
    private function formatContext(): string
    {
        $parts = [];

        foreach ($this->context as $key => $value) {
            $formattedValue = is_string($value) ? $value : json_encode($value);
            $parts[] = "{$key}={$formattedValue}";
        }

        return implode(', ', $parts);
    }
}
