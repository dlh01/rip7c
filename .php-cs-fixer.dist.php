<?php
/*
 * This file is part of dlh01/rip7c.
 *
 * (c) David Herrera <mail@dlh01.info>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()->in([
    __DIR__ . '/src/',
    __DIR__ . '/tests/',
]);

$config = new PhpCsFixer\Config();
$config->setRules([
    '@PSR12' => true,
    '@PHP81Migration' => true,

    'final_class' => true,
    'native_constant_invocation' => true,
    'native_function_casing' => true,
    'native_function_invocation' => true,
    'native_function_type_declaration_casing' => true,
]);
$config->setFinder($finder);

return $config;
