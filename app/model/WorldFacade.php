<?php

/**
 * @author Tomáš Kolinger <tomas@kolinger.name>
 */
class WorldFacade extends BaseFacade
{

	/**
	 * @param int $id
	 * @return string
	 */
	public function findItemNameById($id)
	{
		$query = $this->connection->query('SELECT [name] FROM [:world:item_template] WHERE [entry] = %i', $id);
		return $query->fetchSingle();
	}

}