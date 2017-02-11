#! /usr/bin/env php
<?php

use Redbox\RD\Application;

require './vendor/autoload.php';

$app = new Application('Redbox Docker');
$app->run();
