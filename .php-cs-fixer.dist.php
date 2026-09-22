<?php

declare(strict_types=1);

$finder = (new PhpCsFixer\Finder())
    ->in([__DIR__.'/src', __DIR__.'/tests', __DIR__.'/packages/skautis-symfony/src', __DIR__.'/packages/skautis-symfony/tests'])
;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@PHP84Migration' => true,
        'declare_strict_types' => true,
        'global_namespace_import' => ['import_classes' => false, 'import_functions' => false],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'php_unit_method_casing' => false,
    ])
    ->setFinder($finder)
;
