<?php

use ActiveRecord\SQLBuilder;
use ActiveRecord\Table;

class SQLBuilderTest extends DatabaseTest
{
	protected $table_name = 'authors';
	protected $class_name = 'Author';
	protected $table;

	public function setUp($connection_name=null)
	{
		parent::setUp($connection_name);
		$this->sql = new SQLBuilder($this->conn,$this->table_name);
		$this->table = Table::load($this->class_name);
	}

	protected function cond_from_s($name, $values=null, $map=null)
	{
		return SQLBuilder::create_conditions_from_underscored_string($this->table->conn, $name, $values, $map);
	}

	public function assertConditions($expected_sql, $values, $underscored_string, $map=null)
	{
		$cond = SQLBuilder::create_conditions_from_underscored_string($this->table->conn,$underscored_string,$values,$map);
		$this->assertSqlHas($expected_sql,array_shift($cond));

		if ($values)
			$this->assertEquals(array_values(array_filter($values,function($s) { return $s !== null; })),array_values($cond));
		else
			$this->assertEquals(array(),$cond);
	}

	/**
	 * @expectedException ActiveRecord\ActiveRecordException
	 */
	public function test_no_connection()
	{
		new SQLBuilder(null,'authors');
	}

	public function test_nothing()
	{
		$this->assertEquals('SELECT * FROM authors',(string)$this->sql);
	}

	public function test_where_with_array()
	{
		$this->sql->where("id=? AND name IN(?)",1,array('Tito','Mexican'));
		$this->assertSqlHas("SELECT * FROM authors WHERE id=? AND name IN(?,?)",(string)$this->sql);
		$this->assertEquals(array(1,'Tito','Mexican'),$this->sql->get_where_values());
	}

	public function test_where_with_hash()
	{
		$this->sql->where(array('id' => 1, 'name' => 'Tito'));
		$this->assertSqlHas("SELECT * FROM authors WHERE id=? AND name=?",(string)$this->sql);
		$this->assertEquals(array(1,'Tito'),$this->sql->get_where_values());
	}

	public function test_where_with_hash_and_array()
	{
		$this->sql->where(array('id' => 1, 'name' => array('Tito','Mexican')));
		$this->assertSqlHas("SELECT * FROM authors WHERE id=? AND name IN(?,?)",(string)$this->sql);
		$this->assertEquals(array(1,'Tito','Mexican'),$this->sql->get_where_values());
	}

	public function test_gh134_where_with_hash_and_null()
	{
		$this->sql->where(array('id' => 1, 'name' => null));
		$this->assertSqlHas("SELECT * FROM authors WHERE id=? AND name IS ?",(string)$this->sql);
		$this->assertEquals(array(1, null),$this->sql->get_where_values());
	}

	public function test_where_with_null()
	{
		$this->sql->where(null);
		$this->assertEquals('SELECT * FROM authors',(string)$this->sql);
	}

	public function test_where_with_no_args()
	{
		$this->sql->where();
		$this->assertEquals('SELECT * FROM authors',(string)$this->sql);
	}

	public function test_order()
	{
		$this->sql->order('name');
		$this->assertEquals('SELECT * FROM authors ORDER BY name',(string)$this->sql);
	}

	public function test_limit()
	{
		$this->sql->limit(10)->offset(1);
		$this->assertEquals($this->conn->limit('SELECT * FROM authors',1,10),(string)$this->sql);
	}

	public function test_select()
	{
		$this->sql->select('id,name');
		$this->assertEquals('SELECT id,name FROM authors',(string)$this->sql);
	}

	public function test_joins()
	{
		$join = 'inner join books on(authors.id=books.author_id)';
		$this->sql->joins($join);
		$this->assertEquals("SELECT * FROM authors $join",(string)$this->sql);
	}

	public function test_group()
	{
		$this->sql->group('name');
		$this->assertEquals('SELECT * FROM authors GROUP BY name',(string)$this->sql);
	}

	public function test_having()
	{
		$this->sql->having("created_at > '2009-01-01'");
		$this->assertEquals("SELECT * FROM authors HAVING created_at > '2009-01-01'", (string)$this->sql);
	}

	public function test_all_clauses_after_where_should_be_correctly_ordered()
	{
		$this->sql->limit(10)->offset(1);
		$this->sql->having("created_at > '2009-01-01'");
		$this->sql->order('name');
		$this->sql->group('name');
		$this->sql->where(array('id' => 1));
		$this->assertSqlHas($this->conn->limit("SELECT * FROM authors WHERE id=? GROUP BY name HAVING created_at > '2009-01-01' ORDER BY name",1,10), (string)$this->sql);
	}

	/**
	 * @expectedException ActiveRecord\ActiveRecordException
	 */
	public function test_insert_requires_hash()
	{
		$this->sql->insert(array(1));
	}

	public function test_insert()
	{
		$this->sql->insert(array('id' => 1, 'name' => 'Tito'));
		$this->assertSqlHas("INSERT INTO authors(id,name) VALUES(?,?)",(string)$this->sql);
	}

	public function test_insert_with_null()
	{
		$this->sql->insert(array('id' => 1, 'name' => null));
		$this->assertSqlHas("INSERT INTO authors(id,name) VALUES(?,?)",$this->sql->to_s());
	}

	public function test_update_with_hash()
	{
		$this->sql->update(array('id' => 1, 'name' => 'Tito'))->where('id=1 AND name IN(?)',array('Tito','Mexican'));
 		$this->assertSqlHas("UPDATE authors SET id=?, name=? WHERE id=1 AND name IN(?,?)",(string)$this->sql);
		$this->assertEquals(array(1,'Tito','Tito','Mexican'),$this->sql->bind_values());
	}

	public function test_update_with_limit_and_order()
	{
		if (!$this->conn->accepts_limit_and_order_for_update_and_delete())
			$this->markTestSkipped('Only MySQL & Sqlite accept limit/order with UPDATE operation');

		$this->sql->update(array('id' => 1))->order('name asc')->limit(1);
		$this->assertSqlHas("UPDATE authors SET id=? ORDER BY name asc LIMIT 1", $this->sql->to_s());
	}

	public function test_update_with_string()
	{
		$this->sql->update("name='Bob'");
		$this->assertSqlHas("UPDATE authors SET name='Bob'", $this->sql->to_s());
	}

	public function test_update_with_null()
	{
		$this->sql->update(array('id' => 1, 'name' => null))->where('id=1');
		$this->assertSqlHas("UPDATE authors SET id=?, name=? WHERE id=1",$this->sql->to_s());
	}

	public function test_delete()
	{
		$this->sql->delete();
		$this->assertEquals('DELETE FROM authors',$this->sql->to_s());
	}

	public function test_delete_with_where()
	{
		$this->sql->delete('id=? or name in(?)',1,array('Tito','Mexican'));
		$this->assertEquals('DELETE FROM authors WHERE id=? or name in(?,?)',$this->sql->to_s());
		$this->assertEquals(array(1,'Tito','Mexican'),$this->sql->bind_values());
	}

	public function test_delete_with_hash()
	{
		$this->sql->delete(array('id' => 1, 'name' => array('Tito','Mexican')));
		$this->assertSqlHas("DELETE FROM authors WHERE id=? AND name IN(?,?)",$this->sql->to_s());
		$this->assertEquals(array(1,'Tito','Mexican'),$this->sql->get_where_values());
	}

	public function test_delete_with_limit_and_order()
	{
		if (!$this->conn->accepts_limit_and_order_for_update_and_delete())
			$this->markTestSkipped('Only MySQL & Sqlite accept limit/order with DELETE operation');

		$this->sql->delete(array('id' => 1))->order('name asc')->limit(1);
		$this->assertSqlHas("DELETE FROM authors WHERE id=? ORDER BY name asc LIMIT 1",$this->sql->to_s());
	}

	public function test_reverse_order()
	{
		$this->assertEquals('id ASC, name DESC', SQLBuilder::reverse_order('id DESC, name ASC'));
		$this->assertEquals('id ASC, name DESC , zzz ASC', SQLBuilder::reverse_order('id DESC, name ASC , zzz DESC'));
		$this->assertEquals('id DESC, name DESC', SQLBuilder::reverse_order('id, name'));
		$this->assertEquals('id DESC', SQLBuilder::reverse_order('id'));
		$this->assertEquals('', SQLBuilder::reverse_order(''));
		$this->assertEquals(' ', SQLBuilder::reverse_order(' '));
		$this->assertEquals(null, SQLBuilder::reverse_order(null));
	}

	public function test_create_conditions_from_underscored_string()
	{
		$this->assertConditions('id=? AND name=? OR z=?',array(1,'Tito','X'),'id_and_name_or_z');
		$this->assertConditions('id=?',array(1),'id');
		$this->assertConditions('id IN(?)',array(array(1,2)),'id');
	}

	public function test_create_conditions_from_underscored_string_with_nulls()
	{
		$this->assertConditions('id=? AND name IS NULL',array(1,null),'id_and_name');
	}

	public function test_create_conditions_from_underscored_string_with_missing_args()
	{
		$this->assertConditions('id=? AND name IS NULL OR z IS NULL',array(1,null),'id_and_name_or_z');
		$this->assertConditions('id IS NULL',null,'id');
	}

	public function test_create_conditions_from_underscored_string_with_blank()
	{
		$this->assertConditions('id=? AND name IS NULL OR z=?',array(1,null,''),'id_and_name_or_z');
	}

	public function test_create_conditions_from_underscored_string_invalid()
	{
		$this->assertEquals(null,$this->cond_from_s(''));
		$this->assertEquals(null,$this->cond_from_s(null));
	}

	public function test_create_conditions_from_underscored_string_with_mapped_columns()
	{
		$this->assertConditions('id=? AND name=?',array(1,'Tito'),'id_and_my_name',array('my_name' => 'name'));
	}

	public function test_create_hash_from_underscored_string()
	{
		$values = array(1,'Tito');
		$hash = SQLBuilder::create_hash_from_underscored_string('id_and_my_name',$values);
		$this->assertEquals(array('id' => 1, 'my_name' => 'Tito'),$hash);
	}

	public function test_create_hash_from_underscored_string_with_mapped_columns()
	{
		$values = array(1,'Tito');
		$map = array('my_name' => 'name');
		$hash = SQLBuilder::create_hash_from_underscored_string('id_and_my_name',$values,$map);
		$this->assertEquals(array('id' => 1, 'name' => 'Tito'),$hash);
	}

	public function test_where_with_joins_prepends_table_name_to_fields()
	{
		$joins = 'INNER JOIN books ON (books.id = authors.id)';
		// joins needs to be called prior to where
		$this->sql->joins($joins);
		$this->sql->where(array('id' => 1, 'name' => 'Tito'));

		$this->assertSqlHas("SELECT * FROM authors $joins WHERE authors.id=? AND authors.name=?",(string)$this->sql);
	}
}
