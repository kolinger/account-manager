<?php

/**
 * @author Tomáš Kolinger <tomas@kolinger.name>
 */
class RouterFactory
{

	/**
	 * @return IRouter
	 */
	public function createRouter()
	{
		$router = new NRouteList();
		$router[] = new NRoute('[<lang cz|en|sk>/]<presenter>[/<action>][/<id>]', array(
			'presenter' => 'Dashboard',
			'action' => 'default',
			'lang' => 'cz',
		));
		return $router;
	}

}
