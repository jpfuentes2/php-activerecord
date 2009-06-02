<?php
include 'helpers/config.php';

class ActiveRecordTest extends DatabaseTest
{
	public function setUp($connection_name=null)
	{
		parent::setUp($connection_name);
		$this->options = array('conditions' => 'blah', 'order' => 'blah');
	}

	public function testOptionsIsNot()
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
	public function testOptionsHashWithUnknownKeys() {
		$this->assertFalse(Author::is_options_hash(array('conditions' => 'blah', 'sharks' => 'laserz', 'dubya' => 'bush')));
	}

	public function testOptionsIsHash()
	{
		$this->assertTrue(Author::is_options_hash($this->options));
	}

	public function testExtractAndValidateOptions() {
		$args = array('first',$this->options);
		$this->assertEquals($this->options,Author::extract_and_validate_options($args));
		$this->assertEquals(array('first'),$args);
	}

	public function testExtractAndValidateOptionsWithArrayInArgs() {
		$args = array('first',array(1,2),$this->options);
		$this->assertEquals($this->options,Author::extract_and_validate_options($args));
	}

	public function testExtractAndValidateOptionsRemovesOptionsHash() {
		$args = array('first',$this->options);
		Author::extract_and_validate_options($args);
		$this->assertEquals(array('first'),$args);
	}

	public function testExtractAndValidateOptionsNope() {
		$args = array('first');
		$this->assertEquals(array(),Author::extract_and_validate_options($args));
		$this->assertEquals(array('first'),$args);
	}

	public function testExtractAndValidateOptionsNopeBecauseWasntAtEnd() {
		$args = array('first',$this->options,array(1,2));
		$this->assertEquals(array(),Author::extract_and_validate_options($args));
	}

	/**
	 * @expectedException ActiveRecord\UndefinedPropertyException
	 */
	public function testInvalidAttribute()
	{
		$author = Author::find('first',array('conditions' => 'author_id=1'));
		$author->some_invalid_field_name;
	}

	public function testInvalidAttributes()
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

	public function testGetValuesFor()
	{
		$book = Book::find_by_name('Ancient Art of Main Tanking');
		$ret = $book->get_values_for(array('book_id','author_id'));
		$this->assertEquals(array('book_id','author_id'),array_keys($ret));
		$this->assertEquals(array(1,1),array_values($ret));
	}

	public function testHyphenatedColumnNamesToUnderscore()
	{
		$res = RmBldg::first();
		$this->assertEquals('name',$res->rm_name);
	}

	public function testReload()
	{
		$venue = Venue::find(1);
		$this->assertEquals('NY', $venue->state);
		$venue->state = 'VA';
		$this->assertEquals('VA', $venue->state);
		$venue->reload();
		$this->assertEquals('NY', $venue->state);
	}

	public function testActiveRecordModelHomeNotSet()
	{
		$home = ActiveRecord\Config::instance()->get_model_directory();
		ActiveRecord\Config::instance()->set_model_directory(__FILE__);
		$this->assertEquals(false,class_exists('TestAutoload'));

		ActiveRecord\Config::instance()->set_model_directory($home);
	}

	public function testAutoLoadWithNamespacedModel()
	{
		$this->assertTrue(class_exists('NamespaceTest\SomeModel'));
	}

	public function testShouldHaveAllColumnAttributesWhenInitializingWithArray()
	{
		$author = new Author(array('name' => 'Tito'));
		$this->assertEquals(array('author_id','parent_author_id','name','updated_at','created_at','some_date'),array_keys($author->attributes()));
	}

	public function testDefaults()
	{
		$author = new Author();
		$this->assertEquals('default_name',$author->name);
	}

	public function testAliasAttributeGetter()
	{
		$venue = Venue::find(1);
		$this->assertEquals($venue->marquee, $venue->name);
		$this->assertEquals($venue->mycity, $venue->city);
	}

	public function testAliasAttributeSetter()
	{
		$venue = Venue::find(1);
		$venue->marquee = 'new name';
		$this->assertEquals($venue->marquee, 'new name');
		$this->assertEquals($venue->marquee, $venue->name);

		$venue->name = 'another name';
		$this->assertEquals($venue->name, 'another name');
		$this->assertEquals($venue->marquee, $venue->name);
	}

	public function testAliasFromMassAttributes()
	{
		$venue = new Venue(array('marquee' => 'meme', 'id' => 123));
		$this->assertEquals('meme',$venue->name);
		$this->assertEquals($venue->marquee,$venue->name);
	}

	public function testAttrAccessible()
	{
		$book = new BookAttrAccessible(array('name' => 'should not be set', 'author_id' => 1));
		$this->assertNull($book->name);
		$this->assertEquals(1,$book->author_id);
		$book->name = 'test';
		$this->assertEquals('test', $book->name);
	}

	public function testAttrProtected()
	{
		$book = new BookAttrAccessible(array('book_id' => 999));
		$this->assertNull($book->book_id);
		$book->book_id = 999;
		$this->assertEquals(999, $book->book_id);
	}

	public function testIsset()
	{
		$book = new Book();
		$this->assertTrue(isset($book->name));
		$this->assertFalse(isset($book->sharks));
	}

	public function testReadonlyOnlyHaltOnWriteMethod()
	{
		$book = Book::first(array('readonly' => true));
		$this->assertTrue($book->is_readonly());

		foreach (array('insert','update', 'save') as $method)
		{
			try {
				$book->$method();
				$this-fail('expected exception ActiveRecord\ReadonlyException');
			} catch (ActiveRecord\ReadonlyException $e) {
				;
			}
		}

		$book->name = 'some new name';
		$this->assertEquals($book->name, 'some new name');
	}

	public function testCastWhenUsingSetter()
	{
		$book = new Book();
		$book->book_id = '1';
		$this->assertSame(1,$book->book_id);
	}

	public function testCastWhenLoading()
	{
		$book = Book::find(1);
		$this->assertSame(1,$book->book_id);
		$this->assertSame('Ancient Art of Main Tanking',$book->name);
	}

	public function testCastDefaults()
	{
		$book = new Book();
		$this->assertSame(0.0,$book->special);
	}

	public function testTransactionCommitted()
	{
		$original = Author::count();
		Author::transaction(function() { Author::create(array("name" => "blah")); });
		$this->assertEquals($original+1,Author::count());
	}

	public function testTransactionCommittedWhenReturningTrue()
	{
		$original = Author::count();
		Author::transaction(function() { Author::create(array("name" => "blah")); return true; });
		$this->assertEquals($original+1,Author::count());
	}
	
	public function testTransactionRolledbackByReturningFalse()
	{
		$original = Author::count();

		Author::transaction(function()
		{
			Author::create(array("name" => "blah"));
			return false;
		});

		$this->assertEquals($original,Author::count());
	}
	
	// TODO this doesn't work for some reason
	// TODO the exception is not being caught from within Model::transaction
	public function testTransactionRolledbackByThrowingException()
	{
		/*
		$original = Author::count();

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
		}

		$this->assertEquals($original,Author::count());
		*/
	}
};
?>