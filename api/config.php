<?php

$dir = __DIR__ . '/../src';

$iterator = Symfony\Component\Finder\Finder::create()
    ->files()
    ->name('*.php')
    ->exclude('build')
    ->exclude('tests')
    ->in($dir);

$options = [
    'theme'                => 'default',
    'title'                => 'Wealthbot.io API Documentation',
    'build_dir'            => __DIR__ . '/../api/build/webo',
    'cache_dir'            => __DIR__ . '/../api/cache/webo',
];

$sami = new Sami\Sami($iterator, $options);

return $sami;
