<?php
if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50300)
	die('PHP ActiveRecord requires PHP 5.3 or higher');

define('PHP_ACTIVERECORD_VERSION_ID','1.0');

if (!defined('PHP_ACTIVERECORD_AUTOLOAD_PREPEND'))
	define('PHP_ACTIVERECORD_AUTOLOAD_PREPEND',true);

require __DIR__.'/lib/Singleton.php';
require __DIR__.'/lib/Config.php';
require __DIR__.'/lib/Utils.php';
require __DIR__.'/lib/DateTime.php';
require __DIR__.'/lib/Model.php';
require __DIR__.'/lib/Table.php';
require __DIR__.'/lib/ConnectionManager.php';
require __DIR__.'/lib/Connection.php';
require __DIR__.'/lib/Serialization.php';
require __DIR__.'/lib/SQLBuilder.php';
require __DIR__.'/lib/Reflections.php';
require __DIR__.'/lib/Inflector.php';
require __DIR__.'/lib/CallBack.php';
require __DIR__.'/lib/Exceptions.php';
require __DIR__.'/lib/Cache.php';

if (!defined('PHP_ACTIVERECORD_AUTOLOAD_DISABLE'))
	spl_autoload_register('activerecord_autoload',false,PHP_ACTIVERECORD_AUTOLOAD_PREPEND);

function activerecord_autoload($class_name)
{
	$paths = ActiveRecord\Config::instance()->get_model_directories();
	$namespace_directory = '';
	if (($namespaces = ActiveRecord\get_namespaces($class_name)))
	{
		$class_name = array_pop($namespaces);
		$directories = array();

		foreach ($namespaces as $directory)
			$directories[] = $directory;

		$namespace_directory = DIRECTORY_SEPARATOR . implode($directories, DIRECTORY_SEPARATOR);
	}
	$paths = count($paths) ? $paths : array('.');
	
	foreach($paths as $path)
	{
		$root = realpath($path);
		$file = "{$root}{$namespace_directory}/{$class_name}.php";
		
		if (file_exists($file))
		{
			require $file;
			return;
		}
	}
}
