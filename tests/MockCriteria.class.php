<?php
class MockCriteria extends PHPUnit_Framework_TestCase {
	
	protected $className = null;
	protected $methodList = array();
	
	/**
	 * @return MockCriteria
	 */
	public static function create($className = null) {
		return new self($className);
	}
	
	public function __construct($className = null) {
		if ($className !== null) {
			$this->of($className);
		}
	}
	
	/**
	 * @param string $className
	 * @return MockCriteria 
	 */
	public function of($className) {
		$this->className = $className;
		return $this;
	}
	
	public function addMethod(MockMethodAbstract $method) {
		if ($method instanceof MockMethod) {
			$this->addMethodForExahCall($method);
		} elseif ($method instanceof MockMethodAt) {
			$this->addMethodAtCall($method);
		} else {
			throw new WrongStateException('Undefined method class: '.  get_class($method));
		}

		return $this;
	}
	
	/**
	 * @param string $methodName
	 * @return bool 
	 */
	public function hasMethod($methodName) {
		return isset($this->methodList[$methodName]);
	}
	
	/**
	 * @param string $methodName
	 * @return MockCriteria 
	 */
	public function dropMethod($methodName) {
		if (!isset($this->methodList[$methodName])) {
			throw new MissingElementException("MockMethodAbstract does not exists: {$methodName} ");
		}
		unset($this->methodList[$methodName]);
		return $this;
	}
	
	/**
	 * @return object
	 * @throw WrongArgumentException
	 */
	public function spawn(IMockSpawnSupport $test) {
		Assert::isNotNull($this->className, 'Class of Mock object must be setted');
		
		$mockObject = $test->getMock($this->className);
		
		foreach ($this->methodList as $method) {
			if (is_array($method)) {
				foreach ($method as $method) {
					$method->integrateToObject($test, $mockObject);
				}
			} else {
				$method->integrateToObject($test, $mockObject);
			}
		}
		
		return $mockObject;
	}
	
	protected function addMethodForExahCall(MockMethod $method) {
		if (isset($this->methodList[$method->getName()])) {
			throw new WrongArgumentException("MockMethod with name {$method->getName()} already setted");
		}
		$this->methodList[$method->getName()] = $method;
	}
	
	protected function addMethodAtCall(MockMethodAt $method) {
		if (isset($this->methodList[$method->getName()])) {
			$methodList = $this->methodList[$method->getName()];
			if (!is_array($methodList)) {
				throw new WrongArgumentException("MockMethod with name {$method->getName()} already setted");
			}
			if (isset($methodList[$method->getCallTime()])) {
				throw new WrongArgumentException("MockMethod with name {$method->getName()} and callTime {$method->getCallTime()} already setted");
			}
		}
		
		$this->methodList[$method->getName()][$method->getCallTime()] = $method;
	}
}
?>