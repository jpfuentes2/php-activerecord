<?php
require_once 'Log.php';
require_once 'PHPUnit/Framework/TestCase.php';
require_once 'SnakeCase_PHPUnit_Framework_TestCase.php';
require_once 'DatabaseTest.php';
require_once 'AdapterTest.php';
require_once dirname(__FILE__) . '/../../ActiveRecord.php';

// whether or not to run the slow non-crucial tests
$GLOBALS['slow_tests'] = false;

if (getenv('LOG') !== 'false')
	DatabaseTest::$log = true;

ActiveRecord\Config::initialize(function($cfg)
{
	$cfg->set_model_directory(realpath(dirname(__FILE__) . '/../models'));
	$cfg->set_connections(array(
		'mysql'		=> getenv('PHPAR_MYSQL') ? getenv('PHPAR_MYSQL') : 'mysql://test:test@127.0.0.1/test',
		'pgsql'		=> getenv('PHPAR_PGSQL') ? getenv('PHPAR_PGSQL') : 'pgsql://test:test@127.0.0.1/test',
		'oci'		=> getenv('PHPAR_OCI') ? getenv('PHPAR_OCI') : 'oci://test:test@127.0.0.1/dev',
		'sqlite'	=> getenv('PHPAR_SQLITE') ? getenv('PHPAR_SQLITE') : 'sqlite://test.db'));

	$cfg->set_default_connection('mysql');

	for ($i=0; $i<count($GLOBALS['argv']); ++$i)
	{
		if ($GLOBALS['argv'][$i] == '--adapter')
			$cfg->set_default_connection($GLOBALS['argv'][$i+1]);
		elseif ($GLOBALS['argv'][$i] == '--slow-tests')
			$GLOBALS['slow_tests'] = true;
	}

	$logger = Log::singleton('file', dirname(__FILE__) . '/../log/query.log','ident',array('mode' => 0664, 'timeFormat' =>  '%Y-%m-%d %H:%M:%S'));

	$cfg->set_logging(true);
	$cfg->set_logger($logger);
});

error_reporting(E_ALL | E_STRICT);
?>
