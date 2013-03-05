<?php

/**
 * @author Tomáš Kolinger <tomas@kolinger.name>
 */
class TokenFacade extends BaseFacade
{

	/**
	 * @param int $user
	 * @param string $data
	 * @return int
	 */
	public function create($user, $data)
	{
		$data = array(
			'id' => NStrings::random(30),
			'data' => $data,
			'user' => $user,
		);
		$this->connection->query('INSERT INTO [token]', $data);
		return $data['id'];
	}



	/**
	 * @param int $id
	 * @return DibiRow|FALSE
	 */
	public function findOneById($id)
	{
		$query = $this->connection->query('SELECT [id], [user], [data] FROM [token] WHERE [id] = %s', $id);
		return $query->fetch();
	}



	/**
	 * @param int $id
	 */
	public function delete($id)
	{
		$this->connection->query('DELETE FROM [token] WHERE [id] = %s', $id);
	}

}