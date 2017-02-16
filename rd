#! /usr/bin/env php
<?php

use Redbox\RD\Application;

function includeIfExists($file)
{
    return file_exists($file) ? include $file : false;
}

if ((!$loader = includeIfExists(__DIR__.'/vendor/autoload.php')) && (!$loader = includeIfExists(__DIR__.'/../../composer/autoload.php'))) {
    echo 'You must set up the project dependencies using `composer install`'.PHP_EOL.
    exit(1);
}

$app = new Application('Redbox Docker');
$app->run();
