<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('vendor')
    ->exclude('var')
    ->in(__DIR__)    
;

$config = $config = new PhpCsFixer\Config();

return $config->setRules([
        '@Symfony' => true,
    ])
    ->setFinder($finder)
;

