<?php
if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50300)
	die('PHP ActiveRecord requires PHP 5.3 or higher');

define('PHP_ACTIVERECORD_VERSION_ID','1.0');

if (!defined('PHP_ACTIVERECORD_AUTOLOAD_PREPEND'))
	define('PHP_ACTIVERECORD_AUTOLOAD_PREPEND',true);

require 'lib/Singleton.php';
require 'lib/Config.php';
require 'lib/Utils.php';
require 'lib/DateTime.php';
require 'lib/Model.php';
require 'lib/Table.php';
require 'lib/ConnectionManager.php';
require 'lib/Connection.php';
require 'lib/SQLBuilder.php';
require 'lib/Reflections.php';
require 'lib/Inflector.php';
require 'lib/CallBack.php';
require 'lib/Exceptions.php';
require 'lib/Cache.php';

if (!defined('PHP_ACTIVERECORD_AUTOLOAD_DISABLE'))
	spl_autoload_register('activerecord_autoload',false,PHP_ACTIVERECORD_AUTOLOAD_PREPEND);

function activerecord_autoload($class_name)
{
$paths = ActiveRecord\Config::instance()->get_model_directories();
	
	//within each model directory, look for the model.
	foreach((array)$paths as $path)
	{
		$root = realpath($path);
		
		//namespace within the $class_name? if so, look in subdir based on namespace
		if (($namespaces = ActiveRecord\get_namespaces($class_name)))
		{
			$class_name = array_pop($namespaces);
			$directories = array();
			foreach ($namespaces as $directory)
				$directories[] = $directory;

			$root .= DIRECTORY_SEPARATOR . implode($directories, DIRECTORY_SEPARATOR);
		}

		//if file exists, include it.
		$file = "$root/$class_name.php";
		
		if (file_exists($file))
		{
			require $file;
			if(class_exists($class_name, false)) break; //make double-sure the file actually contains the class we're after
		}

	}
}
