<?php
/***************************************************************************
 *   Copyright (C) 2009 by Solomatin Alexandr                              *
 *                                                                         *
 ***************************************************************************/
/* $Id: InterceptingChainHandler.class.php 283 2009-12-01 07:51:46Z lom $ */

	interface InterceptingChainHandler
	{
		public function run(InterceptingChain $chain);

	}

?>