<?php

putenv('PHP_CS_FIXER_IGNORE_ENV=1');

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
    ->exclude('vendor')
    ->exclude('public/bundles')
    ->notPath([
        'config/bundles.php',
        'config/reference.php',
    ])
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        '@PHP84Migration' => true,
        'no_unused_imports' => true,
    ])
    ->setFinder($finder)
;
