<?php

require_once $_SERVER['SYMFONY2_DIR'] . '/Symfony/Foundation/UniversalClassLoader.php';

use Symfony\Foundation\UniversalClassLoader;

$loader = new UniversalClassLoader();
$loader->registerNamespaces(array(
    'Symfony'       => $_SERVER['SYMFONY2_DIR'],
    'Bundle'        => realpath(__DIR__ . '/../../..'),
    'Doctrine\\ODM' => $_SERVER['DOCTRINE2_ODM_DIR'],
    'Doctrine'      => $_SERVER['DOCTRINE2_DIR']
));

$loader->register();

