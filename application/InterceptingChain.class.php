<?php
/***************************************************************************
 *   Copyright (C) 2009 by Solomatin Alexandr                              *
 *                                                                         *
 ***************************************************************************/
/* $Id: InterceptingChain.class.php 283 2009-12-01 07:51:46Z lom $ */

	class InterceptingChain
	{
		protected $chain = array();

		protected $pos	= -1;

		/**
		 * @return InterceptingChain
		 */
		public static function create()
		{
			return new self;
		}

		/**
		 * @return InterceptingChain
		 */
		public function add(InterceptingChainHandler $handler)
		{
			$this->chain []= $handler;

			return $this;
		}

		public function getHandlers()
		{
			return $this->chain;
		}

		public function next()
		{
			$this->pos++;

			if (isset($this->chain[$this->pos])) {
				$this->chain[$this->pos]->run($this);
			}

			return $this;
		}

		/**
		 * @return InterceptingChain
		 */
		public function run()
		{
			$this->pos = -1;

			$this->next();

			return $this;
		}

	}

?>