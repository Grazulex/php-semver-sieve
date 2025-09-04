<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;
use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    // PHP version
    $rectorConfig->phpVersion(\Rector\ValueObject\PhpVersion::PHP_81);

    // Core sets
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_81,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::TYPE_DECLARATION,
        SetList::PRIVATIZATION,
        SetList::INSTANCEOF,
    ]);

    // Additional rules
    $rectorConfig->rules([
        AddVoidReturnTypeWhereNoReturnRector::class,
        TypedPropertyFromStrictConstructorRector::class,
        InlineConstructorDefaultToPropertyRector::class,
        ReadOnlyPropertyRector::class,
    ]);

    // Skip rules
    $rectorConfig->skip([
        // Skip test files for some transformations
        __DIR__ . '/tests' => [
            \Rector\Privatization\Rector\Class_\FinalizeClassesWithoutChildrenRector::class,
        ],
    ]);

    // Parallel processing
    $rectorConfig->parallel();
};
