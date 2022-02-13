<?php

return (new PhpCsFixer\Config())
    ->setRules(
        [
            'linebreak_after_opening_tag' => true,
            'ordered_imports' => true,
            'no_superfluous_phpdoc_tags' => true,
            'phpdoc_trim' => true,
            'no_extra_blank_lines' => true,
        ]
    )
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->name('*.php')
            ->in(__DIR__.'/src')
            ->in(__DIR__.'/tests')
    );
