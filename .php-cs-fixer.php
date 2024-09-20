<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in(__DIR__)
    ->exclude(['.github', '.vscode', 'docker', 'vendor'])
    ->ignoreDotFiles(false);

$rules = [
    '@PSR1' => true,
    '@PSR2' => true,
    '@PSR12' => true,
    'ordered_imports' => [
        'sort_algorithm' => 'alpha',
        'imports_order' => [
            'class',
            'function',
            'const'
        ]
    ],
    'single_line_empty_body' => true,
    'array_indentation' => true,
    'array_syntax' => ['syntax' => 'short'],
    'no_whitespace_in_blank_line' => true,
    'whitespace_after_comma_in_array' => [
        'ensure_single_space' => true
    ],
    'no_multiline_whitespace_around_double_arrow' => true,
    'no_trailing_comma_in_singleline_array' => true,
    'normalize_index_brace' => true,
    'trim_array_spaces' => true,
    'single_class_element_per_statement' => [
        'elements' => [
            'const',
            'property'
        ]
    ],
    'visibility_required' => [
        'elements' => [
            'property',
            'method',
            'const'
        ]
    ],
    'align_multiline_comment' => true
];

$config = new Config();
$config->setFinder($finder);
$config->setRules($rules);
$config->setIndent('    ');
$config->setLineEnding("\n");

return $config;
