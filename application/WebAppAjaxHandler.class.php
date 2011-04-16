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

	class WebAppAjaxHandler implements InterceptingChainHandler
	{
		private static $ajaxRequestVar = 'HTTP_X_REQUESTED_WITH';
		private static $ajaxRequestValueList = array('XMLHttpRequest');

		/**
		 * @return WebAppAjaxHandler
		 */
		public static function create()
		{
			return new self();
		}

		/**
		 * @return WebAppAjaxHandler
		 */
		public function run(InterceptingChain $chain)
		{
			$isAjaxRequest = $this->isAjaxRequest($chain->getRequest());

			$chain->setVar('isAjax', $isAjaxRequest);
			$chain->getServiceLocator()->set('isAjax', $isAjaxRequest);

			$chain->next();

			return $this;
		}

		/**
		 * @return boolean
		 */
		public function isAjaxRequest(HttpRequest $request)
		{
			$form = Form::create()->
				add(
					Primitive::plainChoice(self::$ajaxRequestVar)->
						setList(self::$ajaxRequestValueList)
				)->
				add(
					Primitive::boolean('isAjax')
				)->
				import($request->getServer())->
				importOneMore('isAjax', $request->getGet());

			if ($form->getErrors()) {
				return false;
			}
			if ($form->getValue(self::$ajaxRequestVar)) {
				return true;
			}
			if ($form->getValue('isAjax')) {
				return true;
			}
		}
	}
?>