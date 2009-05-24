<?php
include 'helpers/config.php';

class BookPresence extends ActiveRecord\Model
{
	static $table_name = 'books';

	static $validates_presence_of = array(
		array('name')
	);
}

class ValidatesPresenceOfTest extends DatabaseTest
{
	public function testPresence()
	{
		$book = new Book;
		$book->name = 'blah';
		$book->save();
		$this->assertFalse($book->errors->is_invalid('name'));
	}

	public function testInvalidNull()
	{
		$book = new BookPresence;
		$book->name = null;
		$book->save();
		$this->assertTrue($book->errors->is_invalid('name'));
	}

	public function testInvalidBlank()
	{
		$book = new BookPresence;
		$book->name = '';
		$book->save();
		$this->assertTrue($book->errors->is_invalid('name'));
	}

	public function testValidWhiteSpace()
	{
		$book = new BookPresence;
		$book->name = ' ';
		$book->save();
		$this->assertFalse($book->errors->is_invalid('name'));
	}

	public function testCustomMessage()
	{
		BookPresence::$validates_presence_of[0]['message'] = 'is using a custom message.';

		$book = new BookPresence;
		$book->name = null;
		$book->save();
		$this->assertEquals('is using a custom message.', $book->errors->on('name'));
	}
};
?>