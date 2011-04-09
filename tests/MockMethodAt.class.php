<?php
class MockMethodAt extends MockMethodAbstract {
	
	protected $callTime = null;
	
	/**
	 * @return MockMethodAt
	 */
	public static function create($name, $callTime = 0) {
		return new self($name, $callTime);
	}
	
	public function __construct($name, $callTime = 0) {
		parent::__construct($name);
		$this->setCallTime($callTime);
	}
	
	/**
	 * @param integer $return
	 * @return MockMethodAt 
	 */
	public function setCallTime($callTime) {
		Assert::isPositiveInteger($callTime);
		$this->callTime = $callTime;
		return $this;
	}
	
	public function getCallTime() {
		return $this->callTime;
	}
	
	/**
	 * @param integer $return
	 * @return MockMethodAbstract 
	 */
	public function dropCallAt() {
		$this->callTime = null;
		return $this;
	}
	
	public function integrateToObject(IMockSpawnSupport $test, $mockObject) {
		$mockObject
			->expects($this->spawnCallTime($test))
			->method($this->name)
			->will($this->spawnReturn($test, $mockObject));
	}
	
	protected function spawnCallTime(IMockSpawnSupport $test) {
		return $test->getAt($this->callTime);
	}
}
?>