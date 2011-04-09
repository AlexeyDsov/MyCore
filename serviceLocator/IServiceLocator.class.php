<?php
interface IServiceLocator
{
	/**
	 * @param string $className
	 * @return object
	 */
	public function spawn($className);

	/**
	 * @param string $name
	 * @param any $service
	 * @return ServiceLocator
	 */
	public function set($name, $service);

	/**
	 * @param string $name
	 * @return any
	 */
	public function get($name);

	/**
	 * @param string $name
	 * @return ServiceLocator
	 */
	public function drop($name);

	/**
	 * @param string $name
	 * @return boolean
	 */
	public function has($name);

	/**
	 * @return array
	 */
	public function getList();
}
?>