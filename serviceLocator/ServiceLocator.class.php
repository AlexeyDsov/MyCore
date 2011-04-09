<?php
class ServiceLocator
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
	 * @return IServiceLocatorSupport
	 */
	public function spawn($className)
	{
		$class = new $className;
		if ($class instanceof IServiceLocatorSupport) {
			$class->setServiceLocator($this);
		}
		return $class;
	}

	/**
	 * @return ServiceLocator
	 */
	public function set($name, $service)
	{
		Assert::isFalse($this->has($name), 'object with such name already setted');
		$this->store[$name] = $service;
		return $this;
	}

	public function get($name)
	{
		Assert::isTrue($this->has($name), 'object with such name was not setted');
		return $this->store[$name];
	}

	public function has($name)
	{
		return array_key_exists($name, $this->store);
	}
}
?>