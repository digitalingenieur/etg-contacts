#!/usr/bin/env php
<?php
// application.php

define('BASEDIR', __DIR__);
define ('K_TCPDF_EXTERNAL_CONFIG', true);
include('config/tcpdf_config.php');

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Console\Application;

$application = new Application();

// ... register commands
$application->add(new App\Command\CreateAddressBook());

$application->run();