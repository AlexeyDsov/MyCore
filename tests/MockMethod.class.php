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

	class MockMethod extends MockMethodAbstract {

		protected $callTimes = null;

		/**
		 * @return MockMethod
		 */
		public static function create($name)
		{
			return new self($name);
		}

		/**
		 * @param integer $return
		 * @return MockMethodAbstract
		 */
		public function setCallTimes($callTimes)
		{
			Assert::isPositiveInteger($callTimes);
			$this->callTimes = $callTimes;
			return $this;
		}

		/**
		 * @param integer $return
		 * @return MockMethodAbstract
		 */
		public function dropCallTimes()
		{
			$this->callTimes = null;
			return $this;
		}

		public function integrateToObject(IMockSpawnSupport $test, $mockObject)
		{
			$mockObject->
				expects($this->spawnExpects($test))->
				method($this->name)->
				will($this->spawnReturn($test, $mockObject));
		}

		protected function spawnExpects(IMockSpawnSupport $test)
		{
			if ($this->callTimes === null) {
				return $test->getAny();
			} elseif ($this->callTimes == 0) {
				return $test->getNever();
			} elseif ($this->callTimes == 1) {
				return $test->getOnce();
			} else {
				return $test->getExactly($this->callTimes);
			}
		}
	}
?>