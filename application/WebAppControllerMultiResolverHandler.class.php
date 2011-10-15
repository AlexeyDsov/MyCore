<?php
	class WebAppControllerMultiResolverHandler extends WebAppControllerResolverHandler
	{
		protected $subPathList = array();

		/**
		 * @return WebAppControllerMultiResolverHandler
		 */
		public static function create()
		{
			return new self();
		}

		/**
		 * Добавляет подкаталог в котором может быть контроллер относительного базового каталога контроллеров
		 * @param string $subPath
		 * @return WebAppControllerMultiResolverHandler
		 */
		public function addSubPath($subPath) {
			$this->subPathList[] = $subPath;
			return $this;
		}

		/**
		 * Возвращает признак - есть ли для данного приложания контроллер с указанным именем или нет
		 * @param tystringpe $controllerName
		 * @param string $path
		 * @return boolean
		 */
		protected function checkControllerName($controllerName, $path)
		{
			return
				ClassUtils::isClassName($controllerName)
				&& $path
				&& $this->isReadable($controllerName, $path);
		}

		/**
		 * Возвращает признак есть ли файл контроллера в одной из дирректорий
		 * @param string $controllerName
		 * @param string $path
		 * @return boolean
		 */
		protected function isReadable($controllerName, $path)
		{
			$subPathList = $this->subPathList;
			array_unshift($subPathList, '');

			foreach ($subPathList as $subPath) {
				if (is_readable($path.$subPath.$controllerName.EXT_CLASS)) {
					return true;
				}
			}

			return false;
		}
	}
?>