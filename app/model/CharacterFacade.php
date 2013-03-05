<?php

/**
 * @author Tomáš Kolinger <tomas@kolinger.name>
 */
class CharacterFacade extends BaseFacade
{

	const GOLD = 10000;

	const CHARACTERS_FIELDS = '
		[guid],
		[name],
		[race],
		[class],
		[level],
		[online],
		[totaltime],
		[money]
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
	 * @param int $account
	 * @return DibiRow|FALSE
	 */
	public function findOnlineByAccount($account)
	{
		$query = $this->connection->query('SELECT ' . self::CHARACTERS_FIELDS . ' FROM [:chars:characters] WHERE [online] = 1 AND [account] = %i', $account);
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



	/**
	 * @param int $id
	 * @return DibiRow|FALSE
	 */
	public function findOneById($id)
	{
		$query = $this->connection->query('SELECT ' . self::CHARACTERS_FIELDS . ' FROM [:chars:characters] WHERE [guid] = %i', $id);
		return $query->fetch();
	}



	/**
	 * @param int $name
	 * @return DibiRow|FALSE
	 */
	public function findOneByName($name)
	{
		$query = $this->connection->query('SELECT ' . self::CHARACTERS_FIELDS . ' FROM [:chars:characters] WHERE [name] = %s', $name);
		return $query->fetch();
	}



	/**
	 * @param int $id
	 * @param string $name
	 * @return bool
	 */
	public function rename($id, $name)
	{
		$data = array(
			'name' => ucfirst(strtolower($name)),
		);
		$this->connection->query('UPDATE [:chars:characters] SET', $data, 'WHERE [guid] = %i', $id);
		return TRUE;
	}



	/**
	 * @param int $id
	 * @param string|array $location
	 */
	public function teleport($id, $location)
	{
		if (is_array($location)) {
			$x = $location['x'];
			$y = $location['y'];
			$z = $location['z'];
			$o = $location['o'];
			$map = $location['map'];
		} else {
			$tele = $this->connection->query('SELECT [position_x], [position_y], [position_z], [orientation], [map] FROM [:world:game_tele] WHERE [name] = %s', $location);
			$x = $tele['position_x'];
			$y = $tele['position_y'];
			$z = $tele['position_z'];
			$o = $tele['orientation'];
			$map = $tele['map'];
		}
		$data = array(
			'position_x' => $x,
			'position_y' => $y,
			'position_z' => $z,
			'orientation' => $o,
			'map' => $map,
		);
		$this->connection->query('UPDATE [:chars:characters] SET', $data, 'WHERE [guid] = %i', $id);
	}



	/**
	 * @param int $id
	 * @param $type
	 * @param string $count
	 * @return bool
	 */
	public function takePrice($id, $type, $count)
	{
		$character = $this->findOneById($id);

		// golds
		if ($type == 'golds') {
			$price = $count * self::GOLD;
			if ($character->money < $price) {
				return FALSE;
			}
			$data = array(
				'money' => $character->money - $price,
			);
			$this->connection->query('UPDATE [:chars:characters] SET', $data, 'WHERE [guid] = %i', $id);
			return TRUE;

		// item
		} else if ($type == 'item') {
			$parts = explode(':', $count);
			$item = $parts[0];
			$count = $parts[1];

			if (!$this->removeItem($id, $item, $count)) {
				return FALSE;
			}
			return TRUE;
		}

		// TODO: credits
	}



	/**
	 * @param int $id
	 * @param int $item
	 * @param int $count
	 * @return bool
	 */
	public function removeItem($id, $item, $count)
	{
		try {
			// trinitycore 2
			$items = $this->connection->query('SELECT [guid], [count] FROM [:chars:item_instance] WHERE [owner_guid] = %i AND [itemEntry] = %i', $id, $item)->fetchAll();
		} catch (DibiDriverException $e) {
			// oregoncore
			$items = $this->connection->query("
				SELECT
					[guid],
					CAST(SUBSTRING_INDEX(SUBSTRING_INDEX([data], ' ', 15), ' ', -1) AS UNSIGNED) AS [count],
					[data]
				FROM [:chars:item_instance]
				WHERE CAST(SUBSTRING_INDEX(SUBSTRING_INDEX([data], ' ', 4), ' ', -1) AS UNSIGNED) = %i", $item
			);
			$oregon = TRUE;
		}

		// remove or update items
		foreach ($items as $item) {
			if ($item->count <= $count) {
				$this->connection->query('DELETE FROM [:chars:item_instance] WHERE [guid] = %i', $item->guid);
				$this->connection->query('DELETE FROM [:chars:character_inventory] WHERE [item] = %i', $item->guid);
			} else {
				if (isset($oregon)) {
					$parts = explode(' ', $item->data);
					$parts[14] = $item->count - $count;
					$data = array(
						'data' => implode(' ', $parts),
					);
				} else {
					$data = array(
						'count' => $item->count - $count,
					);
				}
				$this->connection->query('UPDATE [:chars:item_instance] SET', $data, 'WHERE [guid] = %i', $item->guid);
			}
			$count = $count - $item->count;
			if ($count <= 0) {
				break;
			}
		}

		if ($count > 0) {
			return FALSE;
		}

		return TRUE;
	}

}