<?php
include 'helpers/config.php';

class BookLength extends ActiveRecord\Model
{
	static $table = 'books';
	static $validates_length_of = array();
}

class BookSize extends ActiveRecord\Model
{
	static $table = 'books';
	static $validates_size_of = array();
}

class ValidatesLengthOfTest extends DatabaseTest
{
	public function setUp($connection_name=null)
	{
		parent::setUp($connection_name);
		BookLength::$validates_length_of[0] = array('name', 'allow_blank' => false, 'allow_null' => false);
	}

	public function testWithin()
	{
		BookLength::$validates_length_of[0]['within'] = array(1, 5);
		$book = new BookLength;
		$book->name = '12345';
		$book->save();
		$this->assertFalse($book->errors->is_invalid('name'));
	}

	public function testValidIn()
	{
		BookLength::$validates_length_of[0]['in'] = array(1, 5);
		$book = new BookLength;
		$book->name = '12345';
		$book->save();
		$this->assertFalse($book->errors->is_invalid('name'));
	}

	public function testAliasedSizeOf()
	{
		BookSize::$validates_size_of = BookLength::$validates_length_of;
		BookSize::$validates_size_of[0]['within'] = array(1, 5);
		$book = new BookSize;
		$book->name = '12345';
		$book->save();
		$this->assertFalse($book->errors->is_invalid('name'));
	}

	public function testInvalidWithinAndIn()
	{
		BookLength::$validates_length_of[0]['within'] = array(1, 3);
		$book = new BookLength;
		$book->name = 'four';
		$book->save();
		$this->assertTrue($book->errors->is_invalid('name'));

		$this->setUp();
		BookLength::$validates_length_of[0]['in'] = array(1, 3);
		$book = new BookLength;
		$book->name = 'four';
		$book->save();
		$this->assertTrue($book->errors->is_invalid('name'));
	}

	public function testValidNull()
	{
		BookLength::$validates_length_of[0]['within'] = array(1, 3);
		BookLength::$validates_length_of[0]['allow_null'] = true;

		$book = new BookLength;
		$book->name = null;
		$book->save();
		$this->assertFalse($book->errors->is_invalid('name'));
	}

	public function testValidBlank()
	{
		BookLength::$validates_length_of[0]['within'] = array(1, 3);
		BookLength::$validates_length_of[0]['allow_blank'] = true;

		$book = new BookLength;
		$book->name = '';
		$book->save();
		$this->assertFalse($book->errors->is_invalid('name'));
	}

	public function testInvalidBlank()
	{
		BookLength::$validates_length_of[0]['within'] = array(1, 3);

		$book = new BookLength;
		$book->name = '';
		$book->save();
		$this->assertTrue($book->errors->is_invalid('name'));
		$this->assertEquals('is too short (minimum is 1 characters)', $book->errors->on('name'));
	}

	public function testInvalidNull()
	{
		BookLength::$validates_length_of[0]['within'] = array(1, 3);

		$book = new BookLength;
		$book->name = null;
		$book->save();
		$this->assertTrue($book->errors->is_invalid('name'));
		$this->assertEquals('is too short (minimum is 1 characters)', $book->errors->on('name'));
	}

	public function testFloatAsImpossibleRangeOption()
	{
		BookLength::$validates_length_of[0]['within'] = array(1, 3.6);
		$book = new BookLength;
		$book->name = '123';
		try {
			$book->save();
		} catch (ActiveRecord\ValidationsArgumentError $e) {
			$this->assertEquals('Range values cannot use floats for length.', $e->getMessage());
		}

		$this->setUp();
		BookLength::$validates_length_of[0]['is'] = 1.8;
		$book = new BookLength;
		$book->name = '123';
		try {
			$book->save();
		} catch (ActiveRecord\ValidationsArgumentError $e) {
			$this->assertEquals('is value cannot use a float for length.', $e->getMessage());
			return;
		}

		$this->fail('An expected exception has not be raised.');
	}

	public function testSignedIntegerAsImpossibleWithinOption()
	{
		BookLength::$validates_length_of[0]['within'] = array(-1, 3);

		$book = new BookLength;
		$book->name = '123';
		try {
			$book->save();
		} catch (ActiveRecord\ValidationsArgumentError $e) {
			$this->assertEquals('Range values cannot use signed integers.', $e->getMessage());
			return;
		}

		$this->fail('An expected exception has not be raised.');
	}

	public function testSignedIntegerAsImpossibleIsOption()
	{
		BookLength::$validates_length_of[0]['is'] = -8;

		$book = new BookLength;
		$book->name = '123';
		try {
			$book->save();
		} catch (ActiveRecord\ValidationsArgumentError $e) {
			$this->assertEquals('is value cannot use a signed integer.', $e->getMessage());
			return;
		}

		$this->fail('An expected exception has not be raised.');
	}

	public function testLackOfOption()
	{
		try {
			$book = new BookLength;
			$book->name = null;
			$book->save();
		} catch (ActiveRecord\ValidationsArgumentError $e) {
			$this->assertEquals('Range unspecified.  Specify the [within], [maximum], or [is] option.', $e->getMessage());
			return;
		}

		$this->fail('An expected exception has not be raised.');
	}

	public function testTooManyOptions()
	{
		BookLength::$validates_length_of[0]['within'] = array(1, 3);
		BookLength::$validates_length_of[0]['in'] = array(1, 3);

		try {
			$book = new BookLength;
			$book->name = null;
			$book->save();
		} catch (ActiveRecord\ValidationsArgumentError $e) {
			$this->assertEquals('Too many range options specified.  Choose only one.', $e->getMessage());
			return;
		}

		$this->fail('An expected exception has not be raised.');
	}

	public function testTooManyOptionsWithDifferentOptionTypes()
	{
		BookLength::$validates_length_of[0]['within'] = array(1, 3);
		BookLength::$validates_length_of[0]['is'] = 3;

		try {
			$book = new BookLength;
			$book->name = null;
			$book->save();
		} catch (ActiveRecord\ValidationsArgumentError $e) {
			$this->assertEquals('Too many range options specified.  Choose only one.', $e->getMessage());
			return;
		}

		$this->fail('An expected exception has not be raised.');
	}

	/**
	 * @expectedException ActiveRecord\ValidationsArgumentError
	 */
	public function testWithOptionAsNonNumeric()
	{
		BookLength::$validates_length_of[0]['with'] = array('test');

		$book = new BookLength;
		$book->name = null;
		$book->save();
	}

	/**
	 * @expectedException ActiveRecord\ValidationsArgumentError
	 */
	public function testWithOptionAsNonNumericNonArray()
	{
		BookLength::$validates_length_of[0]['with'] = 'test';

		$book = new BookLength;
		$book->name = null;
		$book->save();
	}
};
?>