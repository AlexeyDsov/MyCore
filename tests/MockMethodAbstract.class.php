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

	abstract class MockMethodAbstract {

		protected $name = null;
		/**
		 * @var Closure
		 */
		protected $function = null;
		protected $return = null;
		protected $throwException = null;
		protected $returnArgument = null;
		protected $returnSelf = false;
		protected $returnFunction = false;

		public function __construct($name) {
			$this->setName($name);
		}

		/**
		 * @param string $className
		 * @return MockMethodAbstract
		 */
		public function setName($className) {
			$this->name = $className;
			return $this;
		}

		/**
		 * @return string
		 */
		public function getName() {
			return $this->name;
		}

		/**
		 * @param any $return
		 * @return MockMethodAbstract
		 */
		public function setFunction(Closure $return) {
			$this->function = $return;
			return $this;
		}

		/**
		 * @param any $returnObject
		 * @return MockMethodAbstract
		 */
		public function setReturn($returnObject) {
			$this->return = $returnObject;
			return $this;
		}

		/**
		 * @return MockMethodAbstract
		 */
		public function setThrowException(Exception $exception) {
			$this->throwException = $exception;
			return $this;
		}

		/**
		 * @param int $returnArgument
		 * @return MockMethodAbstract
		 */
		public function setReturnArgument($returnArgument) {
			Assert::isPositiveInteger($returnArgument);
			$this->returnArgument = $returnArgument;
			return $this;
		}

		/**
		 * @param bool $returnObject
		 * @return MockMethodAbstract
		 */
		public function setReturnSelf($returnSelf) {
			Assert::isTernaryBase($returnSelf);
			$this->returnSelf = $returnSelf;
			return $this;
		}

		/**
		 * @param bool $returnObject
		 * @return MockMethodAbstract
		 */
		public function setReturnFunction($returnFunction) {
			Assert::isTernaryBase($returnFunction);
			$this->returnFunction = $returnFunction;
			return $this;
		}

		abstract public function integrateToObject(IMockSpawnSupport $test, $mockObject);

		protected function spawnReturn(IMockSpawnSupport $test, $mockObject) {
			$mockFunction = $this->function;
			$mockReturn = $this->return;
			$throwException = $this->throwException;
			$returnArgument = $this->returnArgument;
			$returnFunction = $this->returnFunction;
			$returnSelf = $this->returnSelf;

			$this->assertDoubleReturns();
			if ($returnFunction || $returnSelf || $throwException) {
				if ($returnFunction && !$mockFunction) {
					Assert::isUnreachable('Return function must exists if you want return it\s result');
				}

				return $test->getReturnCallback(function (/* args */) use(
					$test, $mockObject, $mockFunction, $mockReturn, $throwException, $returnArgument, $returnFunction, $returnSelf
				) {
					if ($mockFunction) {
						$functionResult = call_user_func_array($mockFunction, func_get_args());
						if ($returnFunction) {
							return $functionResult;
						}
					}

					if ($returnSelf) {
						return $mockObject;
					}

					if ($throwException) {
						$exceptionName = get_class($throwException);
						throw new $exceptionName($throwException->getMessage(), $throwException->getCode());
					}

					if ($returnArgument !== null) {
						return $test->getReturnArgument($returnArgument);
					}

					return $test->getReturnValue($mockReturn);
				});
			} elseif ($returnArgument !== null) {
				return $test->getReturnArgument($returnArgument);
			} else {
				return $test->getReturnValue($mockReturn);
			}
		}

		protected function assertDoubleReturns() {
			$doubleList = array(
				'throwException' => ($this->throwException !== null),
				'returnArgument' => ($this->returnArgument !== null),
				'returnFunction' => ($this->returnFunction === true),
				'returnSelf' => ($this->returnSelf === true),
			);

			$trueList = array();
			foreach ($doubleList as $name => $result) {
				if ($result === true) {
					$trueList[] = $name;
				}
			}

			if (count($trueList) > 1) {
				throw new WrongStateException(
					"At one moment for method {$this->name} setted more than one return: "
						. implode(', ', $trueList)
				);
			}
		}
	}
?>