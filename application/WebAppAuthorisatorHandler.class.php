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

	class WebAppAuthorisatorHandler implements InterceptingChainHandler
	{
		protected $authorisatorList = array();

		/**
		 * @return WebAppAuthorisatorHandler
		 */
		public static function create()
		{
			return new self();
		}

		/**
		 * @return WebAppAuthorisatorHandler
		 */
		public function run(InterceptingChain $chain)
		{
			$serviceLocator = $chain->getServiceLocator();
			$session = $serviceLocator->get('session');

			foreach ($this->authorisatorList as $authrisatorName => $authorisator) {
				$serviceLocator->set($authrisatorName, $authorisator->setSession($session));
			}

			$chain->next();

			return $this;
		}

		/**
		 * @return WebAppAuthorisatorHandler
		 */
		public function addAuthorisator($nameInLocator, Authorisator $authorisator)
		{
			$this->authorisatorList[$nameInLocator] = $authorisator;
			return $this;
		}
	}
?>