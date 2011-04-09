<?php
/***************************************************************************
 *   Copyright (C) 2009 by Solomatin Alexandr                              *
 *                                                                         *
 ***************************************************************************/
/* $Id: WebAppViewHandler.class.php 283 2009-12-01 07:51:46Z lom $ */

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
				$viewResolver = MultiPrefixPhpViewResolver::create()->
					setViewClassName('SimplePhpView')->
					addPrefix(
						$chain->getPathTemplate()
					);

				$view = $viewResolver->resolveViewName($viewName);
			}

			$view->render($model);

			$chain->next();

			return $this;
		}

	}

?>