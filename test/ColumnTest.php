<?
include 'helpers/config.php';

use ActiveRecord\Column;

class ColumnTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->column = new Column();
	}

	public function assertMappedType($type, $raw_type)
	{
		$this->column->raw_type = $raw_type;
		$this->assertEquals($type,$this->column->map_raw_type());
	}

	public function assertCast($type, $casted_value, $original_value)
	{
		$this->column->type = $type;
		$value = $this->column->cast($original_value);

		if ($original_value != null && ($type == Column::DATETIME || $type == Column::DATE))
			$this->assertTrue($value instanceof \DateTime);
		else
			$this->assertSame($casted_value,$value);
	}

	public function testMapRawTypeDates()
	{
		$this->assertMappedType(Column::DATETIME,'datetime');
		$this->assertMappedType(Column::DATE,'date');
	}

	public function testMapRawTypeIntegers()
	{
		$this->assertMappedType(Column::INTEGER,'integer');
		$this->assertMappedType(Column::INTEGER,'int');
		$this->assertMappedType(Column::INTEGER,'tinyint');
		$this->assertMappedType(Column::INTEGER,'smallint');
		$this->assertMappedType(Column::INTEGER,'mediumint');
		$this->assertMappedType(Column::INTEGER,'bigint');
	}

	public function testMapRawTypeDecimals()
	{
		$this->assertMappedType(Column::DECIMAL,'float');
		$this->assertMappedType(Column::DECIMAL,'double');
		$this->assertMappedType(Column::DECIMAL,'numeric');
		$this->assertMappedType(Column::DECIMAL,'dec');
	}

	public function testMapRawTypeStrings()
	{
		$this->assertMappedType(Column::STRING,'string');
		$this->assertMappedType(Column::STRING,'varchar');
		$this->assertMappedType(Column::STRING,'text');
	}

	public function testMapRawTypeDefaultToString()
	{
		$this->assertMappedType(Column::STRING,'bajdslfjasklfjlksfd');
	}

	public function testMapRawTypeChangesIntegerToInt()
	{
		$this->column->raw_type = 'integer';
		$this->column->map_raw_type();
		$this->assertEquals('int',$this->column->raw_type);
	}

	public function testCast()
	{
		$this->assertCast(Column::INTEGER,1,'1');
		$this->assertCast(Column::INTEGER,1,'1.5');
		$this->assertCast(Column::DECIMAL,1.5,'1.5');
		$this->assertCast(Column::DATETIME,new \DateTime('2001-01-01'),'2001-01-01');
		$this->assertCast(Column::DATE,new \DateTime('2001-01-01'),'2001-01-01');
		$this->assertCast(Column::STRING,'bubble tea','bubble tea');
	}

	public function testCastLeaveNullAlone()
	{
		$types = array(
			Column::STRING,
			Column::INTEGER,
			Column::DECIMAL,
			Column::DATETIME,
			Column::DATE);

		foreach ($types as $type) {
			$this->assertCast($type,null,null);
		}
	}
}
?>