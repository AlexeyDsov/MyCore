<?php
/***************************************************************************
 *   Copyright (C) 2010 by Alexey Denisov                                  *
 *                                                                         *
 ***************************************************************************/

	class WebAppAuthorisatorInit implements InterceptingChainHandler
	{
		protected $authorisatorList = array();
		
		/**
		 * @return WebAppAuthorisatorInit
		 */
		public static function create()
		{
			return new self();
		}

		/**
		 * @return WebAppAuthorisatorInit
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
		 * @return WebAppAuthorisatorInit
		 */
		public function addAuthorisator($nameInLocator, Authorisator $authorisator)
		{
			$this->authorisatorList[$nameInLocator] = $authorisator;
			return $this;
		}
	}
?>