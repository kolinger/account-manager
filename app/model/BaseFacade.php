<?php

/**
 * @author Tomáš Kolinger <tomas@kolinger.name>
 */
abstract class BaseFacade extends NObject
{

	const TRINITY_CORE = 'tc';
	const OREGON_CORE = 'oregon';
	const SKYFIRE = 'skyfire';

	/**
	 * @var string
	 */
	private $emulator;

	/**
	 * @var DibiConnection
	 */
	protected $connection;



	/**
	 * @param DibiConnection $connection
	 * @param NDIContainer $container
	 */
	public function __construct(DibiConnection $connection, NDIContainer $container)
	{
		if ($container->parameters['emulator'] == self::OREGON_CORE) {
			$this->emulator = self::OREGON_CORE;
		} else if ($container->parameters['emulator'] == self::SKYFIRE) {
			$this->emulator = self::SKYFIRE;
		} else {
			$this->emulator = self::TRINITY_CORE;
		}
		$this->connection = $connection;
	}



	/**
	 * @return string
	 */
	public function getEmulator()
	{
		return $this->emulator;
	}

}