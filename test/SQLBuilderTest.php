<?php
include 'helpers/config.php';

use ActiveRecord\SQLBuilder;

class SQLBuilderTest extends DatabaseTest
{
	public function setUp($connection_name=null)
	{
		parent::setUp($connection_name);
		$this->sql = new SQLBuilder($this->conn,'authors');
	}

	/**
	 * @expectedException ActiveRecord\ActiveRecordException
	 */
	public function testNoConnection()
	{
		new SQLBuilder(null,'authors');
	}

	public function testNothing()
	{
		$this->assertEquals('SELECT * FROM authors',(string)$this->sql);
	}

	public function testWhereWithArray()
	{
		$this->sql->where('id=? AND name IN(?)',1,array('Tito','Mexican'));
		$this->assertEquals('SELECT * FROM authors WHERE id=? AND name IN(?,?)',(string)$this->sql);
		$this->assertEquals(array(1,'Tito','Mexican'),$this->sql->get_where_values());
	}

	public function testWhereWithHash()
	{
		$this->sql->where(array('id' => 1, 'name' => 'Tito'));
		$this->assertEquals('SELECT * FROM authors WHERE id=? AND name=?',(string)$this->sql);
		$this->assertEquals(array(1,'Tito'),$this->sql->get_where_values());
	}

	public function testWhereWithHashAndArray()
	{
		$this->sql->where(array('id' => 1, 'name' => array('Tito','Mexican')));
		$this->assertEquals('SELECT * FROM authors WHERE id=? AND name IN(?,?)',(string)$this->sql);
		$this->assertEquals(array(1,'Tito','Mexican'),$this->sql->get_where_values());
	}

	public function testWhereWithNull()
	{
		$this->sql->where(null);
		$this->assertEquals('SELECT * FROM authors',(string)$this->sql);
	}

	public function testWhereWithNoArgs()
	{
		$this->sql->where();
		$this->assertEquals('SELECT * FROM authors',(string)$this->sql);
	}

	public function testOrder()
	{
		$this->sql->order('name');
		$this->assertEquals('SELECT * FROM authors ORDER BY name',(string)$this->sql);
	}

	public function testLimit()
	{
		$this->sql->limit(10)->offset(1);
		$this->assertEquals($this->conn->limit('SELECT * FROM authors',1,10),(string)$this->sql);
	}

	public function testSelect()
	{
		$this->sql->select('id,name');
		$this->assertEquals('SELECT id,name FROM authors',(string)$this->sql);
	}

	public function testJoins()
	{
		$join = 'inner join books on(authors.id=books.author_id)';
		$this->sql->joins($join);
		$this->assertEquals("SELECT * FROM authors $join",(string)$this->sql);
	}

	public function testGroup()
	{
		$this->sql->group('name');
		$this->assertEquals('SELECT * FROM authors GROUP BY name',(string)$this->sql);
	}

	/**
	 * @expectedException ActiveRecord\ActiveRecordException
	 */
	public function testInsertRequiresHash()
	{
		$this->sql->insert(array(1));
	}

	/**
	 * @expectedException ActiveRecord\ActiveRecordException
	 */
	public function testUpdateRequiresHash()
	{
		$this->sql->update(array(1));
	}

	public function testInsert()
	{
		$this->sql->insert(array('id' => 1, 'name' => 'Tito'));
		$this->assertEquals('INSERT INTO authors(id,name) VALUES(?,?)',(string)$this->sql);
	}

	public function testInsertWithNull()
	{
		$this->sql->insert(array('id' => 1, 'name' => null));
		$this->assertEquals('INSERT INTO authors(id,name) VALUES(?,?)',$this->sql->to_s());
	}

	public function testUpdate()
	{
		$this->sql->update(array('id' => 1, 'name' => 'Tito'))->where('id=1 AND name IN(?)',array('Tito','Mexican'));
 		$this->assertEquals('UPDATE authors SET id=?, name=? WHERE id=1 AND name IN(?,?)',(string)$this->sql);
 		$this->assertEquals(array(1,'Tito','Tito','Mexican'),$this->sql->bind_values());
	}

	public function testUpdateWithNull()
	{
		$this->sql->update(array('id' => 1, 'name' => null))->where('id=1');
		$this->assertEquals('UPDATE authors SET id=?, name=? WHERE id=1',$this->sql->to_s());
	}

	public function testDelete()
	{
		$this->sql->delete();
		$this->assertEquals('DELETE FROM authors',$this->sql->to_s());
	}

	public function testDeleteWithWhere()
	{
		$this->sql->delete('id=? or name in(?)',1,array('Tito','Mexican'));
		$this->assertEquals('DELETE FROM authors WHERE id=? or name in(?,?)',$this->sql->to_s());
		$this->assertEquals(array(1,'Tito','Mexican'),$this->sql->bind_values());
	}

	public function testDeleteWithHash()
	{
		$this->sql->delete(array('id' => 1, 'name' => array('Tito','Mexican')));
		$this->assertEquals('DELETE FROM authors WHERE id=? AND name IN(?,?)',$this->sql->to_s());
		$this->assertEquals(array(1,'Tito','Mexican'),$this->sql->get_where_values());
	}

	public function testReverseOrder()
	{
		$this->assertEquals('id ASC, name DESC', SQLBuilder::reverse_order('id DESC, name ASC'));
		$this->assertEquals('id ASC, name DESC , zzz ASC', SQLBuilder::reverse_order('id DESC, name ASC , zzz DESC'));
		$this->assertEquals('id DESC, name DESC', SQLBuilder::reverse_order('id, name'));
		$this->assertEquals('id DESC', SQLBuilder::reverse_order('id'));
		$this->assertEquals('', SQLBuilder::reverse_order(''));
		$this->assertEquals(' ', SQLBuilder::reverse_order(' '));
		$this->assertEquals(null, SQLBuilder::reverse_order(null));
	}

	public function testCreateConditionsFromUnderscoredString()
	{
		$x = array(1,'Tito','X');
		$this->assertEquals(array_merge(array('id=? AND name=? OR z=?'),$x),SQLBuilder::create_conditions_from_underscored_string('id_and_name_or_z',$x));

		$x = array(1);
		$this->assertEquals(array('id=?',1),SQLBuilder::create_conditions_from_underscored_string('id',$x));

		$x = array(array(1,2));
		$this->assertEquals(array_merge(array('id IN(?)'),$x),SQLBuilder::create_conditions_from_underscored_string('id',$x));
	}

	public function testCreateConditionsFromUnderscoredStringWithNulls()
	{
		$x = array(1,null);
		$this->assertEquals(array('id=? AND name IS NULL',1),SQLBuilder::create_conditions_from_underscored_string('id_and_name',$x));
	}

	public function testCreateConditionsFromUnderscoredStringWithMissingArgs()
	{
		$x = array(1,null);
		$this->assertEquals(array('id=? AND name IS NULL OR z IS NULL',1),SQLBuilder::create_conditions_from_underscored_string('id_and_name_or_z',$x));

		$this->assertEquals(array('id IS NULL'),SQLBuilder::create_conditions_from_underscored_string('id'));
	}

	public function testCreateConditionsFromUnderscoredStringWithBlank()
	{
		$x = array(1,null,'');
		$this->assertEquals(array('id=? AND name IS NULL OR z=?',1,''),SQLBuilder::create_conditions_from_underscored_string('id_and_name_or_z',$x));
	}

	public function testCreateConditionsFromUnderscoredStringInvalid()
	{
		$this->assertEquals(null,SQLBuilder::create_conditions_from_underscored_string(''));
		$this->assertEquals(null,SQLBuilder::create_conditions_from_underscored_string(null));
	}

	public function testCreateConditionsFromUnderscoredStringWithMappedColumns()
	{
		$x = array(1,'Tito');
		$map = array('my_name' => 'name');
		$conditions = SQLBuilder::create_conditions_from_underscored_string('id_and_my_name',$x,$map);
		$this->assertEquals(array('id=? AND name=?',1,'Tito'),$conditions);
	}
};
?>