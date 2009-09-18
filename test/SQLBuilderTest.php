<?php
include 'helpers/config.php';

use ActiveRecord\SQLBuilder;

class SQLBuilderTest extends DatabaseTest
{
	public function set_up($connection_name=null)
	{
		parent::set_up($connection_name);
		$this->sql = new SQLBuilder($this->conn,'authors');
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
		$this->assert_equals('SELECT * FROM authors',(string)$this->sql);
	}

	public function test_where_with_array()
	{
		$this->sql->where('`id`=? AND `name` IN(?)',1,array('Tito','Mexican'));
		$this->assert_equals('SELECT * FROM authors WHERE `id`=? AND `name` IN(?,?)',(string)$this->sql);
		$this->assert_equals(array(1,'Tito','Mexican'),$this->sql->get_where_values());
	}

	public function test_where_with_hash()
	{
		$this->sql->where(array('id' => 1, 'name' => 'Tito'));
		$this->assert_equals('SELECT * FROM authors WHERE `id`=? AND `name`=?',(string)$this->sql);
		$this->assert_equals(array(1,'Tito'),$this->sql->get_where_values());
	}

	public function test_where_with_hash_and_array()
	{
		$this->sql->where(array('id' => 1, 'name' => array('Tito','Mexican')));
		$this->assert_equals('SELECT * FROM authors WHERE `id`=? AND `name` IN(?,?)',(string)$this->sql);
		$this->assert_equals(array(1,'Tito','Mexican'),$this->sql->get_where_values());
	}

	public function test_where_with_null()
	{
		$this->sql->where(null);
		$this->assert_equals('SELECT * FROM authors',(string)$this->sql);
	}

	public function test_where_with_no_args()
	{
		$this->sql->where();
		$this->assert_equals('SELECT * FROM authors',(string)$this->sql);
	}

	public function test_order()
	{
		$this->sql->order('name');
		$this->assert_equals('SELECT * FROM authors ORDER BY name',(string)$this->sql);
	}

	public function test_limit()
	{
		$this->sql->limit(10)->offset(1);
		$this->assert_equals($this->conn->limit('SELECT * FROM authors',1,10),(string)$this->sql);
	}

	public function test_select()
	{
		$this->sql->select('id,name');
		$this->assert_equals('SELECT id,name FROM authors',(string)$this->sql);
	}

	public function test_joins()
	{
		$join = 'inner join books on(authors.id=books.author_id)';
		$this->sql->joins($join);
		$this->assert_equals("SELECT * FROM authors $join",(string)$this->sql);
	}

	public function test_group()
	{
		$this->sql->group('name');
		$this->assert_equals('SELECT * FROM authors GROUP BY name',(string)$this->sql);
	}

	public function test_having()
	{
		$this->sql->having("created_at > '2009-01-01'");
		$this->assert_equals("SELECT * FROM authors HAVING created_at > '2009-01-01'", (string)$this->sql);
	}

	public function test_all_clauses_after_where_should_be_correctly_ordered()
	{
		$this->sql->limit(10)->offset(1);
		$this->sql->having("created_at > '2009-01-01'");
		$this->sql->order('name');
		$this->sql->group('name');
		$this->sql->where(array('id' => 1));
		$this->assert_equals("SELECT * FROM authors WHERE `id`=? GROUP BY name HAVING created_at > '2009-01-01' ORDER BY name LIMIT 1,10", (string)$this->sql);
	}

	/**
	 * @expectedException ActiveRecord\ActiveRecordException
	 */
	public function test_insert_requires_hash()
	{
		$this->sql->insert(array(1));
	}

	/**
	 * @expectedException ActiveRecord\ActiveRecordException
	 */
	public function test_update_requires_hash()
	{
		$this->sql->update(array(1));
	}

	public function test_insert()
	{
		$this->sql->insert(array('id' => 1, 'name' => 'Tito'));
		$this->assert_equals('INSERT INTO authors(`id`,`name`) VALUES(?,?)',(string)$this->sql);
	}

	public function test_insert_with_null()
	{
		$this->sql->insert(array('id' => 1, 'name' => null));
		$this->assert_equals('INSERT INTO authors(`id`,`name`) VALUES(?,?)',$this->sql->to_s());
	}

	public function test_update()
	{
		$this->sql->update(array('id' => 1, 'name' => 'Tito'))->where('id=1 AND name IN(?)',array('Tito','Mexican'));
 		$this->assert_equals('UPDATE authors SET `id`=?, `name`=? WHERE id=1 AND name IN(?,?)',(string)$this->sql);
 		$this->assert_equals(array(1,'Tito','Tito','Mexican'),$this->sql->bind_values());
	}

	public function test_update_with_null()
	{
		$this->sql->update(array('id' => 1, 'name' => null))->where('id=1');
		$this->assert_equals('UPDATE authors SET `id`=?, `name`=? WHERE id=1',$this->sql->to_s());
	}

	public function test_delete()
	{
		$this->sql->delete();
		$this->assert_equals('DELETE FROM authors',$this->sql->to_s());
	}

	public function test_delete_with_where()
	{
		$this->sql->delete('id=? or name in(?)',1,array('Tito','Mexican'));
		$this->assert_equals('DELETE FROM authors WHERE id=? or name in(?,?)',$this->sql->to_s());
		$this->assert_equals(array(1,'Tito','Mexican'),$this->sql->bind_values());
	}

	public function test_delete_with_hash()
	{
		$this->sql->delete(array('id' => 1, 'name' => array('Tito','Mexican')));
		$this->assert_equals('DELETE FROM authors WHERE `id`=? AND `name` IN(?,?)',$this->sql->to_s());
		$this->assert_equals(array(1,'Tito','Mexican'),$this->sql->get_where_values());
	}

	public function test_reverse_order()
	{
		$this->assert_equals('id ASC, name DESC', SQLBuilder::reverse_order('id DESC, name ASC'));
		$this->assert_equals('id ASC, name DESC , zzz ASC', SQLBuilder::reverse_order('id DESC, name ASC , zzz DESC'));
		$this->assert_equals('id DESC, name DESC', SQLBuilder::reverse_order('id, name'));
		$this->assert_equals('id DESC', SQLBuilder::reverse_order('id'));
		$this->assert_equals('', SQLBuilder::reverse_order(''));
		$this->assert_equals(' ', SQLBuilder::reverse_order(' '));
		$this->assert_equals(null, SQLBuilder::reverse_order(null));
	}

	public function test_create_conditions_from_underscored_string()
	{
		$x = array(1,'Tito','X');
		$this->assert_equals(array_merge(array('id=? AND name=? OR z=?'),$x),SQLBuilder::create_conditions_from_underscored_string('id_and_name_or_z',$x));

		$x = array(1);
		$this->assert_equals(array('id=?',1),SQLBuilder::create_conditions_from_underscored_string('id',$x));

		$x = array(array(1,2));
		$this->assert_equals(array_merge(array('id IN(?)'),$x),SQLBuilder::create_conditions_from_underscored_string('id',$x));
	}

	public function test_create_conditions_from_underscored_string_with_nulls()
	{
		$x = array(1,null);
		$this->assert_equals(array('id=? AND name IS NULL',1),SQLBuilder::create_conditions_from_underscored_string('id_and_name',$x));
	}

	public function test_create_conditions_from_underscored_string_with_missing_args()
	{
		$x = array(1,null);
		$this->assert_equals(array('id=? AND name IS NULL OR z IS NULL',1),SQLBuilder::create_conditions_from_underscored_string('id_and_name_or_z',$x));

		$this->assert_equals(array('id IS NULL'),SQLBuilder::create_conditions_from_underscored_string('id'));
	}

	public function test_create_conditions_from_underscored_string_with_blank()
	{
		$x = array(1,null,'');
		$this->assert_equals(array('id=? AND name IS NULL OR z=?',1,''),SQLBuilder::create_conditions_from_underscored_string('id_and_name_or_z',$x));
	}

	public function test_create_conditions_from_underscored_string_invalid()
	{
		$this->assert_equals(null,SQLBuilder::create_conditions_from_underscored_string(''));
		$this->assert_equals(null,SQLBuilder::create_conditions_from_underscored_string(null));
	}

	public function test_create_conditions_from_underscored_string_with_mapped_columns()
	{
		$x = array(1,'Tito');
		$map = array('my_name' => 'name');
		$conditions = SQLBuilder::create_conditions_from_underscored_string('id_and_my_name',$x,$map);
		$this->assert_equals(array('id=? AND name=?',1,'Tito'),$conditions);
	}

	public function test_create_hash_from_underscored_string()
	{
		$values = array(1,'Tito');
		$hash = SQLBuilder::create_hash_from_underscored_string('id_and_my_name',$values);
		$this->assert_equals(array('id' => 1, 'my_name' => 'Tito'),$hash);
	}

	public function test_create_hash_from_underscored_string_with_mapped_columns()
	{
		$values = array(1,'Tito');
		$map = array('my_name' => 'name');
		$hash = SQLBuilder::create_hash_from_underscored_string('id_and_my_name',$values,$map);
		$this->assert_equals(array('id' => 1, 'name' => 'Tito'),$hash);
	}
};
?>