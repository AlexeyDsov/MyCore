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

	class SimplePhpViewParametrized extends SimplePhpView
	{
		protected $params = array();

		/**
		 * @param string $name
		 * @return any
		 */
		public function get($name)
		{
			if (!$this->has($name)) {
				throw new MissingElementException("not setted value with name '$name'");
			}
			return $this->params[$name];
		}

		/**
		 * @param string $name
		 * @param any $value
		 * @return SimplePhpViewParametrized
		 */
		public function set($name, $value)
		{
			if ($this->has($name)) {
				throw new WrongStateException("value with name '$name' already setted ");
			}
			$this->params[$name] = $value;
			return $this;
		}

		/**
		 * @param string $name
		 * @return SimplePhpViewParametrized
		 */
		public function drop($name)
		{
			if (!$this->has($name)) {
				throw new MissingElementException("not setted value with name '$name'");
			}
			unset($this->params[$name]);
			return $this;
		}

		/**
		 * @param type $name
		 * @return boolean
		 */
		public function has($name)
		{
			Assert::isScalar($name);
			return array_key_exists($name, $this->params);
		}

		/**
		 * Короткий вызов для htmlspecialchars в шаблоне
		 * @param string $value
		 * @return string
		 */
		protected function escape($value/*,  sprintf params */)
		{
			if (func_num_args() > 1) {
				$value = call_user_func_array('sprintf', func_get_args());
			}
			return htmlspecialchars($value);
		}
	}
?>