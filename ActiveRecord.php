<?php
if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50300)
	die('PHP ActiveRecord requires PHP 5.3 or higher');

require_once 'lib/Singleton.php';
require_once 'lib/Config.php';
require_once 'lib/Model.php';
require_once 'lib/Utils.php';
require_once 'lib/Exceptions.php';
require_once 'lib/ConnectionManager.php';
require_once 'lib/Connection.php';
require_once 'lib/SQLBuilder.php';
require_once 'lib/Table.php';
require_once 'lib/Inflector.php';
require_once 'lib/Validations.php';
require_once 'lib/Serialization.php';
require_once 'lib/Reflections.php';
require_once 'lib/CallBack.php';

spl_autoload_register('activerecord_autoload');

function activerecord_autoload($class_name)
{
	$path = ActiveRecord\Config::instance()->get_model_directory();
	$root = realpath(isset($path) ? $path : '.');

	if (($namespaces = ActiveRecord\get_namespaces($class_name)))
	{
		$class_name = array_pop($namespaces);
		$directories = array();
		foreach ($namespaces as $directory)
			$directories[] = $directory;

		$root .= DIRECTORY_SEPARATOR .implode($directories, DIRECTORY_SEPARATOR);
	}

	$file = "$root/$class_name.php";

	if (file_exists($file))
		@include_once $file;
}
?>
