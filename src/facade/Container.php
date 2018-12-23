<?php

namespace mouse\facade;

class Container extends Facade
{
	protected static function getFacadeClass()
	{
		return 'container';
	}
}