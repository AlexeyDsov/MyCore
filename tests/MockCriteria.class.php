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

	class MockCriteria extends PHPUnit_Framework_TestCase {

		protected $className = null;
		protected $methodList = array();
		protected $mockMethodList = array();
		protected $disableConstructor = false;

		/**
		 * @return MockCriteria
		 */
		public static function create($className = null)
		{
			return new self($className);
		}

		public function __construct($className = null)
		{
			if ($className !== null) {
				$this->of($className);
			}
		}

		/**
		 * @param string $className
		 * @return MockCriteria
		 */
		public function of($className)
		{
			$this->className = $className;
			return $this;
		}
	
		/**
		 * @param array $methodList
		 * @return MockCriteria 
		 */
		public function setMockMethodList(array $methodList)
		{
			$this->mockMethodList = $methodList;
			return $this;
		}

		/**
		 * @param type $isDisableConstructor
		 * @return MockCriteria 
		 */
		public function setDisableConstructor($isDisableConstructor)
		{
			$this->disableConstructor = $isDisableConstructor;
			return $this;
		}

		public function addMethod(MockMethodAbstract $method)
		{
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
		public function hasMethod($methodName)
		{
			return isset($this->methodList[$methodName]);
		}

		/**
		 * @param string $methodName
		 * @return MockCriteria
		 */
		public function dropMethod($methodName)
		{
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
		public function spawn(IMockSpawnSupport $test)
		{
			Assert::isNotNull($this->className, 'Class of Mock object must be setted');

			$constructorArgs = func_get_args();
			array_shift($constructorArgs);

			$mockObject = $test->getMock($this->className, $this->mockMethodList, $constructorArgs, '', $this->disableConstructor);

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

		protected function addMethodForExahCall(MockMethod $method)
		{
			if (isset($this->methodList[$method->getName()])) {
				throw new WrongArgumentException("MockMethod with name {$method->getName()} already setted");
			}
			$this->methodList[$method->getName()] = $method;
		}

		protected function addMethodAtCall(MockMethodAt $method)
		{
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