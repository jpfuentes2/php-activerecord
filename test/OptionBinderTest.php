<?php

class ScopedAuthor extends Author {
  static $table_name = 'authors';

  public static function top_three() {
    return static::limit(3)->order('name ASC');
  }

  public static function named_like($name) {
    return static::where('name LIKE ?', '%' . $name . '%');
  }

  public static function id_lower_than($id) {
    return static::where('author_id < ?', $id);
  }

};

class OptionBinderTest extends DatabaseTest
{

  public function setUp() 
  {
    parent::setUp();
    $this->query = Author::scoped();
  }

  /*public function test_all() 
  {
    $results = $this->query->all(); 
    $this->assertEquals(4, count($results));
    foreach($results as $result)
      $this->assertEquals(Author::find($result->id), $result);
  }

  public function test_first() 
  {
    $result = $this->query->first();
    $this->assertEquals('Tito', $result->name);
  }

  public function test_last() 
  {
    $result = $this->query->last();
    $this->assertEquals('Uncle Bob', $result->name);
  }

  public function test_order() 
  {
    $results = $this->query->order('name ASC')->all();
    $this->assertEquals('Bill Clinton', $results[0]->name);
  }

  public function test_limit() 
  {
    $results = $this->query->order('name ASC')->limit(1)->all();
    $this->assertEquals(1, count($results));
  }

  public function test_offset() 
  {
    $results = $this->query->order('name DESC')->limit(1)->offset(1)->all();
    $this->assertEquals('Tito', $results[0]->name);
  }

  public function test_group() 
  {
    $results = $this->query->group('parent_author_id')->all();
    $this->assertEquals(3, count($results));
  }

  /**
   * @expectedException ActiveRecord\UndefinedPropertyException
   */
   /*
  public function test_select() 
  {
    $results = $this->query->select('author_id')->all();
    $results[0]->name; 
  }

  public function test_having()
   {
    $result = $this->query->having('author_id > 2')->first();
    $this->assertEquals('Bill Clinton', $result->name);
  }

  public function test_where_string()
  {
    $result = $this->query->where('author_id > 2')->first();
    $this->assertEquals('Bill Clinton', $result->name);
  }

  public function test_where_quote() 
  {
    $result = $this->query->where('name = ?', 'Tito')->first();
    $this->assertEquals(1, $result->id);
  }

  public function test_where_hash()
  {
    $result = $this->query->where(array('name' => 'Uncle Bob'))->last();
    $this->assertEquals(4, $result->id);
  }

  public function test_where_array() 
  {
    $results = $this->query->where('author_id=? AND name IN(?)',1,array('Tito','Mexican'))->all();
    $this->assertEquals(1, $results[0]->id);
    $this->assertEquals(1, count($results));
  }

  public function test_multiple_wheres() 
  {
    $results = $this->query->where(array('parent_author_id' => 2))->where('author_id > ?', 2)->order('name ASC')->all();
    $this->assertEquals('Uncle Bob', $results[0]->name);
    $this->assertEquals(1, count($results));
  }

  public function test_multiple_wheres_array() 
  {
    $results = $this->query
      ->where('author_id IN (?)', array(1,2,3,4))
      ->where('name LIKE ? AND name <> ?', '%b%', 'George W. Bush')
      ->where(array('parent_author_id' => 2))
      ->order('name ASC')
      ->all();

    $this->assertEquals('Uncle Bob', $results[0]->name);
    $this->assertEquals(1, count($results));
  }*/

  public function test_multiple_wheres_merge()
  {
    $query = Author::scoped()->where("name = 'name'");
    $other = $query->where(array('id' => 3, 'active' => 1))->where("category = 'movies'");
    $query->add_scope($other->get_options());

    $options = $query->get_options();
    $conditions = $options['conditions'];

    $this->assert_sql_has("name = 'name' AND id=? AND active=? AND category = 'movies'", $conditions[0]);
    $this->assertEquals(3, $conditions[1]);
    $this->assertEquals(1, $conditions[2]);
  }

  /**
   * @expectedException ActiveRecord\ReadOnlyException
   */
  // public function test_readonly() 
  // {
    // $result = $this->query->readonly()->first();
    // $result->name = '';
    // $result->save();
  // }  

  // public function test_alias_order() 
  // {
    // $this->assertEquals($this->query->order('name ASC')->first(), Author::scoped()->order('name ASC')->first());
  // }
// 
  // public function test_alias_limit() 
  // {
    // $this->assertEquals($this->query->limit(1)->all(), Author::scoped()->limit(1)->all());
  // }
//   
  // public function test_alias_group() 
  // {
    // $this->assertEquals($this->query->group('parent_author_id')->all(), Author::scoped()->group('parent_author_id')->all());
  // }
//   
  // public function test_alias_offset() 
  // {
    // $this->assertEquals($this->query->offset(2)->first(), Author::scoped()->offset(2)->first());
  // }
// 
  // public function test_alias_select() 
  // {
    // $this->assertEquals($this->query->select('author_id')->first(), Author::scoped()->select('author_id')->first());
  // }
// 
  // public function test_alias_having() 
  // {
    // $this->assertEquals($this->query->having('author_id > 2')->all(), Author::scoped()->having('author_id > 2')->all());
  // }
// 
  // public function test_alias_where() 
  // {
    // $this->assertEquals($this->query->where('author_id = 1')->all(), Author::scoped()->where('author_id = 1')->all());
  // }
// 
  // public function test_single_scope() 
  // {
    // $results = ScopedAuthor::top_three()->all();
    // $this->assertEquals(3, count($results));
    // $this->assertEquals('Bill Clinton', $results[0]->name);
  // }
// 
  // public function test_single_scope_with_default_scopes() 
  // {
    // $results = ScopedAuthor::scoped()->offset(1)->top_three()->limit(2)->all();
    // $this->assertEquals(2, count($results));
    // $this->assertEquals('George W. Bush', $results[0]->name);
  // }
// 
  // public function test_multiple_scopes() 
  // {
    // $results = ScopedAuthor::scoped()->top_three()->named_like('b')->all();
    // $this->assertEquals(3, count($results));
    // $this->assertEquals('Bill Clinton', $results[0]->name);
    // $this->assertEquals('George W. Bush', $results[1]->name);
    // $this->assertEquals('Uncle Bob', $results[2]->name);
  // }
// 
  // public function test_multiple_scopes_with_default_scopes() 
  // {
    // $results = ScopedAuthor::scoped()->offset(1)->top_three()->limit(2)->named_like('b')->order('author_id DESC')->all();
    // $this->assertEquals(2, count($results));
    // $this->assertEquals('Bill Clinton', $results[0]->name);
    // $this->assertEquals('George W. Bush', $results[1]->name);
  // }
// 
  // public function test_multiple_wheres_in_scopes() 
  // {
    // $results = ScopedAuthor::named_like('b')->id_lower_than(4)->order('author_id DESC')->all();
    // $this->assertEquals(2, count($results));
    // $this->assertEquals('Bill Clinton', $results[0]->name);
    // $this->assertEquals('George W. Bush', $results[1]->name);
  // }

}