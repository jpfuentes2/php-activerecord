<?php
include 'helpers/config.php';

class CallBackTest extends DatabaseTest
{
	private $test_closure;
	private $klass;
	private $fired = array();
	private static $instance;

	public static function instance()
	{
		return self::$instance;
	}

	public function setUp($connection_name=null)
	{
		parent::setUp($connection_name);

		self::$instance = $this;

		$this->fired = array();
		$this->test_closure = null;

/*
		//reset all call_backs on our model
		$this->klass = ActiveRecord\Reflections::instance()->add('VenueCB')->get('VenueCB');
		$call_backs = array_intersect_key(array_flip(ActiveRecord\CallBack::get_allowed_call_backs()), $this->klass->getStaticProperties());
		foreach ($call_backs as $call_back => $v)
			VenueCB::$$call_back = null;

		//set default closure
		$test_case = $this;
		$closure = function($record) use ($test_case) {
			$test_case->assertObjectHasAttribute('attributes', $record);
		};
		$this->set_test_closure($closure);

		//set call_back to the method name
		$default_call_back = str_replace('test_', '', $this->getName());
		if (array_key_exists($default_call_back, $this->klass->getStaticProperties()))
			$this->set_cb($default_call_back, $this->getName());
*/
	}

	public function set_test_closure($closure)
	{
		$this->test_closure = $closure;
	}

	public function set_cb($var, $definition)
	{
		VenueCB::$$var = $definition;
	}

	public function assert_fired($call_backs=array(), $unique=false)
	{
		if (!is_array($call_backs))
			$call_backs = array($call_backs);

		$fired = $unique === true ? array_unique($this->fired) : $this->fired;
print_r($this->fired);
		foreach ($call_backs as $cb)
			$this->assertTrue(in_array($cb, $fired));
	}

	public function assert_not_fired($call_backs=array())
	{
		if (!is_array($call_backs))
			$call_backs = array($call_backs);

		foreach ($call_backs as $cb)
			$this->assertFalse(in_array($cb, $this->fired));
	}

	public function assert_nothing_fired()
	{
		$this->assertTrue(empty($this->fired));
	}

	public function run_tests($record)
	{
		$back = debug_backtrace();
		$this->fired[] = $back[1]['function'];

		$this->assertNotNull($record);
		$this->assertType('ActiveRecord\Model', $record);

		if (!is_null($this->test_closure))
			call_user_func($this->test_closure, $record);
	}




	public function test_all_generic_call_back_methods()
	{
		$this->test_closure = null;
		$call_backs = ActiveRecord\CallBack::get_allowed_call_backs();
		$model = new VenueGenericCallBacks();
		$caller = new ActiveRecord\CallBack('VenueGenericCallBacks');

		foreach ($call_backs as $cb)
		{
			echo "$cb\n";
			$caller->send($model,$cb);
		}

		//array_unique b/c save is called multiple times as a wrapper for update/create
		$this->assertEquals(count($call_backs), count(array_unique($this->fired)));
	}

	public function test_call_back_not_fired_due_to_non_existent_method()
	{
		$venue = VenueCB::find(1);
		$this->assert_nothing_fired();
	}

	public function test_after_construct()
	{
		$venue = VenueCB::find(1);
		echo "===\n";
		print_r($this->fired);
		echo "===!\n";
		$this->assert_fired('test_after_construct');
/*
		foreach (array(1,2) as $key)
		{
			VenueCB::find($key);
			$this->assert_fired('test_after_construct', true);
		}
		*/
	}
/*
	public function test_validation_call_backs_not_fired_due_to_bypassing_validations()
	{
		foreach (array('save', 'insert', 'update') as $method)
		{
			$venue = new VenueCB;
			$venue->$method(false);
			$this->assert_nothing_fired();
		}
	}

	public function test_before_validation()
	{
		$test_case = $this;
		$closure = function($record) use ($test_case) {
			$test_case->assertObjectHasAttribute('attributes', $record);
			$test_case->assertTrue(is_null($record->errors));
		};
		$this->set_test_closure($closure);
		$this->set_cb('before_validation_on_create', 'test_before_validation_on_create');

		$venue = new VenueCB;
		$venue->save();
		$this->assert_fired(array('test_before_validation', 'test_before_validation_on_create'));

		$this->setUp();
		$this->set_cb('before_validation_on_update', 'test_before_validation_on_update');

		$venue = VenueCB::first();
		$venue->name = 'updated';
		$venue->save();
		$this->assert_fired(array('test_before_validation_on_update'));
	}

	public function test_before_validation_returns_false_cancels_call_backs()
	{
		$this->set_cb('before_validation', 'test_before_validation_returns_false_cancels_call_backs');
		$this->set_cb('after_validation', 'test_after_validation');

		$venue = new VenueCB;
		$venue->save();
		$this->assert_fired(array('test_before_validation_returns_false_cancels_call_backs'));
		$this->assert_not_fired(array('test_after_validation'));
	}

	public function test_after_validation()
	{
		$test_case = $this;
		$closure = function($record) use ($test_case) {
			$test_case->assertObjectHasAttribute('attributes', $record);
			$test_case->assertTrue($record->errors instanceof ActiveRecord\Errors);
		};
		$this->set_test_closure($closure);
		$this->set_cb('after_validation_on_create', 'test_after_validation_on_create');

		$venue = new VenueCB;
		$venue->save();
		$this->assert_fired(array('test_after_validation', 'test_after_validation_on_create'));

		$this->setUp();
		$this->set_cb('after_validation_on_update', 'test_after_validation_on_update');

		$venue = VenueCB::first();
		$venue->name = 'updated';
		$venue->save();
		$this->assert_fired(array('test_after_validation_on_update'));
	}

	public function test_before_update()
	{
		$this->set_cb('before_save', 'test_before_save');

		$venue = VenueCB::first();
		$venue->name = 'updated';
		$venue->save();
		$this->assert_fired(array('test_before_save', 'test_before_update'));
	}

	public function test_before_save_returns_false_cancels_call_backs()
	{
		$this->set_cb('before_save', 'test_before_save_returns_false_cancels_call_backs');
		$this->set_cb('before_create', 'test_before_create');

		$venue = new VenueCB;
		$venue->name = 'create';
		$venue->save();
		$this->assert_fired('test_before_save_returns_false_cancels_call_backs');
		$this->assert_not_fired('test_before_create');
	}

	public function test_before_create()
	{
		$this->set_cb('before_save', 'test_before_save');

		$venue = new VenueCB;
		$venue->name = 'create';
		$venue->save();
		$this->assert_fired(array('test_before_save', 'test_before_create'));
	}

	public function test_delete()
	{
		$this->set_cb('before_destroy', 'test_before_destroy');
		$this->set_cb('after_destroy', 'test_after_destroy');

		$venue = VenueCB::first();
		$venue->delete();
		$this->assert_fired(array('test_before_destroy', 'test_after_destroy'));

		$this->fired = array();
		$venues = VenueCB::all();
		foreach ($venues as $venue)
		{
			$venue->delete();
			$this->assert_fired(array('test_before_destroy', 'test_after_destroy'));
		}
	}
*/
};