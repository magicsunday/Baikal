<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\StaticCall\RemoveParentCallWithoutParentRector;
use Rector\Php73\Rector\ConstFetch\SensitiveConstantNameRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php80\Rector\FunctionLike\MixedTypeRector;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/Core',
        __DIR__ . '/html',
    ]);

    $rectorConfig->skip([
        'generate-sprite.php',
    ]);

    $rectorConfig->phpstanConfig('phpstan.neon');
    $rectorConfig->importNames();
    $rectorConfig->removeUnusedImports();
    $rectorConfig->disableParallel();

    // Define what rule sets will be applied
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_82,
    ]);

    // Skip some rules
    $rectorConfig->skip([
        ClassPropertyAssignToConstructorPromotionRector::class,
        MixedTypeRector::class,
        SensitiveConstantNameRector::class,
        RemoveParentCallWithoutParentRector::class,
    ]);
};
