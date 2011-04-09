<?php
/***************************************************************************
 *   Copyright (C) 2009 by Solomatin Alexandr                              *
 *                                                                         *
 ***************************************************************************/
/* $Id: WebAppControllerResolverHandler.class.php 283 2009-12-01 07:51:46Z lom $ */

	class WebAppControllerResolverHandler implements InterceptingChainHandler
	{
		protected $defaultController = 'MainController';

		protected $notfoundController = 'ErrorController';

		/**
		 * @return WebAppControllerResolverHandler
		 */
		public static function create()
		{
			return new self();
		}

		/**
		 * @return WebAppControllerResolverHandler
		 */
		public function run(InterceptingChain $chain)
		{
			$controllerName = $this->defaultController;

			$area = null;
			if ($chain->getRequest()->hasAttachedVar('area')) {
				$area = $chain->getRequest()->getAttachedVar('area');
			}

			if ($chain->getRequest()->hasGetVar('area')) {
				$area = $chain->getRequest()->getGetVar('area');
			}

			if (
				$area
				&& $this->checkControllerName($area.'Controller', $chain->getPathController())
			) {
				$controllerName = $area.'Controller';
			} elseif ($area) {
				$controllerName = $this->notfoundController;
				// таким образом, запросили модуль, которого нет на нашем сайте
				HeaderUtils::sendHttpStatus(
					new HttpStatus(HttpStatus::CODE_404)
				);
			}

			$chain->setControllerName($controllerName);

			$chain->next();

			return $this;
		}

		/**
		 * @return WebAppControllerResolverHandler
		 */
		public function setDefaultController($defaultController)
		{
			$this->defaultController = $defaultController;

			return $this;
		}

		/**
		 * @return WebAppControllerResolverHandler
		 */
		public function setNotfoundController($notfoundController)
		{
			$this->notfoundController = $notfoundController;

			return $this;
		}

		protected function checkControllerName($area, $path)
		{
			return
				ClassUtils::isClassName($area)
				&& $path
				&& is_readable($path.$area.EXT_CLASS);
		}

	}

?>