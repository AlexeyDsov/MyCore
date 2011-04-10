<?php
define('PATH_CORE', dirname(__FILE__).DS);

ini_set(
	'include_path',
	get_include_path()

	.PATH_CORE.'ListMakerHelper'.PATH_SEPARATOR
	.PATH_CORE.'application'.PATH_SEPARATOR
	.PATH_CORE.'serviceLocator'.PATH_SEPARATOR
	.PATH_CORE.'utils'.PATH_SEPARATOR
);

?>
