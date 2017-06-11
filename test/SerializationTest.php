<?php
require_once __DIR__ . '/../lib/Serialization.php';

use ActiveRecord\DateTime;

class SerializationTest extends DatabaseTest
{
	public function tearDown()
	{
		parent::tearDown();
		ActiveRecord\ArraySerializer::$include_root = false;
		ActiveRecord\JsonSerializer::$include_root = false;
	}

	public function _a($options=array(), $model=null)
	{
		if (!$model)
			$model = Book::find(1);

		$s = new ActiveRecord\JsonSerializer($model,$options);
		return $s->to_a();
	}

	public function test_only()
	{
		$this->assertHasKeys('name', 'special', $this->_a(array('only' => array('name', 'special'))));
	}

	public function test_only_not_array()
	{
		$this->assertHasKeys('name', $this->_a(array('only' => 'name')));
	}

	public function test_only_should_only_apply_to_attributes()
	{
		$this->assertHasKeys('name','author', $this->_a(array('only' => 'name', 'include' => 'author')));
		$this->assertHasKeys('book_id','upper_name', $this->_a(array('only' => 'book_id', 'methods' => 'upper_name')));
	}

	public function test_only_overrides_except()
	{
		$this->assertHasKeys('name', $this->_a(array('only' => 'name', 'except' => 'name')));
	}

	public function test_except()
	{
		$this->assertDoesntHasKeys('name', 'special', $this->_a(array('except' => array('name','special'))));
	}

	public function test_except_takes_a_string()
	{
		$this->assertDoesntHasKeys('name', $this->_a(array('except' => 'name')));
	}

	public function test_methods()
	{
		$a = $this->_a(array('methods' => array('upper_name')));
		$this->assertEquals('ANCIENT ART OF MAIN TANKING', $a['upper_name']);
	}

	public function test_methods_takes_a_string()
	{
		$a = $this->_a(array('methods' => 'upper_name'));
		$this->assertEquals('ANCIENT ART OF MAIN TANKING', $a['upper_name']);
	}

	// methods should take precedence over attributes
	public function test_methods_method_same_as_attribute()
	{
		$a = $this->_a(array('methods' => 'name'));
		$this->assertEquals('ancient art of main tanking', $a['name']);
	}

	public function test_methods_method_alias()
	{
		$a = $this->_a(array('methods' => array('name' => 'alias_name')));
		$this->assertEquals('ancient art of main tanking', $a['alias_name']);
		$a = $this->_a(array('methods' => array('upper_name' => 'name')));
		$this->assertEquals('ANCIENT ART OF MAIN TANKING', $a['name']);
	}

	public function test_include()
	{
		$a = $this->_a(array('include' => array('author')));
		$this->assertHasKeys('parent_author_id', $a['author']);
	}

	public function test_include_nested_with_nested_options()
	{
		$a = $this->_a(
			array('include' => array('events' => array('except' => 'title', 'include' => array('host' => array('only' => 'id'))))),
			Host::find(4));

		$this->assertEquals(3, count($a['events']));
		$this->assertDoesntHasKeys('title', $a['events'][0]);
		$this->assertEquals(array('id' => 4), $a['events'][0]['host']);
	}

	public function test_datetime_values_get_converted_to_strings()
	{
		$now = new DateTime();
		$a = $this->_a(array('only' => 'created_at'),new Author(array('created_at' => $now)));
		$this->assertEquals($now->format(ActiveRecord\Serialization::$DATETIME_FORMAT),$a['created_at']);
	}

	public function test_to_json()
	{
		$book = Book::find(1);
		$json = $book->to_json();
		$this->assertEquals($book->attributes(),(array)json_decode($json));
	}

	public function test_to_json_include_root()
	{
		ActiveRecord\JsonSerializer::$include_root = true;
		$this->assertNotNull(json_decode(Book::find(1)->to_json())->book);
	}

	public function test_to_xml_include()
	{
		$xml = Host::find(4)->to_xml(array('include' => 'events'));
		$decoded = get_object_vars(new SimpleXMLElement($xml));

		$this->assertEquals(3, count($decoded['events']->event));
	}

	public function test_to_xml()
	{
		$book = Book::find(1);
		$this->assertEquals($book->attributes(),get_object_vars(new SimpleXMLElement($book->to_xml())));
	}

	public function test_to_array()
	{
		$book = Book::find(1);
		$array = $book->to_array();
		$this->assertEquals($book->attributes(), $array);
	}

	public function test_to_array_include_root()
	{
		ActiveRecord\ArraySerializer::$include_root = true;
		$book = Book::find(1);
		$array = $book->to_array();
		$book_attributes = array('book' => $book->attributes());
		$this->assertEquals($book_attributes, $array);
	}

	public function test_to_array_except()
	{
		$book = Book::find(1);
		$array = $book->to_array(array('except' => array('special')));
		$book_attributes = $book->attributes();
		unset($book_attributes['special']);
		$this->assertEquals($book_attributes, $array);
	}

	public function test_works_with_datetime()
	{
		Author::find(1)->update_attribute('created_at',new DateTime());
		$this->assertRegExp('/<updated_at>[0-9]{4}-[0-9]{2}-[0-9]{2}/',Author::find(1)->to_xml());
		$this->assertRegExp('/"updated_at":"[0-9]{4}-[0-9]{2}-[0-9]{2}/',Author::find(1)->to_json());
	}

	public function test_to_xml_skip_instruct()
	{
		$this->assertSame(false,strpos(Book::find(1)->to_xml(array('skip_instruct' => true)),'<?xml version'));
		$this->assertSame(0,    strpos(Book::find(1)->to_xml(array('skip_instruct' => false)),'<?xml version'));
	}

	public function test_only_method()
	{
		$this->assertContains('<sharks>lasers</sharks>', Author::first()->to_xml(array('only_method' => 'return_something')));
	}

	public function test_to_csv()
	{
		$book = Book::find(1);
		$this->assertEquals('1,1,2,"Ancient Art of Main Tanking",0,0',$book->to_csv());
	}

	public function test_to_csv_only_header()
	{
		$book = Book::find(1);
		$this->assertEquals('book_id,author_id,secondary_author_id,name,numeric_test,special',
			$book->to_csv(array('only_header'=>true))
		);
	}

	public function test_to_csv_only_method()
	{
		$book = Book::find(1);
		$this->assertEquals('2,"Ancient Art of Main Tanking"',
			$book->to_csv(array('only'=>array('name','secondary_author_id')))
		);
	}

	public function test_to_csv_only_method_on_header()
	{
		$book = Book::find(1);
		$this->assertEquals('secondary_author_id,name',
		$book->to_csv(array('only'=>array('secondary_author_id','name'),
			'only_header'=>true))
		);
	}

	public function test_to_csv_with_custom_delimiter()
	{
		$book = Book::find(1);
		ActiveRecord\CsvSerializer::$delimiter=';';
		$this->assertEquals('1;1;2;"Ancient Art of Main Tanking";0;0',$book->to_csv());
	}

	public function test_to_csv_with_custom_enclosure()
	{
		$book = Book::find(1);
		ActiveRecord\CsvSerializer::$delimiter=',';
		ActiveRecord\CsvSerializer::$enclosure="'";
		$this->assertEquals("1,1,2,'Ancient Art of Main Tanking',0,0",$book->to_csv());
	}
}
