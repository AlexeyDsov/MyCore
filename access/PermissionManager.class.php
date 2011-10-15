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

	class PermissionManager {

		/**
		 * Статическое создание объекта класса
		 * @return PermissionManager
		 */
		public static function create() {
			return new self;
		}

		/**
		 * Возвращает признак
		 * @param IPermissionUser $user
		 * @param string $action
		 * @return bool
		 */
		public function hasPermission(IPermissionUser $user, $action) {
			$actionList = $user->getActionList();

			return (array_search($action, $actionList) !== false);
		}
	}