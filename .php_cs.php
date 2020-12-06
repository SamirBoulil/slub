<?php

return PhpCsFixer\Config::create()
    ->setRules(
        [
            '@PSR2' => true,
            'linebreak_after_opening_tag' => true,
            'ordered_imports' => true,
            'no_unused_imports' => true,
            'no_superfluous_phpdoc_tags' => true,
            'phpdoc_trim' => true,
            'no_extra_blank_lines' => true,
            'method_argument_space' => [
                'ensure_fully_multiline' => false,
            ],
        ]
    )
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->name('*.php')
            ->in(__DIR__.'/src')
            ->in(__DIR__.'/tests')
    );
