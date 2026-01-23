<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/tests/Fixtures')
    ->in(__DIR__.'/tests/Functional')
    ->in(__DIR__.'/tests/Unit')
    ->name('*.php');

return (new Config())
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'declare_strict_types' => true,
        'native_function_invocation' => ['include' => ['@all']],
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => true],
        'phpdoc_align' => ['align' => 'left'],
        'php_unit_method_casing' => ['case' => 'camel_case'],
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder);
