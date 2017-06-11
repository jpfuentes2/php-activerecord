<?php

use ActiveRecord\Column;
use ActiveRecord\DateTime;
use ActiveRecord\DatabaseException;

class ColumnTest extends TestCase
{
	public function setUp()
	{
		$this->column = new Column();
		try {
			$this->conn = ActiveRecord\ConnectionManager::get_connection(ActiveRecord\Config::instance()->get_default_connection());
		} catch (DatabaseException $e) {
			$this->markTestSkipped('failed to connect using default connection. '.$e->getMessage());
		}
	}

	public function assertMappedType($type, $raw_type)
	{
		$this->column->raw_type = $raw_type;
		$this->assertEquals($type,$this->column->map_raw_type());
	}

	public function assertCast($type, $casted_value, $original_value)
	{
		$this->column->type = $type;
		$value = $this->column->cast($original_value,$this->conn);

		if ($original_value != null && ($type == Column::DATETIME || $type == Column::DATE))
			$this->assertTrue($value instanceof DateTime);
		else
			$this->assertSame($casted_value,$value);
	}

	public function test_map_raw_type_dates()
	{
		$this->assertMappedType(Column::DATETIME,'datetime');
		$this->assertMappedType(Column::DATE,'date');
	}

	public function test_map_raw_type_integers()
	{
		$this->assertMappedType(Column::INTEGER,'integer');
		$this->assertMappedType(Column::INTEGER,'int');
		$this->assertMappedType(Column::INTEGER,'tinyint');
		$this->assertMappedType(Column::INTEGER,'smallint');
		$this->assertMappedType(Column::INTEGER,'mediumint');
		$this->assertMappedType(Column::INTEGER,'bigint');
	}

	public function test_map_raw_type_decimals()
	{
		$this->assertMappedType(Column::DECIMAL,'float');
		$this->assertMappedType(Column::DECIMAL,'double');
		$this->assertMappedType(Column::DECIMAL,'numeric');
		$this->assertMappedType(Column::DECIMAL,'dec');
	}

	public function test_map_raw_type_strings()
	{
		$this->assertMappedType(Column::STRING,'string');
		$this->assertMappedType(Column::STRING,'varchar');
		$this->assertMappedType(Column::STRING,'text');
	}

	public function test_map_raw_type_default_to_string()
	{
		$this->assertMappedType(Column::STRING,'bajdslfjasklfjlksfd');
	}

	public function test_map_raw_type_changes_integer_to_int()
	{
		$this->column->raw_type = 'integer';
		$this->column->map_raw_type();
		$this->assertEquals('int',$this->column->raw_type);
	}

	public function test_cast()
	{
		$datetime = new DateTime('2001-01-01');
		$this->assertCast(Column::INTEGER,1,'1');
		$this->assertCast(Column::INTEGER,1,'1.5');
		$this->assertCast(Column::DECIMAL,1.5,'1.5');
		$this->assertCast(Column::DATETIME,$datetime,'2001-01-01');
		$this->assertCast(Column::DATE,$datetime,'2001-01-01');
		$this->assertCast(Column::DATE,$datetime,$datetime);
		$this->assertCast(Column::STRING,'bubble tea','bubble tea');
		$this->assertCast(Column::INTEGER,4294967295,'4294967295');
		$this->assertCast(Column::INTEGER,'18446744073709551615','18446744073709551615');

		// 32 bit
		if (PHP_INT_SIZE === 4)
			$this->assertCast(Column::INTEGER,'2147483648',(((float) PHP_INT_MAX) + 1));
		// 64 bit
		elseif (PHP_INT_SIZE === 8)
			$this->assertCast(Column::INTEGER,'9223372036854775808',(((float) PHP_INT_MAX) + 1));

		$this->assertCast(Column::BOOLEAN,$this->conn->boolean_to_string(true),true);
		$this->assertCast(Column::BOOLEAN,$this->conn->boolean_to_string(false),false);
	}

	public function test_cast_leave_null_alone()
	{
		$types = array(
			Column::STRING,
			Column::INTEGER,
			Column::DECIMAL,
			Column::DATETIME,
			Column::DATE,
			Column::BOOLEAN);

		foreach ($types as $type) {
			$this->assertCast($type,null,null);
		}
	}

	public function test_empty_and_null_date_strings_should_return_null()
	{
		$column = new Column();
		$column->type = Column::DATE;
		$this->assertEquals(null,$column->cast(null,$this->conn));
		$this->assertEquals(null,$column->cast('',$this->conn));
	}

	public function test_empty_and_null_datetime_strings_should_return_null()
	{
		$column = new Column();
		$column->type = Column::DATETIME;
		$this->assertEquals(null,$column->cast(null,$this->conn));
		$this->assertEquals(null,$column->cast('',$this->conn));
	}

	public function test_native_date_time_attribute_copies_exact_tz()
	{
		$dt = new \DateTime(null, new \DateTimeZone('America/New_York'));

		$column = new Column();
		$column->type = Column::DATETIME;

		$dt2 = $column->cast($dt, $this->conn);

		$this->assertEquals($dt->getTimestamp(), $dt2->getTimestamp());
		$this->assertEquals($dt->getTimeZone(), $dt2->getTimeZone());
		$this->assertEquals($dt->getTimeZone()->getName(), $dt2->getTimeZone()->getName());
	}

	public function test_ar_date_time_attribute_copies_exact_tz()
	{
		$dt = new DateTime(null, new \DateTimeZone('America/New_York'));

		$column = new Column();
		$column->type = Column::DATETIME;

		$dt2 = $column->cast($dt, $this->conn);

		$this->assertEquals($dt->getTimestamp(), $dt2->getTimestamp());
		$this->assertEquals($dt->getTimeZone(), $dt2->getTimeZone());
		$this->assertEquals($dt->getTimeZone()->getName(), $dt2->getTimeZone()->getName());
	}
}
