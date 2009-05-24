<?php
require_once 'PHPUnit/Framework.php';
require_once 'helpers/config.php';

foreach (glob('Validates*Test.php') as $file)
	require $file;

class AllValidationsTests extends DatabaseTest
{
	public static function suite()
	{
		$suite = new PHPUnit_Framework_TestSuite('PHPUnit');

		foreach (glob('Validates*Test.php') as $file)
			$suite->addTestSuite(substr($file,0,-4));

		return $suite;
	}
};
?>