<?php

class BookOn extends ActiveRecord\Model
{
	static $table_name = 'books';

	static $validates_presence_of = array();
}

class ValidatesOnSaveCreateUpdateTest extends DatabaseTest{

	public function test_validations_only_run_on_create()
	{
		BookOn::$validates_presence_of[0] = array('name', 'on' => 'create');
		$book = new BookOn();
		$this->assertFalse($book->is_valid());
		
		$book->secondary_author_id = 1;
		$this->assertFalse( $book->save() );
		
		$book->name = 'Baz';
		$this->assertTrue( $book->save() );
		
		$book->name = null;
		$this->assertTrue( $book->save() );
	}

	public function test_validations_only_run_on_update()
	{
		BookOn::$validates_presence_of[0] = array('name', 'on' => 'update');
		$book = new BookOn();
		$this->assertTrue($book->save());
		
		$book->name = null;
		$this->assertFalse( $book->save() );
		
		$book->name = 'Baz';
		$this->assertTrue( $book->save() );
	}

	public function test_validations_only_run_on_save()
	{
		BookOn::$validates_presence_of[0] = array('name', 'on' => 'save');
		$book = new BookOn();
		$this->assertFalse($book->is_valid());
		
		$book->secondary_author_id = 1;
		$this->assertFalse( $book->save() );
		
		$book->name = 'Baz';
		$this->assertTrue( $book->save() );
		
		$book->name = null;
		$this->assertFalse( $book->save() );
	}

	public function test_validations_run_always_without_on()
	{
		BookOn::$validates_presence_of[0] = array('name');
		$book = new BookOn();
		$this->assertFalse($book->is_valid());
		
		$book->secondary_author_id = 1;
		$this->assertFalse( $book->save() );
		
		$book->name = 'Baz';
		$this->assertTrue( $book->save() );
		
		$book->name = null;
		$this->assertFalse( $book->save() );
	}
	
}