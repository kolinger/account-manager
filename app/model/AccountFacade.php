<?php

/**
 * @author Tomáš Kolinger <tomas@kolinger.name>
 */
class AccountFacade extends BaseFacade
{

	/**
	 * @param $id
	 * @return DibiRow|FALSE
	 */
	public function findOneById($id)
	{
		$query = $this->connection->query('SELECT * FROM [:auth:account] WHERE [id] = %i', $id);
		return $query->fetch();
	}



	/**
	 * @param string $username
	 * @return DibiRow|FALSE
	 */
	public function findOneByUsername($username)
	{
		$query = $this->connection->query('SELECT * FROM [:auth:account] WHERE [username] = %s', $username);
		return $query->fetch();
	}



	/**
	 * @param string $email
	 * @return DibiRow|FALSE
	 */
	public function findOneByEmail($email)
	{
		$query = $this->connection->query('SELECT * FROM [:auth:account] WHERE [email] = %s', $email);
		return $query->fetch();
	}



	/**
	 * @param int $id
	 * @param string $username
	 * @param string $password
	 */
	public function changePassword($id, $username, $password)
	{
		$data = array(
			'sha_pass_hash' => Authenticator::calculateHash($password, $username),
		);
		$this->connection->query('UPDATE [:auth:account] SET', $data, 'WHERE [id] = %i', $id);
	}



	/**
	 * @param int $id
	 * @param string $email
	 */
	public function changeEmail($id, $email)
	{
		$data = array(
			'email' => $email,
		);
		$this->connection->query('UPDATE [:auth:account] SET', $data, 'WHERE [id] = %i', $id);
	}



	/**
	 * @param int $id
	 * @param string $type
	 */
	public function changeType($id, $type)
	{
		$data = array(
			'expansion' => $type,
		);
		$this->connection->query('UPDATE [:auth:account] SET', $data, 'WHERE [id] = %i', $id);
	}



	/**
	 * @param string $username
	 * @param string $password
	 * @param string $email
	 * @param int $type
	 * @return int
	 */
	public function create($username, $password, $email, $type)
	{
		// crate account
		$data = array(
			'username' => $username,
			'sha_pass_hash' => Authenticator::calculateHash($password, $username),
			'email' => $email,
			'expansion' => $type,
		);
		$this->connection->query('INSERT INTO [:auth:account]', $data);
		$id = $this->connection->getInsertId();

		// ban account
		$data = array(
			'id' => $id,
			'bandate' => time(),
			'unbandate' => 0,
			'bannedby' => 'Account Manager 2.0',
			'banreason' => 'E-mail activation',
			'active' => 1,
		);
		$this->connection->query('INSERT INTO [:auth:account_banned]', $data);

		// rbac
		try {
			$this->connection->query('SELECT 1+1 FROM [:auth:rbac_account_groups] LIMIT 1')->fetch();
			$data = array(
				'accountId' => $id,
				'groupId' => 1, // played commands
				'realmId' => -1,
			);
			$this->connection->query('INSERT INTO [:auth:rbac_account_groups]', $data);
		} catch (DibiDriverException $e) {
		}

		return $id;
	}



	/**
	 * @param int $id
	 */
	public function activate($id)
	{
		$this->connection->query('UPDATE [:auth:account_banned] SET [active] = 0 WHERE [id] = %i', $id, 'AND [banreason] = %s', 'E-mail activation');
	}

}