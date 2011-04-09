<?php
class MockServiceLocator extends ServiceLocator
{
	protected $store = array();
	protected $objectList = array();

	/**
	 * @return MockServiceLocator
	 */
	public static function create()
	{
		return new self;
	}

	/**
	 * @param string $className
	 * @param object $object
	 * @return MockServiceLocator
	 */
	public function addSpawnObject($className, $object)
	{
		$this->objectList[$className][] = $object;
		return $this;
	}

	/**
	 * @param array $objectList
	 * @return MockServiceLocator
	 */
	public function setObjectList(array $objectList)
	{
		$this->objectList = $objectList;
		return $this;
	}

	/**
	 * @param string $className
	 * @return object
	 */
	public function spawn($className)
	{
		if (isset($this->objectList[$className])) {
			$classNameList = $this->objectList[$className];
			if (empty($classNameList)) {
				throw new WrongStateException("Object list for class '{$className}' already empty");
			}
			$object = reset($classNameList);
			unset($classNameList[key($classNameList)]);
			return $this->implementSelf($object);
		} else {
			throw new WrongStateException("Class '{$className}' was not added for spawn");
		}
	}

	/**
	 * @param object $object
	 * @return object
	 */
	protected function implementSelf($object)
	{
		$object = parent::implementSelf($object);
		if ($object instanceof IServiceLocatorSupport) {
			$subLocator = $object->getServiceLocator();
			$subLocator->setObjectList();
		}
		return $object;
	}
}
?>