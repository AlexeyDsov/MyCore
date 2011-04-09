<?php
/***************************************************************************
 *   Copyright (C) 2009 by Solomatin Alexandr                              *
 *                                                                         *
 ***************************************************************************/
/* $Id: WebAppControllerHandler.class.php 283 2009-12-01 07:51:46Z lom $ */

class WebAppControllerHandler implements InterceptingChainHandler {
	/**
	 * @return WebAppControllerHandler
	 */
	public static function create()
	{
		return new self();
	}

	/**
	 * @return WebAppControllerHandler
	 */
	public function run(InterceptingChain $chain)
	{
		$controllerName = $chain->getControllerName();

		Assert::isNotEmpty($controllerName);

		$serviceLocator = $chain->getServiceLocator();
		$controller = $serviceLocator->spawn($controllerName);
		$this->prepairController($chain, $controller);

		$modelAndView = $controller->handleRequest($chain->getRequest());

		if (!$modelAndView) {
			throw new WrongStateException(
				"After controller '{$controllerName}::handleRequest' we expect get ModelAndView, but not null"
			);
		}

		if (!$modelAndView->getView()) {
			$modelAndView->setView($controllerName);
		}

		if (!$modelAndView->getView() instanceof RedirectView) {
			$this->prepairNonRedirectModel($chain, $controllerName, $modelAndView->getModel());
		}

		$chain->setMav($modelAndView);

		$chain->next();

		return $this;
	}

	/**
	 * @param InterceptingChain $chain
	 * @param string $controllerName
	 * @param Model $model
	 * @return WebAppControllerHandler
	 */
	protected function prepairNonRedirectModel(InterceptingChain $chain, $controllerName, Model $model) {
		return $this;
	}

	/**
	 * @param InterceptingChain $chain
	 * @param Controller $controller
	 * @return WebAppControllerHandler
	 */
	protected function prepairController(InterceptingChain $chain, Controller $controller)
	{
		return $this;
	}
}
?>