<?php

/**
 * @author Tomáš Kolinger <tomas@kolinger.name>
 */
abstract class BaseFacade extends NObject
{

	/**
	 * @var DibiConnection
	 */
	protected $connection;



	/**
	 * @param DibiConnection $connection
	 */
	public function __construct(DibiConnection $connection)
	{
		$this->connection = $connection;
	}

}