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

define('PATH_CORE', dirname(__FILE__).DS);

ini_set(
	'include_path',
	get_include_path()
	. join(
		PATH_SEPARATOR,
		array(
			PATH_CORE.'EntityProto',
			PATH_CORE.'ListMakerHelper',
			PATH_CORE.'access',
			PATH_CORE.'application',
			PATH_CORE.'serviceLocator',
			PATH_CORE.'flow',
			PATH_CORE.'utils',
		)
	)
	. PATH_SEPARATOR
);

?>
