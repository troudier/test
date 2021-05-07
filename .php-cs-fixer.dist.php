<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('vendor')
    ->exclude('var')
    ->in(__DIR__)    
;

$config = PhpCsFixer\Config::create();

return $config->setRules([
        '@Symfony' => true,
    ])
    ->setFinder($finder)
;

