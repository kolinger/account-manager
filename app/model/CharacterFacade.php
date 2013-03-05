<?php

/**
 * @author Tomáš Kolinger <tomas@kolinger.name>
 */
class CharacterFacade extends BaseFacade
{

	const CHARACTERS_FIELDS = '
		[guid],
		[name],
		[race],
		[class],
		[level],
		[online],
		[totaltime]
	';



	/**
	 * @param int $account
	 * @return DibiRow|FALSE
	 */
	public function findByAccount($account)
	{
		$query = $this->connection->query('SELECT ' . self::CHARACTERS_FIELDS . ' FROM [:chars:characters] WHERE [account] = %i', $account);
		return $query->fetchAll();
	}



	/**
	 * @param int $id
	 * @param int $account
	 * @return DibiRow|FALSE
	 */
	public function findOneByIdAndAccount($id, $account)
	{
		$query = $this->connection->query('SELECT ' . self::CHARACTERS_FIELDS . ' FROM [:chars:characters] WHERE [account] = %i', $account, 'AND [guid] = %i', $id);
		return $query->fetch();
	}

}