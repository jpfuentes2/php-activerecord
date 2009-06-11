<?php
include 'helpers/config.php';

class RelationshipTest extends DatabaseTest
{
	protected $relationship_name;
	protected $relationship_names = array('has_many', 'belongs_to', 'has_one');

	public function setUp($connection_name=null)
	{
		parent::setUp($connection_name);
		Event::$belongs_to = array(array('venue'));
		Venue::$has_many = array(array('events'));
		Venue::$has_one = array();
		Employee::$has_one = array(array('position'));
		Host::$has_many = array(array('events'));

		foreach ($this->relationship_names as $name)
		{
			if (preg_match("/$name/", $this->getName(), $match))
				$this->relationship_name = $match[0];
		}
	}

	protected function get_relationship($type=null)
	{
		if (!$type)
			$type = $this->relationship_name;

		switch ($type)
		{
			case 'belongs_to';
				$ret = Event::find(5);
				break;
			case 'has_one';
				$ret = Employee::find(1);
				break;
			case 'has_many';
				$ret = Venue::find(2);
		}

		return $ret;
	}

	protected function assert_default_belongs_to($event, $association_name='venue')
	{
		$this->assertTrue($event->$association_name instanceof Venue);
		$this->assertEquals(5,$event->id);
		$this->assertEquals('West Chester',$event->$association_name->city);
		$this->assertEquals(6,$event->$association_name->id);
	}

	protected function assert_default_has_many($venue, $association_name='events')
	{
		$this->assertEquals(2,$venue->id);
		$this->assertTrue(count($venue->$association_name) > 1);
		$this->assertTrue($venue->{$association_name}[0] instanceof Event);
	}

	protected function assert_default_has_one($employee, $association_name='position')
	{
		$this->assertTrue($employee->$association_name instanceof Position);
		$this->assertNotNull($employee->id, $employee->$association_name->title);
	}

	public function test_belongs_to_basic()
	{
		$this->assert_default_belongs_to($this->get_relationship());
	}

	public function test_belongs_to_returns_null_when_no_record()
	{
		$event = Event::find(6);
		$this->assertNull($event->venue);
	}

	public function test_belongs_to_with_explicit_class_name()
	{
		Event::$belongs_to = array(array('explicit_class_name', 'class_name' => 'Venue'));
		$this->assert_default_belongs_to($this->get_relationship(), 'explicit_class_name');
	}

	public function test_belongs_to_with_select()
	{
		Event::$belongs_to[0]['select'] = 'id, city';
		$event = $this->get_relationship();
		$this->assert_default_belongs_to($event);

		try {
			$event->venue->name;
			$this->fail('expected Exception ActiveRecord\UndefinedPropertyException');
		} catch (ActiveRecord\UndefinedPropertyException $e) {
			$this->assertTrue(strpos($e->getMessage(), 'name') !== false);
		}
	}

	public function test_belongs_to_with_readonly()
	{
		Event::$belongs_to[0]['readonly'] = true;
		$event = $this->get_relationship();
		$this->assert_default_belongs_to($event);

		try {
			$event->venue->save();
			$this->fail('expected exception ActiveRecord\ReadonlyException');
		} catch (ActiveRecord\ReadonlyException $e) {
		}

		$event->venue->name = 'new name';
		$this->assertEquals($event->venue->name, 'new name');
	}

	public function test_belongs_to_with_plural_attribute_name()
	{
		Event::$belongs_to = array(array('venues', 'class_name' => 'Venue'));
		$this->assert_default_belongs_to($this->get_relationship(), 'venues');
	}

	public function test_belongs_to_with_conditions_and_non_qualifying_record()
	{
		Event::$belongs_to[0]['conditions'] = "state = 'NY'";
		$event = $this->get_relationship();
		$this->assertEquals(5,$event->id);
		$this->assertNull($event->venue);
	}

	public function test_belongs_to_with_conditions_and_qualifying_record()
	{
		Event::$belongs_to[0]['conditions'] = "state = 'PA'";
		$this->assert_default_belongs_to($this->get_relationship());
	}

	public function test_belongs_to_build_association()
	{
		$event = $this->get_relationship();
		$values = array('city' => 'Richmond', 'state' => 'VA');
		$venue = $event->build_venue($values);
		$this->assertEquals($values, array_intersect_key($values, $venue->attributes()));
	}

	public function test_belongs_to_create_association()
	{
		$event = $this->get_relationship();
		$values = array('city' => 'Richmond', 'state' => 'VA', 'name' => 'Club 54', 'address' => '123 street');
		$venue = $event->create_venue($values);
		$this->assertNotNull($venue->id);
	}

	public function test_has_many_basic()
	{
		$this->assert_default_has_many($this->get_relationship());
	}

	public function test_has_many_with_explicit_class_name()
	{
		Venue::$has_many = array(array('explicit_class_name', 'class_name' => 'Event'));;
		$this->assert_default_has_many($this->get_relationship(), 'explicit_class_name');
	}

	public function test_has_many_with_select()
	{
		Venue::$has_many[0]['select'] = 'title, type';
		$venue = $this->get_relationship();
		$this->assert_default_has_many($venue);

		try {
			$venue->events[0]->description;
			$this->fail('expected Exception ActiveRecord\UndefinedPropertyException');
		} catch (ActiveRecord\UndefinedPropertyException $e) {
			$this->assertTrue(strpos($e->getMessage(), 'description') !== false);
		}
	}

	public function test_has_many_with_readonly()
	{
		Venue::$has_many[0]['readonly'] = true;
		$venue = $this->get_relationship();
		$this->assert_default_has_many($venue);

		try {
			$venue->events[0]->save();
			$this->fail('expected exception ActiveRecord\ReadonlyException');
		} catch (ActiveRecord\ReadonlyException $e) {
		}

		$venue->events[0]->description = 'new desc';
		$this->assertEquals($venue->events[0]->description, 'new desc');
	}

	public function test_has_many_with_singular_attribute_name()
	{
		Venue::$has_many = array(array('event', 'class_name' => 'Event'));
		$this->assert_default_has_many($this->get_relationship(), 'event');
	}

	public function test_has_many_with_conditions_and_non_qualifying_record()
	{
		Venue::$has_many[0]['conditions'] = "title = 'pr0n @ railsconf'";
		$venue = $this->get_relationship();
		$this->assertEquals(2,$venue->id);
		$this->assertTrue(empty($venue->events), is_array($venue->events));
	}

	public function test_has_many_with_conditions_and_qualifying_record()
	{
		Venue::$has_many[0]['conditions'] = "title = 'Yeah Yeah Yeahs'";
		$venue = $this->get_relationship();
		$this->assertEquals(2,$venue->id);
		$this->assertEquals($venue->events[0]->title,'Yeah Yeah Yeahs');
	}

	public function test_has_many_through()
	{
		Event::$belongs_to = array(array('host'));
		Venue::$has_many[1] = array('hosts', 'through' => 'events');

		$venue = $this->get_relationship();
		$this->assertTrue(count($venue->hosts) > 0);
	}

	/**
	 * @expectedException ActiveRecord\Relationship\HasManyThroughAssociationException
	 */
	public function test_has_many_through_no_association()
	{
		Event::$belongs_to = array(array('host'));
		Venue::$has_many[1] = array('hosts', 'through' => 'blahhhhhhh');

		$venue = $this->get_relationship();
		$n = $venue->hosts;
		$this->assertTrue(count($n) > 0);
	}

	public function test_has_many_through_with_select()
	{
		Event::$belongs_to = array(array('host'));
		Venue::$has_many[1] = array('hosts', 'through' => 'events', 'select' => 'hosts.*, events.*');

		$venue = $this->get_relationship();
		$this->assertTrue(count($venue->hosts) > 0);
		$this->assertNotNull($venue->hosts[0]->title);
	}

	public function test_has_many_through_with_conditions()
	{
		Event::$belongs_to = array(array('host'));
		Venue::$has_many[1] = array('hosts', 'through' => 'events', 'conditions' => array('events.title != ?', 'Love Overboard'));

		$venue = $this->get_relationship();
		$this->assertTrue(count($venue->hosts) === 1);
		$this->assertTrue(strpos(ActiveRecord\Table::load('Host')->last_sql, "events.title !=") !== false);
	}

	public function test_has_many_through_using_source()
	{
		Event::$belongs_to = array(array('host'));
		Venue::$has_many[1] = array('hostess', 'through' => 'events', 'source' => 'host');

		$venue = $this->get_relationship();
		$this->assertTrue(count($venue->hostess) > 0);
	}

	/**
	 * @expectedException ActiveRecord\Relationship\HasManyThroughAssociationException
	 */
	public function test_has_many_through_with_invalid_class_name()
	{
		Event::$belongs_to = array(array('host'));
		Venue::$has_one = array(array('invalid_assoc'));
		Venue::$has_many[1] = array('hosts', 'through' => 'invalid_assoc');

		$this->get_relationship()->hosts;
	}

	public function test_has_one_basic()
	{
		$this->assert_default_has_one($this->get_relationship());
	}

	public function test_has_one_with_explicit_class_name()
	{
		Employee::$has_one = array(array('explicit_class_name', 'class_name' => 'Position'));
		$this->assert_default_has_one($this->get_relationship(), 'explicit_class_name');
	}

	public function test_has_one_with_select()
	{
		Employee::$has_one[0]['select'] = 'title';
		$employee = $this->get_relationship();
		$this->assert_default_has_one($employee);

		try {
			$employee->position->active;
			$this->fail('expected Exception ActiveRecord\UndefinedPropertyException');
		} catch (ActiveRecord\UndefinedPropertyException $e) {
			$this->assertTrue(strpos($e->getMessage(), 'active') !== false);
		}
	}

	public function test_has_one_with_order()
	{
		Employee::$has_one[0]['order'] = 'title';
		$employee = $this->get_relationship();
		$this->assert_default_has_one($employee);
		$this->assertTrue(strpos(Position::table()->last_sql, 'ORDER BY title') !== false);
	}

	public function test_has_one_with_conditions_and_non_qualifying_record()
	{
		Employee::$has_one[0]['conditions'] = "title = 'programmer'";
		$employee = $this->get_relationship();
		$this->assertEquals(1,$employee->id);
		$this->assertNull($employee->position);
	}

	public function test_has_one_with_conditions_and_qualifying_record()
	{
		Employee::$has_one[0]['conditions'] = "title = 'physicist'";
		$this->assert_default_has_one($this->get_relationship());
	}

	public function test_has_one_with_readonly()
	{
		Employee::$has_one[0]['readonly'] = true;
		$employee = $this->get_relationship();
		$this->assert_default_has_one($employee);

		try {
			$employee->position->save();
			$this->fail('expected exception ActiveRecord\ReadonlyException');
		} catch (ActiveRecord\ReadonlyException $e) {
		}

		$employee->position->title = 'new title';
		$this->assertEquals($employee->position->title, 'new title');
	}

/*	public function test_has_one_through()
	{


		Event::$belongs_to = array(array('host'));
		Venue::$has_many[1] = array('hosts', 'through' => 'events');

		$venue = $this->get_relationship();
		$this->assertTrue(count($venue->hosts) > 0);
	}*/
};
?>