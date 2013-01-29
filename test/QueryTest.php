<?php

include 'helpers/config.php';

class ScopedAuthor extends Author {
  static $table_name = 'authors';

  public static function top_three() {
    return static::limit(3)->order('name ASC');
  }

  public static function named_like($name) {
    return static::where('name LIKE ?', '%' . $name . '%');
  }

};

class QueryTest extends DatabaseTest
{

  public function setUp() 
  {
    parent::setUp();
    $this->query = Author::sql();
  }

  public function test_all() 
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

  public function test_where_quote() {
    $result = $this->query->where('name = ?', 'Tito')->first();
    $this->assertEquals(1, $result->id);
  }

  public function test_where_hash() {
    $result = $this->query->where(array('name' => 'Uncle Bob'))->last();
    $this->assertEquals(4, $result->id);
  }

  public function test_where_array() {
    $results = $this->query->where('author_id=? AND name IN(?)',1,array('Tito','Mexican'))->all();
    $this->assertEquals(1, $results[0]->id);
    $this->assertEquals(1, count($results));
  }

  public function test_multiple_wheres() { // Not implemented yet
    $results = $this->query->where(array('parent_author_id' => 2))->where('author_id > ?', 2)->order('name ASC')->all();
    //$this->assertEquals('Uncle Bob', $results[0]->name);
    //$this->assertEquals(1, count($results));
  }

  /**
   * @expectedException ActiveRecord\ReadOnlyException
   */
  public function test_readonly() 
  {
    $result = $this->query->readonly()->first();
    $result->name = '';
    $result->save();
  }  

  public function test_alias_order() 
  {
    $this->assertEquals($this->query->order('name ASC')->first(), Author::order('name ASC')->first());
  }

  public function test_alias_limit() 
  {
    $this->assertEquals($this->query->limit(1)->all(), Author::limit(1)->all());
  }
  
  public function test_alias_group() 
  {
    $this->assertEquals($this->query->group('parent_author_id')->all(), Author::group('parent_author_id')->all());
  }
  
  public function test_alias_offset() 
  {
    $this->assertEquals($this->query->offset(2)->first(), Author::offset(2)->first());
  }

  public function test_alias_select() 
  {
    $this->assertEquals($this->query->select('author_id')->first(), Author::select('author_id')->first());
  }

  public function test_alias_having() 
  {
    $this->assertEquals($this->query->having('author_id > 2')->all(), Author::having('author_id > 2')->all());
  }

  public function test_alias_where() 
  {
    $this->assertEquals($this->query->where('author_id = 1')->all(), Author::where('author_id = 1')->all());
  }

  public function test_single_scope() 
  {
    $results = ScopedAuthor::top_three()->all();
    $this->assertEquals(3, count($results));
    $this->assertEquals('Bill Clinton', $results[0]->name);
  }

  public function test_single_scope_with_default_scopes() 
  {
    $results = ScopedAuthor::offset(1)->top_three()->limit(2)->all();
    $this->assertEquals(2, count($results));
    $this->assertEquals('George W. Bush', $results[0]->name);
  }

  public function test_multiple_scopes() 
  {
    $results = ScopedAuthor::top_three()->named_like('b')->all();
    $this->assertEquals(3, count($results));
    $this->assertEquals('Bill Clinton', $results[0]->name);
    $this->assertEquals('George W. Bush', $results[1]->name);
    $this->assertEquals('Uncle Bob', $results[2]->name);
  }

  public function test_multiple_scopes_with_default_scopes() 
  {
    $results = ScopedAuthor::offset(1)->top_three()->limit(2)->named_like('b')->order('author_id DESC')->all();
    $this->assertEquals(2, count($results));
    $this->assertEquals('Bill Clinton', $results[0]->name);
    $this->assertEquals('George W. Bush', $results[1]->name);
  }

}