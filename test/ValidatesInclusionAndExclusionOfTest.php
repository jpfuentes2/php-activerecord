<?php
include 'helpers/config.php';

class BookExclusion extends ActiveRecord\Model
{
	static $table = 'books';
	public static $validates_exclusion_of = array(
		array('name', 'in' => array('blah', 'alpha', 'bravo'))
	);
};

class BookInclusion extends ActiveRecord\Model
{
	static $table = 'books';
	public static $validates_inclusion_of = array(
		array('name', 'in' => array('blah', 'tanker', 'shark'))
	);
};

class ValidatesInclusionAndExclusionOfTest extends DatabaseTest
{
	public function setUp($connection_name=null)
	{
		parent::setUp($connection_name);
		BookInclusion::$validates_inclusion_of[0] = array('name', 'in' => array('blah', 'tanker', 'shark'));
		BookExclusion::$validates_exclusion_of[0] = array('name', 'in' => array('blah', 'alpha', 'bravo'));
	}

	public function testInclusion()
	{
		$book = new BookInclusion;
		$book->name = 'blah';
		$book->save();
		$this->assertFalse($book->errors->is_invalid('name'));
	}

	public function testExclusion()
	{
		$book = new BookExclusion;
		$book->name = 'blahh';
		$book->save();
		$this->assertFalse($book->errors->is_invalid('name'));
	}

	public function testInvalidInclusion()
	{
		$book = new BookInclusion;
		$book->name = 'thanker';
		$book->save();
		$this->assertTrue($book->errors->is_invalid('name'));
		$book->name = 'alpha ';
		$book->save();
		$this->assertTrue($book->errors->is_invalid('name'));
	}

	public function testInvalidExclusion()
	{
		$book = new BookExclusion;
		$book->name = 'alpha';
		$book->save();
		$this->assertTrue($book->errors->is_invalid('name'));

		$book = new BookExclusion;
		$book->name = 'bravo';
		$book->save();
		$this->assertTrue($book->errors->is_invalid('name'));
	}

	public function testInclusionWithNumeric()
	{
		BookInclusion::$validates_inclusion_of[0]['in']= array(0, 1, 2);
		$book = new BookInclusion;
		$book->name = 2;
		$book->save();
		$this->assertFalse($book->errors->is_invalid('name'));
	}

	public function testInclusionWithBoolean()
	{
		BookInclusion::$validates_inclusion_of[0]['in']= array(true);
		$book = new BookInclusion;
		$book->name = true;
		$book->save();
		$this->assertFalse($book->errors->is_invalid('name'));
	}

	public function testInclusionWithNull()
	{
		BookInclusion::$validates_inclusion_of[0]['in']= array(null);
		$book = new BookInclusion;
		$book->name = null;
		$book->save();
		$this->assertFalse($book->errors->is_invalid('name'));
	}

	public function testInvalidInclusionWithNumeric()
	{
		BookInclusion::$validates_inclusion_of[0]['in']= array(0, 1, 2);
		$book = new BookInclusion;
		$book->name = 5;
		$book->save();
		$this->assertTrue($book->errors->is_invalid('name'));
	}

	public function tesInclusionWithinOption()
	{
		BookInclusion::$validates_inclusion_of[0] = array('name', 'within' => array('okay'));
		$book = new BookInclusion;
		$book->name = 'okay';
		$book->save();
		$this->assertFalse($book->errors->is_invalid('name'));
	}

	public function tesInclusionScalarValue()
	{
		BookInclusion::$validates_inclusion_of[0] = array('name', 'within' => 'okay');
		$book = new BookInclusion;
		$book->name = 'okay';
		$book->save();
		$this->assertFalse($book->errors->is_invalid('name'));
	}

	public function testValidNull()
	{
		BookInclusion::$validates_inclusion_of[0]['allow_null'] = true;
		$book = new BookInclusion;
		$book->name = null;
		$book->save();
		$this->assertFalse($book->errors->is_invalid('name'));
	}

	public function testValidBlank()
	{
		BookInclusion::$validates_inclusion_of[0]['allow_blank'] = true;
		$book = new BookInclusion;
		$book->name = '';
		$book->save();
		$this->assertFalse($book->errors->is_invalid('name'));
	}

	public function testCustomMessage()
	{
		$msg = 'is using a custom message.';
		BookInclusion::$validates_inclusion_of[0]['message'] = $msg;
		BookExclusion::$validates_exclusion_of[0]['message'] = $msg;

		$book = new BookInclusion;
		$book->name = 'not included';
		$book->save();
		$this->assertEquals('is using a custom message.', $book->errors->on('name'));
		$book = new BookExclusion;
		$book->name = 'bravo';
		$book->save();
		$this->assertEquals('is using a custom message.', $book->errors->on('name'));
	}

};
?>