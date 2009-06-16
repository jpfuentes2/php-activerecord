<?php
include 'helpers/config.php';

class ActiveRecordTest extends DatabaseTest
{
	public function set_up($connection_name=null)
	{
		parent::set_up($connection_name);
		$this->options = array('conditions' => 'blah', 'order' => 'blah');
	}

	public function test_options_is_not()
	{
		$this->assert_false(Author::is_options_hash(null));
		$this->assert_false(Author::is_options_hash(''));
		$this->assert_false(Author::is_options_hash('tito'));
		$this->assert_false(Author::is_options_hash(array()));
		$this->assert_false(Author::is_options_hash(array(1,2,3)));
	}

	/**
	 * @expectedException ActiveRecord\ActiveRecordException
	 */
	public function test_options_hash_with_unknown_keys() {
		$this->assert_false(Author::is_options_hash(array('conditions' => 'blah', 'sharks' => 'laserz', 'dubya' => 'bush')));
	}

	public function test_options_is_hash()
	{
		$this->assert_true(Author::is_options_hash($this->options));
	}

	public function test_extract_and_validate_options() {
		$args = array('first',$this->options);
		$this->assert_equals($this->options,Author::extract_and_validate_options($args));
		$this->assert_equals(array('first'),$args);
	}

	public function test_extract_and_validate_options_with_array_in_args() {
		$args = array('first',array(1,2),$this->options);
		$this->assert_equals($this->options,Author::extract_and_validate_options($args));
	}

	public function test_extract_and_validate_options_removes_options_hash() {
		$args = array('first',$this->options);
		Author::extract_and_validate_options($args);
		$this->assert_equals(array('first'),$args);
	}

	public function test_extract_and_validate_options_nope() {
		$args = array('first');
		$this->assert_equals(array(),Author::extract_and_validate_options($args));
		$this->assert_equals(array('first'),$args);
	}

	public function test_extract_and_validate_options_nope_because_wasnt_at_end() {
		$args = array('first',$this->options,array(1,2));
		$this->assert_equals(array(),Author::extract_and_validate_options($args));
	}

	/**
	 * @expectedException ActiveRecord\UndefinedPropertyException
	 */
	public function test_invalid_attribute()
	{
		$author = Author::find('first',array('conditions' => 'author_id=1'));
		$author->some_invalid_field_name;
	}

	public function test_invalid_attributes()
	{
		$book = Book::find(1);
		try {
			$book->update_attributes(array('name' => 'new name', 'invalid_attribute' => true , 'another_invalid_attribute' => 'something'));
		} catch (ActiveRecord\UndefinedPropertyException $e) {
			$exceptions = explode("\r\n", $e->getMessage());
		}

		$this->assert_equals(1, substr_count($exceptions[0], 'invalid_attribute'));
		$this->assert_equals(1, substr_count($exceptions[1], 'another_invalid_attribute'));
	}

	public function test_get_values_for()
	{
		$book = Book::find_by_name('Ancient Art of Main Tanking');
		$ret = $book->get_values_for(array('book_id','author_id'));
		$this->assert_equals(array('book_id','author_id'),array_keys($ret));
		$this->assert_equals(array(1,1),array_values($ret));
	}

	public function test_hyphenated_column_names_to_underscore()
	{
		$res = RmBldg::first();
		$this->assert_equals('name',$res->rm_name);
	}

	public function test_reload()
	{
		$venue = Venue::find(1);
		$this->assert_equals('NY', $venue->state);
		$venue->state = 'VA';
		$this->assert_equals('VA', $venue->state);
		$venue->reload();
		$this->assert_equals('NY', $venue->state);
	}

	public function test_active_record_model_home_not_set()
	{
		$home = ActiveRecord\Config::instance()->get_model_directory();
		ActiveRecord\Config::instance()->set_model_directory(__FILE__);
		$this->assert_equals(false,class_exists('TestAutoload'));

		ActiveRecord\Config::instance()->set_model_directory($home);
	}

	public function test_auto_load_with_namespaced_model()
	{
		$this->assert_true(class_exists('NamespaceTest\SomeModel'));
	}

	public function test_should_have_all_column_attributes_when_initializing_with_array()
	{
		$author = new Author(array('name' => 'Tito'));
		$this->assert_equals(array('author_id','parent_author_id','name','updated_at','created_at','some_date'),array_keys($author->attributes()));
	}

	public function test_defaults()
	{
		$author = new Author();
		$this->assert_equals('default_name',$author->name);
	}

	public function test_alias_attribute_getter()
	{
		$venue = Venue::find(1);
		$this->assert_equals($venue->marquee, $venue->name);
		$this->assert_equals($venue->mycity, $venue->city);
	}

	public function test_alias_attribute_setter()
	{
		$venue = Venue::find(1);
		$venue->marquee = 'new name';
		$this->assert_equals($venue->marquee, 'new name');
		$this->assert_equals($venue->marquee, $venue->name);

		$venue->name = 'another name';
		$this->assert_equals($venue->name, 'another name');
		$this->assert_equals($venue->marquee, $venue->name);
	}

	public function test_alias_from_mass_attributes()
	{
		$venue = new Venue(array('marquee' => 'meme', 'id' => 123));
		$this->assert_equals('meme',$venue->name);
		$this->assert_equals($venue->marquee,$venue->name);
	}

	public function test_attr_accessible()
	{
		$book = new BookAttrAccessible(array('name' => 'should not be set', 'author_id' => 1));
		$this->assert_null($book->name);
		$this->assert_equals(1,$book->author_id);
		$book->name = 'test';
		$this->assert_equals('test', $book->name);
	}

	public function test_attr_protected()
	{
		$book = new BookAttrAccessible(array('book_id' => 999));
		$this->assert_null($book->book_id);
		$book->book_id = 999;
		$this->assert_equals(999, $book->book_id);
	}

	public function test_isset()
	{
		$book = new Book();
		$this->assert_true(isset($book->name));
		$this->assert_false(isset($book->sharks));
	}

	public function test_readonly_only_halt_on_write_method()
	{
		$book = Book::first(array('readonly' => true));
		$this->assert_true($book->is_readonly());

		try {
			$book->save();
			$this-fail('expected exception ActiveRecord\ReadonlyException');
		} catch (ActiveRecord\ReadonlyException $e) {
		}

		$book->name = 'some new name';
		$this->assert_equals($book->name, 'some new name');
	}

	public function test_cast_when_using_setter()
	{
		$book = new Book();
		$book->book_id = '1';
		$this->assert_same(1,$book->book_id);
	}

	public function test_cast_when_loading()
	{
		$book = Book::find(1);
		$this->assert_same(1,$book->book_id);
		$this->assert_same('Ancient Art of Main Tanking',$book->name);
	}

	public function test_cast_defaults()
	{
		$book = new Book();
		$this->assert_same(0.0,$book->special);
	}

	public function test_transaction_committed()
	{
		$original = Author::count();
		Author::transaction(function() { Author::create(array("name" => "blah")); });
		$this->assert_equals($original+1,Author::count());
	}

	public function test_transaction_committed_when_returning_true()
	{
		$original = Author::count();
		Author::transaction(function() { Author::create(array("name" => "blah")); return true; });
		$this->assert_equals($original+1,Author::count());
	}

	public function test_transaction_rolledback_by_returning_false()
	{
		$original = Author::count();

		Author::transaction(function()
		{
			Author::create(array("name" => "blah"));
			return false;
		});

		$this->assert_equals($original,Author::count());
	}

	public function test_transaction_rolledback_by_throwing_exception()
	{
		$original = Author::count();
		$exception = null;

		try
		{
			Author::transaction(function()
			{
				Author::create(array("name" => "blah"));
				throw new Exception("blah");
			});
		}
		catch (Exception $e)
		{
			$exception = $e;
		}

		$this->assert_not_null($exception);
		$this->assert_equals($original,Author::count());
	}

	public function test_delegate()
	{
		$event = Event::first();
		$this->assert_equals($event->venue->state,$event->state);
		$this->assert_equals($event->venue->address,$event->address);
	}

	public function test_delegate_prefix()
	{
		$event = Event::first();
		$this->assert_equals($event->host->name,$event->woot_name);
	}

	public function test_delegate_returns_null_if_relationship_does_not_exist()
	{
		$event = new Event();
		$this->assert_null($event->state);
	}

	public function test_delegate_setter()
	{
		$event = Event::first();
		$event->state = 'MEXICO';
		$this->assert_equals('MEXICO',$event->venue->state);
	}

	public function test_table_name_with_underscores()
	{
		$this->assert_not_null(AwesomePerson::first());
	}
};
?>