<?php
/***************************************************************************
 *   Copyright (C) 2009 by Solomatin Alexandr                              *
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU Lesser General Public License as        *
 *   published by the Free Software Foundation; either version 3 of the    *
 *   License, or (at your option) any later version.                       *
 *                                                                         *
 ***************************************************************************/

	class WebApplication extends InterceptingChain implements IServiceLocatorSupport
	{
		const OBJ_REQUEST = 'request';
		const OBJ_MAV = 'mav';
		const OBJ_CONTROLLER = 'controller';
		const OBJ_CONTROLLER_NAME = 'controllerName';
		const OBJ_SERVICE_LOCATOR = 'serviceLocator';
		const OBJ_PATH_WEB = 'pathWeb';
		const OBJ_PATH_CONTROLLER = 'pathController';
		const OBJ_PATH_TEMPLATE = 'pathTemplate';

		protected $vars = array();

		/**
		 * @return WebApplication
		 */
		public static function create()
		{
			return new self();
		}

		public function __construct()
		{
			$request = HttpRequest::create()->
				setGet($_GET)->
				setPost($_POST)->
				setCookie($_COOKIE)->
				setServer($_SERVER)->
				setFiles($_FILES);

			if (!empty($_SESSION)) {
				$request->setSession($_SESSION);
			}

			$this->setRequest($request);

			return $this;
		}

		public function getVar($name)
		{
			return $this->vars[$name];
		}

		/**
		 * @return WebApplication
		 */
		public function setVar($name, $var)
		{
			$this->vars[$name] = $var;

			return $this;
		}

		public function hasVar($name)
		{
			return array_key_exists($name, $this->vars);
		}

		/**
		 * @return HttpRequest
		 */
		public function getRequest()
		{
			return $this->getVar(self::OBJ_REQUEST);
		}

		/**
		 * @return WebApplication
		 */
		public function setRequest(HttpRequest $request)
		{
			return $this->setVar(self::OBJ_REQUEST, $request);
		}

		/**
		 * @return ModelAndView
		 */
		public function getMav()
		{
			return $this->getVar(self::OBJ_MAV);
		}

		/**
		 * @return WebApplication
		 */
		public function setMav(ModelAndView $mav)
		{
			return $this->setVar(self::OBJ_MAV, $mav);
		}

		/**
		 * @return Controller
		 */
		public function getController()
		{
			return $this->getVar(self::OBJ_CONTROLLER);
		}

		/**
		 * @return WebApplication
		 */
		public function setController(Controller $controller)
		{
			return $this->setVar(self::OBJ_CONTROLLER, $controller);
		}

		public function getControllerName()
		{
			return $this->getVar(self::OBJ_CONTROLLER_NAME);
		}

		/**
		 * @return WebApplication
		 */
		public function setControllerName($controllerName)
		{
			return $this->setVar(self::OBJ_CONTROLLER_NAME, $controllerName);
		}

		/**
		 * @return ServiceLocator
		 */
		public function getServiceLocator()
		{
			return $this->getVar(self::OBJ_SERVICE_LOCATOR);
		}

		/**
		 * @return ServiceLocator
		 */
		public function setServiceLocator(IServiceLocator $serviceLocator)
		{
			return $this->setVar(self::OBJ_SERVICE_LOCATOR, $serviceLocator);
		}

		public function getPathWeb()
		{
			return $this->getVar(self::OBJ_PATH_WEB);
		}

		/**
		 * @return WebApplication
		 */
		public function setPathWeb($pathWeb)
		{
			return $this->setVar(self::OBJ_PATH_WEB, $pathWeb);
		}

		public function getPathController()
		{
			return $this->getVar(self::OBJ_PATH_CONTROLLER);
		}

		/**
		 * @return WebApplication
		 */
		public function setPathController($pathController)
		{
			return $this->setVar(self::OBJ_PATH_CONTROLLER, $pathController);
		}

		public function getPathTemplate()
		{
			return $this->getVar(self::OBJ_PATH_TEMPLATE);
		}

		/**
		 * @return WebApplication
		 */
		public function setPathTemplate($pathTemplate)
		{
			return $this->setVar(self::OBJ_PATH_TEMPLATE, $pathTemplate);
		}
	}
?>