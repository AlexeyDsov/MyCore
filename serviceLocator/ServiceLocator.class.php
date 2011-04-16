<?php
/***************************************************************************
 *   Copyright (C) 2011 by Alexey Denisov                                  *
 *   alexeydsov@gmail.com                                                  *
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU Lesser General Public License as        *
 *   published by the Free Software Foundation; either version 3 of the    *
 *   License, or (at your option) any later version.                       *
 *                                                                         *
 ***************************************************************************/

class ServiceLocator implements IServiceLocator
{
	protected $store = array();

	/**
	 * @return ServiceLocator
	 */
	public static function create()
	{
		return new self;
	}

	/**
	 * @param string $className
	 * @return object
	 */
	public function spawn($className)
	{
		$object = new $className;
		return $this->implementSelf($object);
		return $object;
	}


	/**
	 * @param string $name
	 * @param any $service
	 * @return ServiceLocator
	 */
	public function set($name, $service)
	{
		Assert::isFalse($this->has($name), 'object with such name already setted');
		$this->store[$name] = $service;
		return $this;
	}

	/**
	 * @param string $name
	 * @return any
	 */
	public function get($name)
	{
		Assert::isTrue($this->has($name), 'object with such name was not setted');
		return $this->store[$name];
	}

	/**
	 * @param string $name
	 * @return ServiceLocator
	 */
	public function drop($name)
	{
		Assert::isTrue($this->has($name), 'object with such name was not setted');
		return $this->store[$name];
	}

	/**
	 * @param string $name
	 * @return boolean
	 */
	public function has($name)
	{
		return array_key_exists($name, $this->store);
	}

	/**
	 * @return array
	 */
	public function getList()
	{
		return $this->store;
	}

	/**
	 * @param object $object
	 * @return object
	 */
	protected function implementSelf($object)
	{
		if ($object instanceof IServiceLocatorSupport) {
			$subLocator = new $this();
			foreach ($this->store as $key => $value) {
				$subLocator->set($key, $value);
			}
			$object->setServiceLocator($subLocator);
		}
		return $object;
	}
}
?>