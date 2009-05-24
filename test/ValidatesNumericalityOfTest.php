<?php
include 'helpers/config.php';

class BookNumericality extends ActiveRecord\Model
{
	static $table_name = 'books';

	static $validates_numericality_of = array(
		array('name')
	);
}

class ValidatesNumericalityOfTest extends DatabaseTest
{
	static $NULL = array(null);
  	static $BLANK = array("", " ", " \t \r \n");
	static $FLOAT_STRINGS = array('0.0','+0.0','-0.0','10.0','10.5','-10.5','-0.0001','-090.1');
  	static $INTEGER_STRINGS = array('0', '+0', '-0', '10', '+10', '-10', '0090', '-090');
	static $FLOATS = array(0.0, 10.0, 10.5, -10.5, -0.0001);
  	static $INTEGERS = array(0, 10, -10);
  	static $JUNK = array("not a number", "42 not a number", "00-1", "--3", "+-3", "+3-1", "-+019.0", "12.12.13.12", "123\nnot a number");

	public function setUp($connection_name=null)
	{
		parent::setUp($connection_name);
		BookNumericality::$validates_numericality_of = array(
			array('numeric_test')
		);
	}

	private function assertValidity($value, $boolean, $msg=null)
	{
		$book = new BookNumericality;
		$book->numeric_test = $value;

		if ($boolean == 'valid')
		{
			$this->assertTrue($book->save());
			$this->assertFalse($book->errors->is_invalid('numeric_test'));
		}
		else
		{
			$this->assertFalse($book->save());
			$this->assertTrue($book->errors->is_invalid('numeric_test'));

			if (!is_null($msg))
				$this->assertSame($msg, $book->errors->on('numeric_test'));
		}
	}

	private function assertInvalid($values, $msg=null)
	{
		foreach ($values as $value)
			$this->assertValidity($value, 'invalid', $msg);
	}

	private function assertValid($values, $msg=null)
	{
		foreach ($values as $value)
			$this->assertValidity($value, 'valid', $msg);
	}

	public function testNumericality()
	{
		//$this->assertInvalid(array("0xdeadbeef"));

		$this->assertValid(array_merge(self::$FLOATS, self::$INTEGERS));
		$this->assertInvalid(array_merge(self::$NULL, self::$BLANK, self::$JUNK));
	}

	public function testNotAnumber()
	{
		$this->assertInvalid(array('blah'), 'is not a number');
	}

	public function testInvalidNull()
	{
		$this->assertInvalid(array(null));
	}

	public function testInvalidBlank()
	{
		$this->assertInvalid(array(' ', '  '), 'is not a number');
	}

	public function testInvalidWhitespace()
	{
		$this->assertInvalid(array(''));
	}

	public function testValidNull()
	{
		BookNumericality::$validates_numericality_of[0]['allow_null'] = true;
		$this->assertValid(array(null));
	}

	public function testOnlyInteger()
	{
		BookNumericality::$validates_numericality_of[0]['only_integer'] = true;

		$this->assertValid(array(1, '1'));
		$this->assertInvalid(array(1.5, '1.5'));
	}

	public function testGreaterThan()
	{
		BookNumericality::$validates_numericality_of[0]['greater_than'] = 5;

		$this->assertValid(array(6, '7'));
		$this->assertInvalid(array(5, '5'), 'must be greater than 5');
	}

	public function testGreaterThanOrEqualTo()
	{
		BookNumericality::$validates_numericality_of[0]['greater_than_or_equal_to'] = 5;

		$this->assertValid(array(5, 5.1, '5.1'));
		$this->assertInvalid(array(-50, 4.9, '4.9','-5.1'));
	}

	public function testLessThan()
	{
		BookNumericality::$validates_numericality_of[0]['less_than'] = 5;

		$this->assertValid(array(4.9, -1, 0, '-5'));
		$this->assertInvalid(array(5, '5'), 'must be less than 5');
	}

	public function testLessThanOrEqualTo()
	{
		BookNumericality::$validates_numericality_of[0]['less_than_or_equal_to'] = 5;

		$this->assertValid(array(5, -1, 0, 4.9, '-5'));
		$this->assertInvalid(array('8', 5.1), 'must be less than or equal to 5');
	}

	public function testGreaterThanLessThanAndEven()
	{
		BookNumericality::$validates_numericality_of[0] = array('numeric_test', 'greater_than' => 1, 'less_than' => 4, 'even' => true);

		$this->assertValid(array(2));
		$this->assertInvalid(array(1,3,4));
	}
};

array_merge(ValidatesNumericalityOfTest::$INTEGERS, ValidatesNumericalityOfTest::$INTEGER_STRINGS);
array_merge(ValidatesNumericalityOfTest::$FLOATS, ValidatesNumericalityOfTest::$FLOAT_STRINGS);
?>