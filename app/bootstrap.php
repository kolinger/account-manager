<?php

require dirname(__FILE__) . '/../libs/autoload.php';
$configurator = new NConfigurator;
//$configurator->setDebugMode(TRUE);
$configurator->enableDebugger(dirname(__FILE__) . '/../log');

$configurator->setTempDirectory(dirname(__FILE__) . '/../temp');
$configurator->createRobotLoader()
	->addDirectory(dirname(__FILE__))
	->addDirectory(dirname(__FILE__) . '/../libs')
	->register();

$configurator->addConfig(dirname(__FILE__) . '/config/config.neon');
$configurator->addConfig(dirname(__FILE__) . '/config/config.local.neon', NConfigurator::NONE);

$configurator->onCompile[] = callback('registerDibiExtension');

function registerDibiExtension($configurator, $compiler) {
	$compiler->addExtension('dibi', new AppDibiNetteExtension());
}

$container = $configurator->createContainer();
return $container;
