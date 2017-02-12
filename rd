#! /usr/bin/env php
<?php

use Redbox\RD\Application;

require __DIR__ . '/vendor/autoload.php';

$app = new Application('Redbox Docker');
$app->run();
