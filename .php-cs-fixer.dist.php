<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->exclude([
        'var',
        'vendor',
        'node_modules',
    ])
    ->notName('*.phpt')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        '@PSR12' => true,

        // Common + safe modernizations
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'no_trailing_whitespace' => true,
        'trim_array_spaces' => true,

        // Symfony-like expectations
        'single_quote' => true,
        'phpdoc_align' => false,
        'phpdoc_separation' => false,
        'phpdoc_summary' => false,

        // Avoid risky rules for a template
        'native_function_invocation' => false,
        'native_constant_invocation' => false,
    ])
    ->setRiskyAllowed(false)
    ->setFinder($finder);
