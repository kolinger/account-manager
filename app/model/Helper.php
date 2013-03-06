<?php

/**
 * @author Tomáš Kolinger <tomas@kolinger.name>
 */
class Helper
{

	/**
	 * @var array
	 */
	public static $races = array(
		1 => 'Human',
		2 => 'Orc',
		3 => 'Dwarf',
		4 => 'Night Elf',
		5 => 'Undead',
		6 => 'Tauren',
		7 => 'Gnome',
		8 => 'Troll',
		10 => 'Blood Elf',
		11 => 'Draenei',
	);

	/**
	 * @var array
	 */
	public static $classes = array(
		1 => 'Warrior',
		2 => 'Paladin',
		3 => 'Hunter',
		4 => 'Rogue',
		5 => 'Priest',
		6 => 'Death Knight',
		7 => 'Shaman',
		8 => 'Mage',
		9 => 'Warlock',
		11 => 'Druid',
	);



	/**
	 * @param int $seconds
	 * @return string
	 */
	public static function formatPlayedTime($seconds)
	{
		if ($seconds < 60) {
			$time = 'méně než minuta';
		} else {
			$days = floor($seconds / 86400);
			$seconds = $seconds - 86400 * $days;
			$hours = floor($seconds / 3600);
			$seconds = $seconds - 3600 * $hours;
			$minutes = floor($seconds / 60);

			$time = '';
			if ($days > 0) {
				$time .= $days . ' ' ._n('den', NULL, $days);
			}
			if ($hours > 0) {
				if ($days > 0) {
					$time .= ' ';
				}
				$time .= $hours . ' ' . _n('hodina', NULL, $hours);
			}
			if ($minutes > 0 && $days == 0) {
				if ($hours > 0) {
					$time .= ' ';
				}
				$time .= $minutes . ' ' ._n('minuta', NULL, $minutes);
			}
		}

		return $time;
	}



	public static function formatMute(DibiRow $account)
	{
		if ($account->mutetime < 0) {
			$time = $account->mutetime * -1;
		} else {
			$time = $account->mutetime - time();
		}
		if ($time <= 0) {
			return __('účet nemá mute');
		}
		$s = self::formatPlayedTime($time);
		if (isset($account->mutereason) && isset($account->muteby)) {
			$s .= ' (' . __('důvod:') . ' ' . $account->mutereason;
			$s .= ', ' . __('od:') . ' ' . $account->muteby . ')';
		}
		return $s;
	}

}