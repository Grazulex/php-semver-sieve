<?php

declare(strict_types=1);

namespace Grazulex\SemverSieve\Contracts;

/**
 * Interface for parsing operations.
 *
 * Provides a contract for parsing various input types into structured data.
 */
interface ParserInterface
{
    /**
     * Parse input string into structured data.
     *
     * @param string $input The input string to parse
     * @param array<string, mixed> $options Configuration options
     *
     * @throws \Grazulex\SemverSieve\Exceptions\SemverSieveException
     *
     * @return mixed The parsed result
     */
    public function parse(string $input, array $options = []): mixed;

    /**
     * Validate input without full parsing.
     */
    public function validate(string $input): bool;

    /**
     * Get supported patterns for this parser.
     *
     * @return array<string>
     */
    public function getSupportedPatterns(): array;
}
