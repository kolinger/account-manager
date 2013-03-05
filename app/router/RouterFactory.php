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
		$router[] = new NRoute('<presenter>[/<action>][/<id>]', 'Dashboard:default');
		return $router;
	}

}
