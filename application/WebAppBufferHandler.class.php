<?php
/***************************************************************************
 *   Copyright (C) 2009 by Solomatin Alexandr                              *
 *                                                                         *
 ***************************************************************************/
/* $Id: WebAppBufferHandler.class.php 283 2009-12-01 07:51:46Z lom $ */

	class WebAppBufferHandler implements InterceptingChainHandler
	{
		/**
		 * @return WebAppBufferHandler
		 */
		public static function create()
		{
			return new self();
		}

		/**
		 * @return WebAppBufferHandler
		 */
		public function run(InterceptingChain $chain)
		{
			ob_start();

			$chain->next();

			if (($pageContents = ob_get_contents()) !== '') {
				ob_end_flush();
			} else {
				ob_end_clean();
			}

			return $this;
		}

	}

?>