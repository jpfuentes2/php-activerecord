<?php

class ActiveRecordTest extends DatabaseTest
{
	public function setUp($connection_name=null)
	{
		parent::setUp($connection_name);
		$this->options = array('conditions' => 'blah', 'order' => 'blah');
	}

	public function test_options_is_not()
	{
		$this->assertFalse(Author::is_options_hash(null));
		$this->assertFalse(Author::is_options_hash(''));
		$this->assertFalse(Author::is_options_hash('tito'));
		$this->assertFalse(Author::is_options_hash(array()));
		$this->assertFalse(Author::is_options_hash(array(1,2,3)));
	}

	/**
	 * @expectedException ActiveRecord\ActiveRecordException
	 */
	public function test_options_hash_with_unknown_keys() {
		$this->assertFalse(Author::is_options_hash(array('conditions' => 'blah', 'sharks' => 'laserz', 'dubya' => 'bush')));
	}

	public function test_options_is_hash()
	{
		$this->assertTrue(Author::is_options_hash($this->options));
	}

	public function test_extract_and_validate_options() {
		$args = array('first',$this->options);
		$this->assertEquals($this->options,Author::extract_and_validate_options($args));
		$this->assertEquals(array('first'),$args);
	}

	public function test_extract_and_validate_options_with_array_in_args() {
		$args = array('first',array(1,2),$this->options);
		$this->assertEquals($this->options,Author::extract_and_validate_options($args));
	}

	public function test_extract_and_validate_options_removes_options_hash() {
		$args = array('first',$this->options);
		Author::extract_and_validate_options($args);
		$this->assertEquals(array('first'),$args);
	}

	public function test_extract_and_validate_options_nope() {
		$args = array('first');
		$this->assertEquals(array(),Author::extract_and_validate_options($args));
		$this->assertEquals(array('first'),$args);
	}

	public function test_extract_and_validate_options_nope_because_wasnt_at_end() {
		$args = array('first',$this->options,array(1,2));
		$this->assertEquals(array(),Author::extract_and_validate_options($args));
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

		$this->assertEquals(1, substr_count($exceptions[0], 'invalid_attribute'));
		$this->assertEquals(1, substr_count($exceptions[1], 'another_invalid_attribute'));
	}

	public function test_getter_undefined_property_exception_includes_model_name()
	{
		$this->assertExceptionMessageContains("Author->this_better_not_exist",function()
		{
			$author = new Author();
			$author->this_better_not_exist;
		});
	}

	public function test_mass_assignment_undefined_property_exception_includes_model_name()
	{
		$this->assertExceptionMessageContains("Author->this_better_not_exist",function()
		{
			new Author(array("this_better_not_exist" => "hi"));
		});
	}

	public function test_setter_undefined_property_exception_includes_model_name()
	{
		$this->assertExceptionMessageContains("Author->this_better_not_exist",function()
		{
			$author = new Author();
			$author->this_better_not_exist = "hi";
		});
	}

	public function test_get_values_for()
	{
		$book = Book::find_by_name('Ancient Art of Main Tanking');
		$ret = $book->get_values_for(array('book_id','author_id'));
		$this->assertEquals(array('book_id','author_id'),array_keys($ret));
		$this->assertEquals(array(1,1),array_values($ret));
	}

	public function test_hyphenated_column_names_to_underscore()
	{
		if ($this->conn instanceof ActiveRecord\OciAdapter)
			return;

		$keys = array_keys(RmBldg::first()->attributes());
		$this->assertTrue(in_array('rm_name',$keys));
	}

	public function test_column_names_with_spaces()
	{
		if ($this->conn instanceof ActiveRecord\OciAdapter)
			return;

		$keys = array_keys(RmBldg::first()->attributes());
		$this->assertTrue(in_array('space_out',$keys));
	}

	public function test_mixed_case_column_name()
	{
		$keys = array_keys(Author::first()->attributes());
		$this->assertTrue(in_array('mixedcasefield',$keys));
	}

	public function test_mixed_case_primary_key_save()
	{
		$venue = Venue::find(1);
		$venue->name = 'should not throw exception';
		$venue->save();
		$this->assertEquals($venue->name,Venue::find(1)->name);
	}

	public function test_reload()
	{
		$venue = Venue::find(1);
		$this->assertEquals('NY', $venue->state);
		$venue->state = 'VA';
		$this->assertEquals('VA', $venue->state);
		$venue->reload();
		$this->assertEquals('NY', $venue->state);
	}
	
	public function test_reload_protected_attribute()
	{
		$book = BookAttrAccessible::find(1);
	
		$book->name = "Should not stay";
		$book->reload();
		$this->assertNotEquals("Should not stay", $book->name);
	}

	public function test_active_record_model_home_not_set()
	{
		$home = ActiveRecord\Config::instance()->get_model_directory();
		ActiveRecord\Config::instance()->set_model_directory(__DIR__);
		$this->assertEquals(false,class_exists('TestAutoload'));

		ActiveRecord\Config::instance()->set_model_directory($home);
	}

	public function test_auto_load_with_model_in_secondary_model_directory(){
		$home = ActiveRecord\Config::instance()->get_model_directory();
		ActiveRecord\Config::instance()->set_model_directories(array(
			realpath(__DIR__ . '/models'),
			realpath(__DIR__ . '/backup-models'),
		));
		$this->assertTrue(class_exists('Backup'));

		ActiveRecord\Config::instance()->set_model_directory($home);
	}

	public function test_auto_load_with_namespaced_model()
	{
		$this->assertTrue(class_exists('NamespaceTest\Book'));
	}

	public function test_auto_load_with_namespaced_model_in_secondary_model_directory(){
		$home = ActiveRecord\Config::instance()->get_model_directory();
		ActiveRecord\Config::instance()->set_model_directories(array(
			realpath(__DIR__ . '/models'),
			realpath(__DIR__ . '/backup-models'),
		));
		$this->assertTrue(class_exists('NamespaceTest\Backup'));

		ActiveRecord\Config::instance()->set_model_directory($home);
	}

	public function test_namespace_gets_stripped_from_table_name()
	{
		$model = new NamespaceTest\Book();
		$this->assertEquals('books',$model->table()->table);
	}

	public function test_namespace_gets_stripped_from_inferred_foreign_key()
	{
		$model = new NamespaceTest\Book();
		$table = ActiveRecord\Table::load(get_class($model));

		$this->assertEquals($table->get_relationship('parent_book')->foreign_key[0], 'book_id');
		$this->assertEquals($table->get_relationship('parent_book_2')->foreign_key[0], 'book_id');
		$this->assertEquals($table->get_relationship('parent_book_3')->foreign_key[0], 'book_id');
	}

	public function test_namespaced_relationship_associates_correctly()
	{
		$model = new NamespaceTest\Book();
		$table = ActiveRecord\Table::load(get_class($model));

		$this->assertNotNull($table->get_relationship('parent_book'));
		$this->assertNotNull($table->get_relationship('parent_book_2'));
		$this->assertNotNull($table->get_relationship('parent_book_3'));

		$this->assertNotNull($table->get_relationship('pages'));
		$this->assertNotNull($table->get_relationship('pages_2'));

		$this->assertNull($table->get_relationship('parent_book_4'));
		$this->assertNull($table->get_relationship('pages_3'));

		// Should refer to the same class
		$this->assertSame(
			ltrim($table->get_relationship('parent_book')->class_name, '\\'),
			ltrim($table->get_relationship('parent_book_2')->class_name, '\\')
		);

		// Should refer to different classes
		$this->assertNotSame(
			ltrim($table->get_relationship('parent_book_2')->class_name, '\\'),
			ltrim($table->get_relationship('parent_book_3')->class_name, '\\')
		);

		// Should refer to the same class
		$this->assertSame(
			ltrim($table->get_relationship('pages')->class_name, '\\'),
			ltrim($table->get_relationship('pages_2')->class_name, '\\')
		);
	}

	public function test_should_have_all_column_attributes_when_initializing_with_array()
	{
		$author = new Author(array('name' => 'Tito'));
		$this->assertTrue(count(array_keys($author->attributes())) >= 9);
	}

	public function test_defaults()
	{
		$author = new Author();
		$this->assertEquals('default_name',$author->name);
	}

	public function test_alias_attribute_getter()
	{
		$venue = Venue::find(1);
		$this->assertEquals($venue->marquee, $venue->name);
		$this->assertEquals($venue->mycity, $venue->city);
	}

	public function test_alias_attribute_setter()
	{
		$venue = Venue::find(1);
		$venue->marquee = 'new name';
		$this->assertEquals($venue->marquee, 'new name');
		$this->assertEquals($venue->marquee, $venue->name);

		$venue->name = 'another name';
		$this->assertEquals($venue->name, 'another name');
		$this->assertEquals($venue->marquee, $venue->name);
	}

	public function test_alias_attribute_custom_setter()
	{
		Venue::$use_custom_set_city_setter = true;
		$venue = Venue::find(1);

		$venue->mycity = 'cityname';
		$this->assertEquals($venue->mycity, 'cityname#');
		$this->assertEquals($venue->mycity, $venue->city);

		$venue->city = 'anothercity';
		$this->assertEquals($venue->city, 'anothercity#');
		$this->assertEquals($venue->city, $venue->mycity);

		Venue::$use_custom_set_city_setter = false;
	}

	public function test_alias_from_mass_attributes()
	{
		$venue = new Venue(array('marquee' => 'meme', 'id' => 123));
		$this->assertEquals('meme',$venue->name);
		$this->assertEquals($venue->marquee,$venue->name);
	}

	public function test_gh18_isset_on_aliased_attribute()
	{
		$this->assertTrue(isset(Venue::first()->marquee));
	}

	public function test_attr_accessible()
	{
		$book = new BookAttrAccessible(array('name' => 'should not be set', 'author_id' => 1));
		$this->assertNull($book->name);
		$this->assertEquals(1,$book->author_id);
		$book->name = 'test';
		$this->assertEquals('test', $book->name);
	}

	public function test_attr_protected()
	{
		$book = new BookAttrAccessible(array('book_id' => 999));
		$this->assertNull($book->book_id);
		$book->book_id = 999;
		$this->assertEquals(999, $book->book_id);
	}

	public function test_isset()
	{
		$book = new Book();
		$this->assertTrue(isset($book->name));
		$this->assertFalse(isset($book->sharks));
	}

	public function test_readonly_only_halt_on_write_method()
	{
		$book = Book::first(array('readonly' => true));
		$this->assertTrue($book->is_readonly());

		try {
			$book->save();
			$this->fail('expected exception ActiveRecord\ReadonlyException');
		} catch (ActiveRecord\ReadonlyException $e) {
		}

		$book->name = 'some new name';
		$this->assertEquals($book->name, 'some new name');
	}

	public function test_cast_when_using_setter()
	{
		$book = new Book();
		$book->book_id = '1';
		$this->assertSame(1,$book->book_id);
	}

	public function test_cast_when_loading()
	{
		$book = Book::find(1);
		$this->assertSame(1,$book->book_id);
		$this->assertSame('Ancient Art of Main Tanking',$book->name);
	}

	public function test_cast_defaults()
	{
		$book = new Book();
		$this->assertSame(0.0,$book->special);
	}

	public function test_transaction_committed()
	{
		$original = Author::count();
		$ret = Author::transaction(function() { Author::create(array("name" => "blah")); });
		$this->assertEquals($original+1,Author::count());
		$this->assertTrue($ret);
	}
	
	public function test_transaction_committed_when_returning_true()
	{
		$original = Author::count();
		$ret = Author::transaction(function() { Author::create(array("name" => "blah")); return true; });
		$this->assertEquals($original+1,Author::count());
		$this->assertTrue($ret);
	}
	
	public function test_transaction_rolledback_by_returning_false()
	{
		$original = Author::count();
		
		$ret = Author::transaction(function()
		{
			Author::create(array("name" => "blah"));
			return false;
		});
		
		$this->assertEquals($original,Author::count());
		$this->assertFalse($ret);
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

		$this->assertNotNull($exception);
		$this->assertEquals($original,Author::count());
	}

	public function test_delegate()
	{
		$event = Event::first();
		$this->assertEquals($event->venue->state,$event->state);
		$this->assertEquals($event->venue->address,$event->address);
	}

	public function test_delegate_prefix()
	{
		$event = Event::first();
		$this->assertEquals($event->host->name,$event->woot_name);
	}

	public function test_delegate_returns_null_if_relationship_does_not_exist()
	{
		$event = new Event();
		$this->assertNull($event->state);
	}

	public function test_delegate_set_attribute()
	{
		$event = Event::first();
		$event->state = 'MEXICO';
		$this->assertEquals('MEXICO',$event->venue->state);
	}

	public function test_delegate_getter_gh_98()
	{
		Venue::$use_custom_get_state_getter = true;

		$event = Event::first();
		$this->assertEquals('ny', $event->venue->state);
		$this->assertEquals('ny', $event->state);

		Venue::$use_custom_get_state_getter = false;
	}

	public function test_delegate_setter_gh_98()
	{
		Venue::$use_custom_set_state_setter = true;

		$event = Event::first();
		$event->state = 'MEXICO';
		$this->assertEquals('MEXICO#',$event->venue->state);

		Venue::$use_custom_set_state_setter = false;
	}

	public function test_table_name_with_underscores()
	{
		$this->assertNotNull(AwesomePerson::first());
	}

	public function test_model_should_default_as_new_record()
	{
		$author = new Author();
		$this->assertTrue($author->is_new_record());
	}

	public function test_setter()
	{
		$author = new Author();
		$author->password = 'plaintext';
		$this->assertEquals(md5('plaintext'),$author->encrypted_password);
	}

	public function test_setter_with_same_name_as_an_attribute()
	{
		$author = new Author();
		$author->name = 'bob';
		$this->assertEquals('BOB',$author->name);
	}

	public function test_getter()
	{
		$book = Book::first();
		$this->assertEquals(strtoupper($book->name), $book->upper_name);
	}

	public function test_getter_with_same_name_as_an_attribute()
	{
		Book::$use_custom_get_name_getter = true;
		$book = new Book;
		$book->name = 'bob';
		$this->assertEquals('BOB', $book->name);
		Book::$use_custom_get_name_getter = false;
	}

	public function test_setting_invalid_date_should_set_date_to_null()
	{
		$author = new Author();
		$author->created_at = 'CURRENT_TIMESTAMP';
		$this->assertNull($author->created_at);
	}

	public function test_table_name()
	{
		$this->assertEquals('authors',Author::table_name());
	}

	/**
	 * @expectedException ActiveRecord\ActiveRecordException
	 */
	public function test_undefined_instance_method()
	{
		Author::first()->find_by_name('sdf');
	}

	public function test_clear_cache_for_specific_class()
	{
		$book_table1 = ActiveRecord\Table::load('Book');
		$book_table2 = ActiveRecord\Table::load('Book');
		ActiveRecord\Table::clear_cache('Book');
		$book_table3 = ActiveRecord\Table::load('Book');

		$this->assertTrue($book_table1 === $book_table2);
		$this->assertTrue($book_table1 !== $book_table3);
	}

	public function test_flag_dirty()
	{
		$author = new Author();
		$author->flag_dirty('some_date');
		$this->assertHasKeys('some_date', $author->dirty_attributes());
		$this->assertTrue($author->attribute_is_dirty('some_date'));
		$author->save();
		$this->assertFalse($author->attribute_is_dirty('some_date'));
	}

	public function test_flag_dirty_attribute_which_does_not_exit()
	{
		$author = new Author();
		$author->flag_dirty('some_inexistant_property');
		$this->assertNull($author->dirty_attributes());
		$this->assertFalse($author->attribute_is_dirty('some_inexistant_property'));
	}

	public function test_gh245_dirty_attribute_should_not_raise_php_notice_if_not_dirty()
	{
		$event = new Event(array('title' => "Fun"));
		$this->assertFalse($event->attribute_is_dirty('description'));
		$this->assertTrue($event->attribute_is_dirty('title'));
	}

	public function test_attribute_is_not_flagged_dirty_if_assigning_same_value() {
		$event = Event::find(1);
		$event->type = "Music";
		$this->assertFalse($event->attribute_is_dirty('type'));
	}

	public function test_changed_attributes() {
		$event = Event::find(1);

		$event->type = "Groovy Music";
		$changed_attributes = $event->changed_attributes();
		$this->assertTrue(is_array($changed_attributes));
		$this->assertEquals(1, count($changed_attributes));
		$this->assertTrue(isset($changed_attributes['type']));
		$this->assertEquals("Music", $changed_attributes['type']);

		$event->type = "Funky Music";
		$changed_attributes = $event->changed_attributes();
		$this->assertTrue(is_array($changed_attributes));
		$this->assertEquals(1, count($changed_attributes));
		$this->assertTrue(isset($changed_attributes['type']));
		$this->assertEquals("Music", $changed_attributes['type']);
	}

	public function test_changes() {
		$event = Event::find(1);

		$event->type = "Groovy Music";
		$changes = $event->changes();
		$this->assertTrue(is_array($changes));
		$this->assertEquals(1, count($changes));
		$this->assertTrue(isset($changes['type']));
		$this->assertTrue(is_array($changes['type']));
		$this->assertEquals("Music", $changes['type'][0]);
		$this->assertEquals("Groovy Music", $changes['type'][1]);

		$event->type = "Funky Music";
		$changes = $event->changes();
		$this->assertTrue(is_array($changes));
		$this->assertEquals(1, count($changes));
		$this->assertTrue(isset($changes['type']));
		$this->assertTrue(is_array($changes['type']));
		$this->assertEquals("Music", $changes['type'][0]);
		$this->assertEquals("Funky Music", $changes['type'][1]);
	}

	public function test_attribute_was() {
		$event = Event::find(1);
		$event->type = "Funky Music";
		$this->assertEquals("Music", $event->attribute_was("type"));
		$event->type = "Groovy Music";
		$this->assertEquals("Music", $event->attribute_was("type"));
	}

	public function test_previous_changes() {
		$event = Event::find(1);
		$event->type = "Groovy Music";
		$previous_changes = $event->previous_changes();
		$this->assertTrue(empty($previous_changes));
		$event->save();
		$previous_changes = $event->previous_changes();
		$this->assertTrue(is_array($previous_changes));
		$this->assertEquals(1, count($previous_changes));
		$this->assertTrue(isset($previous_changes['type']));
		$this->assertTrue(is_array($previous_changes['type']));
		$this->assertEquals("Music", $previous_changes['type'][0]);
		$this->assertEquals("Groovy Music", $previous_changes['type'][1]);
	}

	public function test_save_resets_changed_attributes() {
		$event = Event::find(1);
		$event->type = "Groovy Music";
		$event->save();
		$changed_attributes = $event->changed_attributes();
		$this->assertTrue(empty($changed_attributes));
	}

	public function test_changing_datetime_attribute_tracks_change() {
		$author = new Author();
		$author->created_at = $original = new \DateTime("yesterday");
		$author->created_at = $now = new \DateTime();
		$changes = $author->changes();
		$this->assertTrue(isset($changes['created_at']));
		$this->assertDateTimeEquals($original, $changes['created_at'][0]);
		$this->assertDateTimeEquals($now, $changes['created_at'][1]);
	}

	public function test_changing_empty_attribute_value_tracks_change() {
		$event = new Event();
		$event->description = "The most fun";
		$changes = $event->changes();
		$this->assertTrue(array_key_exists("description", $changes));
		$this->assertEquals("", $changes['description'][0]);
		$this->assertEquals("The most fun", $changes['description'][1]);
	}

	public function test_assigning_php_datetime_gets_converted_to_date_class_with_defaults()
	{
		$author = new Author();
		$author->created_at = $now = new \DateTime();
		$this->assertIsA("ActiveRecord\\DateTime", $author->created_at);
		$this->assertDateTimeEquals($now,$author->created_at);
	}

	public function test_assigning_php_datetime_gets_converted_to_date_class_with_custom_date_class()
	{
		ActiveRecord\Config::instance()->set_date_class('\\DateTime'); // use PHP built-in DateTime
		$author = new Author();
		$author->created_at = $now = new \DateTime();
		$this->assertIsA("DateTime", $author->created_at);
		$this->assertDateTimeEquals($now,$author->created_at);
	}

	public function test_assigning_from_mass_assignment_php_datetime_gets_converted_to_ar_datetime()
	{
		$author = new Author(array('created_at' => new \DateTime()));
		$this->assertIsA("ActiveRecord\\DateTime",$author->created_at);
	}

	public function test_get_real_attribute_name()
	{
		$venue = new Venue();
		$this->assertEquals('name', $venue->get_real_attribute_name('name'));
		$this->assertEquals('name', $venue->get_real_attribute_name('marquee'));
		$this->assertEquals(null, $venue->get_real_attribute_name('invalid_field'));
	}

	public function test_id_setter_works_with_table_without_pk_named_attribute()
	{
		$author = new Author(array('id' => 123));
		$this->assertEquals(123,$author->author_id);
	}

	public function test_query()
	{
		$row = Author::query('SELECT COUNT(*) AS n FROM authors',null)->fetch();
		$this->assertTrue($row['n'] > 1);

		$row = Author::query('SELECT COUNT(*) AS n FROM authors WHERE name=?',array('Tito'))->fetch();
		$this->assertEquals(array('n' => 1), $row);
	}
}
