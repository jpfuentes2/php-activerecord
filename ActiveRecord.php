<?php
if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50300)
	die('PHP ActiveRecord requires PHP 5.3 or higher');

define('PHP_ACTIVERECORD_VERSION_ID','1.0');

if (!defined('PHP_ACTIVERECORD_AUTOLOAD_PREPEND'))
	define('PHP_ACTIVERECORD_AUTOLOAD_PREPEND',true);

require 'lib/SpeedyPHP/ActiveRecord/Singleton.php';
require 'lib/SpeedyPHP/ActiveRecord/Config.php';
require 'lib/SpeedyPHP/ActiveRecord/Utils.php';
require 'lib/SpeedyPHP/ActiveRecord/DateTime.php';
require 'lib/SpeedyPHP/ActiveRecord/Model.php';
require 'lib/SpeedyPHP/ActiveRecord/Table.php';
require 'lib/SpeedyPHP/ActiveRecord/ConnectionManager.php';
require 'lib/SpeedyPHP/ActiveRecord/Connection.php';
require 'lib/SpeedyPHP/ActiveRecord/SQLBuilder.php';
require 'lib/SpeedyPHP/ActiveRecord/Reflections.php';
require 'lib/SpeedyPHP/ActiveRecord/Inflector.php';
require 'lib/SpeedyPHP/ActiveRecord/CallBack.php';
require 'lib/SpeedyPHP/ActiveRecord/Exceptions.php';
require 'lib/SpeedyPHP/ActiveRecord/Cache.php';

if (!defined('PHP_ACTIVERECORD_AUTOLOAD_DISABLE'))
	spl_autoload_register('activerecord_autoload',false,PHP_ACTIVERECORD_AUTOLOAD_PREPEND);

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

		$root .= DIRECTORY_SEPARATOR . implode($directories, DIRECTORY_SEPARATOR);
	}

	$file = "$root/$class_name.php";

	if (file_exists($file))
		require $file;
}
?>
