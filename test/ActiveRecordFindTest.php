<?php

class ActiveRecordFindTest extends DatabaseTest
{
	/**
	 * @expectedException ActiveRecord\RecordNotFound
	 */
	public function test_find_with_no_params()
	{
		Author::find();
	}

	public function test_find_by_pk()
	{
		$author = Author::find(3);
		$this->assertEquals(3,$author->id);
	}

	/**
	 * @expectedException ActiveRecord\RecordNotFound
	 */
	public function test_find_by_pkno_results()
	{
		Author::find(99999999);
	}

	public function test_find_by_multiple_pk_with_partial_match()
	{
		try
		{
			Author::find(1,999999999);
			$this->fail();
		}
		catch (ActiveRecord\RecordNotFound $e)
		{
			$this->assertTrue(strpos($e->getMessage(),'found 1, but was looking for 2') !== false);
		}
	}

	public function test_find_by_pk_with_options()
	{
		$author = Author::find(3,array('order' => 'name'));
		$this->assertEquals(3,$author->id);
		$this->assertTrue(strpos(Author::table()->last_sql,'ORDER BY name') !== false);
	}

	public function test_find_by_pk_array()
	{
		$authors = Author::find(1,'2');
		$this->assertEquals(2, count($authors));
		$this->assertEquals(1, $authors[0]->id);
		$this->assertEquals(2, $authors[1]->id);
	}

	public function test_find_by_pk_array_with_options()
	{
		$authors = Author::find(1,'2',array('order' => 'name'));
		$this->assertEquals(2, count($authors));
		$this->assertTrue(strpos(Author::table()->last_sql,'ORDER BY name') !== false);
	}

	/**
	 * @expectedException ActiveRecord\RecordNotFound
	 */
	public function test_find_nothing_with_sql_in_string()
	{
		Author::first('name = 123123123');
	}

	public function test_find_all()
	{
		$authors = Author::find('all',array('conditions' => array('author_id IN(?)',array(1,2,3))));
		$this->assertTrue(count($authors) >= 3);
	}

	public function test_find_all_with_no_bind_values()
	{
		$authors = Author::find('all',array('conditions' => array('author_id IN(1,2,3)')));
		$this->assertEquals(1,$authors[0]->author_id);
	}

	/**
	 * @expectedException ActiveRecord\DatabaseException
	 */
	public function test_find_all_with_empty_array_bind_value_throws_exception()
	{
		$authors = Author::find('all',array('conditions' => array('author_id IN(?)', array())));
		$this->assertCount(0,$authors);
	}

	public function test_find_hash_using_alias()
	{
		$venues = Venue::all(array('conditions' => array('marquee' => 'Warner Theatre', 'city' => array('Washington','New York'))));
		$this->assertTrue(count($venues) >= 1);
	}

	public function test_find_hash_using_alias_with_null()
	{
		$venues = Venue::all(array('conditions' => array('marquee' => null)));
		$this->assertEquals(0,count($venues));
	}

	public function test_dynamic_finder_using_alias()
	{
		$this->assertNotNull(Venue::find_by_marquee('Warner Theatre'));
	}

	public function test_find_all_hash()
	{
		$books = Book::find('all',array('conditions' => array('author_id' => 1)));
		$this->assertTrue(count($books) > 0);
	}

	public function test_find_all_hash_with_order()
	{
		$books = Book::find('all',array('conditions' => array('author_id' => 1), 'order' => 'name DESC'));
		$this->assertTrue(count($books) > 0);
	}

	public function test_find_all_no_args()
	{
		$author = Author::all();
		$this->assertTrue(count($author) > 1);
	}

	public function test_find_all_no_results()
	{
		$authors = Author::find('all',array('conditions' => array('author_id IN(11111111111,22222222222,333333333333)')));
		$this->assertEquals(array(),$authors);
	}

	public function test_find_first()
	{
		$author = Author::find('first',array('conditions' => array('author_id IN(?)', array(1,2,3))));
		$this->assertEquals(1,$author->author_id);
		$this->assertEquals('Tito',$author->name);
	}

	public function test_find_first_no_results()
	{
		$this->assertNull(Author::find('first',array('conditions' => 'author_id=1111111')));
	}

	public function test_find_first_using_pk()
	{
		$author = Author::find('first',3);
		$this->assertEquals(3,$author->author_id);
	}

	public function test_find_first_with_conditions_as_string()
	{
		$author = Author::find('first',array('conditions' => 'author_id=3'));
		$this->assertEquals(3,$author->author_id);
	}

	public function test_find_all_with_conditions_as_string()
	{
		$author = Author::find('all',array('conditions' => 'author_id in(2,3)'));
		$this->assertEquals(2,count($author));
	}

	public function test_find_by_sql()
	{
		$author = Author::find_by_sql("SELECT * FROM authors WHERE author_id in(1,2)");
		$this->assertEquals(1,$author[0]->author_id);
		$this->assertEquals(2,count($author));
	}

	public function test_find_by_sqltakes_values_array()
	{
		$author = Author::find_by_sql("SELECT * FROM authors WHERE author_id=?",array(1));
		$this->assertNotNull($author);
	}

	public function test_find_with_conditions()
	{
		$author = Author::find(array('conditions' => array('author_id=? and name=?', 1, 'Tito')));
		$this->assertEquals(1,$author->author_id);
	}

	public function test_find_last()
	{
		$author = Author::last();
		$this->assertEquals(4, $author->author_id);
		$this->assertEquals('Uncle Bob',$author->name);
	}

	public function test_find_last_using_string_condition()
	{
		$author = Author::find('last', array('conditions' => 'author_id IN(1,2,3,4)'));
		$this->assertEquals(4, $author->author_id);
		$this->assertEquals('Uncle Bob',$author->name);
	}

	public function test_limit_before_order()
	{
		$authors = Author::all(array('limit' => 2, 'order' => 'author_id desc', 'conditions' => 'author_id in(1,2)'));
		$this->assertEquals(2,$authors[0]->author_id);
		$this->assertEquals(1,$authors[1]->author_id);
	}

	public function test_for_each()
	{
		$i = 0;
		$res = Author::all();

		foreach ($res as $author)
		{
			$this->assertTrue($author instanceof ActiveRecord\Model);
			$i++;
		}
		$this->assertTrue($i > 0);
	}

	public function test_fetch_all()
	{
		$i = 0;

		foreach (Author::all() as $author)
		{
			$this->assertTrue($author instanceof ActiveRecord\Model);
			$i++;
		}
		$this->assertTrue($i > 0);
	}

	public function test_count()
	{
		$this->assertSame(1,Author::count(1));
		$this->assertSame(2,Author::count(array(1,2)));
		$this->assertTrue(Author::count() > 1);
		$this->assertSame(0,Author::count(array('conditions' => 'author_id=99999999999999')));
		$this->assertSame(2,Author::count(array('conditions' => 'author_id=1 or author_id=2')));
		$this->assertSame(1,Author::count(array('name' => 'Tito', 'author_id' => 1)));
	}

	public function test_gh149_empty_count()
	{
		$total = Author::count();
		$this->assertEquals($total, Author::count(null));
		$this->assertEquals($total, Author::count(array()));
	}

	public function test_exists()
	{
		$this->assertTrue(Author::exists(1));
		$this->assertTrue(Author::exists(array('conditions' => 'author_id=1')));
		$this->assertTrue(Author::exists(array('conditions' => array('author_id=? and name=?', 1, 'Tito'))));
		$this->assertFalse(Author::exists(9999999));
		$this->assertFalse(Author::exists(array('conditions' => 'author_id=999999')));
	}

	public function test_find_by_call_static()
	{
		$this->assertEquals('Tito',Author::find_by_name('Tito')->name);
		$this->assertEquals('Tito',Author::find_by_author_id_and_name(1,'Tito')->name);
		$this->assertEquals('George W. Bush',Author::find_by_author_id_or_name(2,'Tito',array('order' => 'author_id desc'))->name);
		$this->assertEquals('Tito',Author::find_by_name(array('Tito','George W. Bush'),array('order' => 'name desc'))->name);
	}

	public function test_find_by_call_static_no_results()
	{
		$this->assertNull(Author::find_by_name('SHARKS WIT LASERZ'));
		$this->assertNull(Author::find_by_name_or_author_id());
	}

	/**
	 * @expectedException ActiveRecord\DatabaseException
	 */
	public function test_find_by_call_static_invalid_column_name()
	{
		Author::find_by_sharks();
	}

	public function test_find_all_by_call_static()
	{
		$x = Author::find_all_by_name('Tito');
		$this->assertEquals('Tito',$x[0]->name);
		$this->assertEquals(1,count($x));

		$x = Author::find_all_by_author_id_or_name(2,'Tito',array('order' => 'name asc'));
		$this->assertEquals(2,count($x));
		$this->assertEquals('George W. Bush',$x[0]->name);
	}

	public function test_find_all_by_call_static_no_results()
	{
		$x = Author::find_all_by_name('SHARKSSSSSSS');
		$this->assertEquals(0,count($x));
	}

	public function test_find_all_by_call_static_with_array_values_and_options()
	{
		$author = Author::find_all_by_name(array('Tito','Bill Clinton'),array('order' => 'name desc'));
		$this->assertEquals('Tito',$author[0]->name);
		$this->assertEquals('Bill Clinton',$author[1]->name);
	}

	/**
	 * @expectedException ActiveRecord\ActiveRecordException
	 */
	public function test_find_all_by_call_static_undefined_method()
	{
		Author::find_sharks('Tito');
	}

	public function test_find_all_takes_limit_options()
	{
		$authors = Author::all(array('limit' => 1, 'offset' => 2, 'order' => 'name desc'));
		$this->assertEquals('George W. Bush',$authors[0]->name);
	}

	/**
	 * @expectedException ActiveRecord\ActiveRecordException
	 */
	public function test_find_by_call_static_with_invalid_field_name()
	{
		Author::find_by_some_invalid_field_name('Tito');
	}

	public function test_find_with_select()
	{
		$author = Author::first(array('select' => 'name, 123 as bubba', 'order' => 'name desc'));
		$this->assertEquals('Uncle Bob',$author->name);
		$this->assertEquals(123,$author->bubba);
	}

	public function test_find_with_select_non_selected_fields_should_not_have_attributes()
	{
		$author = Author::first(array('select' => 'name, 123 as bubba'));
		try {
			$author->id;
			$this->fail('expected ActiveRecord\UndefinedPropertyExecption');
		} catch (ActiveRecord\UndefinedPropertyException $e) {
			;
		}
	}

	public function test_joins_on_model_with_association_and_explicit_joins()
	{
		JoinBook::$belongs_to = array(array('author'));
		JoinBook::first(array('joins' => array('author','LEFT JOIN authors a ON(books.secondary_author_id=a.author_id)')));
		$this->assertSqlHas('INNER JOIN authors ON(books.author_id = authors.author_id)',JoinBook::table()->last_sql);
		$this->assertSqlHas('LEFT JOIN authors a ON(books.secondary_author_id=a.author_id)',JoinBook::table()->last_sql);
	}

	public function test_joins_on_model_with_explicit_joins()
	{
		JoinBook::first(array('joins' => array('LEFT JOIN authors a ON(books.secondary_author_id=a.author_id)')));
		$this->assertSqlHas('LEFT JOIN authors a ON(books.secondary_author_id=a.author_id)',JoinBook::table()->last_sql);
	}

	public function test_group()
	{
		$venues = Venue::all(array('select' => 'state', 'group' => 'state'));
		$this->assertTrue(count($venues) > 0);
		$this->assertSqlHas('GROUP BY state',ActiveRecord\Table::load('Venue')->last_sql);
	}

	public function test_group_with_order_and_limit_and_having()
	{
		$venues = Venue::all(array('select' => 'state', 'group' => 'state', 'having' => 'length(state) = 2', 'order' => 'state', 'limit' => 2));
		$this->assertTrue(count($venues) > 0);
		$this->assertSqlHas($this->conn->limit('SELECT state FROM venues GROUP BY state HAVING length(state) = 2 ORDER BY state',null,2),Venue::table()->last_sql);
	}

	public function test_escape_quotes()
	{
		$author = Author::find_by_name("Tito's");
		$this->assertNotEquals("Tito's",Author::table()->last_sql);
	}

	public function test_from()
	{
		$author = Author::find('first', array('from' => 'books', 'order' => 'author_id asc'));
		$this->assertTrue($author instanceof Author);
		$this->assertNotNull($author->book_id);

		$author = Author::find('first', array('from' => 'authors', 'order' => 'author_id asc'));
		$this->assertTrue($author instanceof Author);
		$this->assertEquals(1, $author->id);
	}

	public function test_having()
	{
		if ($this->conn instanceof ActiveRecord\OciAdapter)
		{
			$author = Author::first(array(
				'select' => 'to_char(created_at,\'YYYY-MM-DD\') as created_at',
				'group'  => 'to_char(created_at,\'YYYY-MM-DD\')',
				'having' => "to_char(created_at,'YYYY-MM-DD') > '2009-01-01'"));
			$this->assertSqlHas("GROUP BY to_char(created_at,'YYYY-MM-DD') HAVING to_char(created_at,'YYYY-MM-DD') > '2009-01-01'",Author::table()->last_sql);
		}
		else
		{
			$author = Author::first(array(
				'select' => 'date(created_at) as created_at',
				'group'  => 'date(created_at)',
				'having' => "date(created_at) > '2009-01-01'"));
			$this->assertSqlHas("GROUP BY date(created_at) HAVING date(created_at) > '2009-01-01'",Author::table()->last_sql);
		}
	}

	/**
	 * @expectedException ActiveRecord\DatabaseException
	 */
	public function test_from_with_invalid_table()
	{
		$author = Author::find('first', array('from' => 'wrong_authors_table'));
	}

	public function test_find_with_hash()
	{
		$this->assertNotNull(Author::find(array('name' => 'Tito')));
		$this->assertNotNull(Author::find('first',array('name' => 'Tito')));
		$this->assertEquals(1,count(Author::find('all',array('name' => 'Tito'))));
		$this->assertEquals(1,count(Author::all(array('name' => 'Tito'))));
	}

	public function test_find_or_create_by_on_existing_record()
	{
		$this->assertNotNull(Author::find_or_create_by_name('Tito'));
	}

	public function test_find_or_create_by_creates_new_record()
	{
		$author = Author::find_or_create_by_name_and_encrypted_password('New Guy','pencil');
		$this->assertTrue($author->author_id > 0);
		$this->assertEquals('pencil',$author->encrypted_password);
	}

	/**
	 * @expectedException ActiveRecord\ActiveRecordException
	 */
	public function test_find_or_create_by_throws_exception_when_using_or()
	{
		Author::find_or_create_by_name_or_encrypted_password('New Guy','pencil');
	}

	/**
	 * @expectedException ActiveRecord\RecordNotFound
	 */
	public function test_find_by_zero()
	{
		Author::find(0);
	}

	/**
	 * @expectedException ActiveRecord\RecordNotFound
	 */
	public function test_find_by_null()
	{
		Author::find(null);
	}

	public function test_count_by()
	{
		$this->assertEquals(2,Venue::count_by_state('VA'));
		$this->assertEquals(3,Venue::count_by_state_or_name('VA','Warner Theatre'));
		$this->assertEquals(0,Venue::count_by_state_and_name('VA','zzzzzzzzzzzzz'));
	}

	public function test_find_by_pk_should_not_use_limit()
	{
		Author::find(1);
		$this->assertSqlHas('SELECT * FROM authors WHERE author_id=?',Author::table()->last_sql);
	}

	public function test_find_by_datetime()
	{
		$now = new DateTime();
		$arnow = new ActiveRecord\DateTime();
		$arnow->setTimestamp($now->getTimestamp());

		Author::find(1)->update_attribute('created_at',$now);
		$this->assertNotNull(Author::find_by_created_at($now));
		$this->assertNotNull(Author::find_by_created_at($arnow));
	}
}
