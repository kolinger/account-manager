<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 *
 * Copyright (c) 2005 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */



/**
 * Dibi extension for Nette Framework. Creates 'connection' service.
 *
 * @author     David Grudl
 * @package    dibi\nette
 * @phpversion 5.3
 */
class FixedDibiNetteExtension extends NConfigCompilerExtension
{

	/**
	 * @var array
	 */
	private $defaults = array(
		'sqlite' => array(
			'driver'   => 'pdo',
			'database' => '%appDir%/model/database.db',
			'profiler' => TRUE,
		),
	);



	public function loadConfiguration()
	{
		$container = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);

		$sqliteConfig = $config['sqlite'];
		unset($config['sqlite']);

		$useProfiler = isset($config['profiler'])
			? $config['profiler']
			: !$container->parameters['productionMode'];

		unset($config['profiler']);

		if (isset($config['flags'])) {
			$flags = 0;
			foreach ((array) $config['flags'] as $flag) {
				$flags |= constant($flag);
			}
			$config['flags'] = $flags;
		}

		$connection = $container->addDefinition($this->prefix('connection'))
			->setClass('DibiConnection', array($config));

		$sqlite = $container->addDefinition($this->prefix('sqlite'))
			->setClass('DibiConnection')
			->setFactory(get_called_class() . '::createSqliteConnection', array($sqliteConfig));

		if ($useProfiler) {
			$panel = $container->addDefinition($this->prefix('panel'))
				->setClass('DibiNettePanel')
				->addSetup('NDebugger::$bar->addPanel(?)', array('@self'))
				->addSetup('NDebugger::$blueScreen->addPanel(?)', array(array('@self', 'renderException')));

			$connection->addSetup('$service->onEvent[] = ?', array(array($panel, 'logEvent')));
			$sqlite->addSetup('$service->onEvent[] = ?', array(array($panel, 'logEvent')));
		}
	}



	/**
	 * @param array $config
	 * @return DibiConnection
	 */
	public static function createSqliteConnection(array $config)
	{
		$config['pdo'] = new PDO('sqlite:' . $config['database']);
		return new DibiConnection($config);
	}

}
