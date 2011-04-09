<?php
/***************************************************************************
 *   Copyright (C) 2010 by Alexey Denisov                                  *
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
					Primitive::plainChoice(self::$ajaxRequestVar)
						->setList(self::$ajaxRequestValueList)
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