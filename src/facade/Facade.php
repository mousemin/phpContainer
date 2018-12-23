<?php

namespace mouse\facade;

use mouse\container\Container;

class Facade
{
	/**
	 * Container对象
	 * 
	 * @var Object
	 */
	static $container;

	/**
	 * 创建Facade使用的对象
	 * 
	 * @param  string $class 类名|别名
	 * @return Object        
	 */
	public static function createFacade($class = '')
	{
		$class = empty($class) ? static::getFacadeClass() : static::class;
		if(is_null(static::$container)) {
			static::$container = new Container;
		}
		if(static::$container->has($class)) {
			return static::$container->get($class);
		} elseif (class_exists($class)) {
			return static::$container->generate($class);
		} else {
			throw new FacadeException("container中{$class}别名不存在");
		}
	}

	/**
	 * 设置Facade使用的类名|别名
	 * 	
	 * @return string
	 */
	protected static function getFacadeClass()
	{
		return __CLASS__;
	}
	
	public static function __callStatic($method, $argc)
	{

		return call_user_func_array([static::createFacade(), $method], $argc);
	}

}