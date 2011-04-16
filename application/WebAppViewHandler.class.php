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

	class WebAppViewHandler implements InterceptingChainHandler
	{
		/**
		 * @return WebAppViewHandler
		 */
		public static function create()
		{
			return new self();
		}

		/**
		 * @return WebAppViewHandler
		 */
		public function run(InterceptingChain $chain)
		{
			$view	= $chain->getMav()->getView();
			$model 	= $chain->getMav()->getModel();

			if (!$view instanceof View) {
				$viewName = $view;
				$viewResolver = $this->getViewResolver($chain, $model);
				$view = $viewResolver->resolveViewName($viewName);
			}

			if ($chain->getMav()->viewIsNormal()) {
				$this->updateNonRedirectModel($chain, $model);
			}
			$view->render($model);

			$chain->next();

			return $this;
		}

		/**
		 * @param InterceptingChain $chain
		 * @param Model $model
		 * @return ViewResolver
		 */
		protected function getViewResolver(InterceptingChain $chain, Model $model) {
			return PhpViewResolver::create($chain->getPathTemplateDefault(), EXT_TPL);
		}

		/**
		 * @param Model $model
		 * @return WebAppViewHandler
		 */
		protected function updateNonRedirectModel(InterceptingChain $chain, Model $model) {
			return $this;
		}
	}
?>