<?
include 'helpers/config.php';

class ActiveRecordFindTest extends DatabaseTest
{
	/**
	 * @expectedException ActiveRecord\RecordNotFound
	 */
	public function testFindWithNoParams()
	{
		Author::find();
	}

	public function testFindByPK()
	{
		$author = Author::find(3);
		$this->assertEquals(3,$author->id);
	}

	/**
	 * @expectedException ActiveRecord\RecordNotFound
	 */
	public function testFindByPKNoResults()
	{
		Author::find(999999999999);
	}

	public function testFindByMultiplePKWithPartialMatch()
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

	public function testFindByPKWithOptions()
	{
		$author = Author::find(3,array('order' => 'name'));
		$this->assertEquals(3,$author->id);
		$this->assertTrue(strpos(Author::table()->last_sql,'ORDER BY name') !== false);
	}

	public function testFindByPKArray()
	{
		$authors = Author::find(1,'2');
		$this->assertEquals(2, count($authors));
		$this->assertEquals(1, $authors[0]->id);
		$this->assertEquals(2, $authors[1]->id);
	}

	public function testFindByPKArrayWithOptions()
	{
		$authors = Author::find(1,'2',array('order' => 'name'));
		$this->assertEquals(2, count($authors));
		$this->assertTrue(strpos(Author::table()->last_sql,'ORDER BY name') !== false);
	}

	/**
	 * @expectedException ActiveRecord\RecordNotFound
	 */
	public function testFindByPKWithSQLInString()
	{
		Author::first('name = 123123123');
	}

	public function testFindAll()
	{
		$authors = Author::find('all',array('conditions' => array('author_id IN(?)',array(1,2,3))));
		$this->assertTrue(count($authors) >= 3);
	}

	public function testFindAllWithNoBindValues()
	{
		$authors = Author::find('all',array('conditions' => array('author_id IN(1,2,3)')));
		$this->assertEquals(1,$authors[0]->author_id);
	}

	public function testFindAllUsingAllAlias()
	{
		$authors = Author::all(array('conditions' => array('author_id IN(?)',array(1,2,3))));
		$this->assertTrue(count($authors) >= 3);
	}

	public function testFindAllHash()
	{
		$books = Book::find('all',array('conditions' => array('author_id' => 1)));
		$this->assertTrue(count($books) > 0);
	}

	public function testFindAllHashWithOrder()
	{
		$books = Book::find('all',array('conditions' => array('author_id' => 1), 'order' => 'name DESC'));
		$this->assertTrue(count($books) > 0);
	}

	/**
	 * @expectedException ActiveRecord\ActiveRecordException
	 */
	public function testFindAllWithInvalidOption()
	{
		Book::find('all',array('author_id' => 1));
	}

	public function testFindAllNoArgs()
	{
		$author = Author::all();
		$this->assertTrue(count($author) > 1);
	}

	public function testFindAllNoResults()
	{
		$authors = Author::find('all',array('conditions' => array('author_id IN(11111111111,22222222222,333333333333)')));
		$this->assertEquals(array(),$authors);
	}

	public function testFindFirst()
	{
		$author = Author::find('first',array('conditions' => array('author_id IN(?)', array(1,2,3))));
		$this->assertEquals(1,$author->author_id);
		$this->assertEquals('Tito',$author->name);
	}

	public function testFindFirstNoResults()
	{
		$this->assertNull(Author::find('first',array('conditions' => 'author_id=1111111')));
	}

	public function testFindFirstUsingPK()
	{
		$author = Author::find('first',3);
		$this->assertEquals(3,$author->author_id);
	}

	public function testFindFirstWithConditionsAsString()
	{
		$author = Author::find('first',array('conditions' => 'author_id=3'));
		$this->assertEquals(3,$author->author_id);
	}

	public function testFindAllWithConditionsAsString()
	{
		$author = Author::find('all',array('conditions' => 'author_id in(2,3)'));
		$this->assertEquals(2,count($author));
	}

	public function testFindBySQL()
	{
		$author = Author::find_by_sql("SELECT * FROM authors WHERE author_id in(1,2)");
		$this->assertEquals(1,$author[0]->author_id);
		$this->assertEquals(2,count($author));
	}

	public function testFindLast()
	{
		$author = Author::last();
		$this->assertEquals(3, $author->author_id);
		$this->assertEquals('Bill Clinton',$author->name);
	}

	public function testFindLastUsingStringCondition()
	{
		$author = Author::find('last', array('conditions' => 'author_id IN(1,2,3)'));
		$this->assertEquals(3, $author->author_id);
		$this->assertEquals('Bill Clinton',$author->name);
	}

	public function testLimitBeforeOrder()
	{
		$authors = Author::all(array('limit' => 2, 'order' => 'author_id desc', 'conditions' => 'author_id in(1,2)'));
		$this->assertEquals(2,$authors[0]->author_id);
		$this->assertEquals(1,$authors[1]->author_id);
	}

	public function testForEach()
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

	public function testFetchAll()
	{
		$i = 0;

		foreach (Author::all() as $author)
		{
			$this->assertTrue($author instanceof ActiveRecord\Model);
			$i++;
		}
		$this->assertTrue($i > 0);
	}

	public function testCount()
	{
		$this->assertTrue(Author::count(1) == 1);
		$this->assertTrue(Author::count() > 1);
		$this->assertEquals(0,Author::count(array('conditions' => 'author_id=99999999999999')));
	}

	public function testExists()
	{
		$this->assertTrue(Author::exists(1));
		$this->assertTrue(Author::exists(array('conditions' => 'author_id=1')));
		$this->assertFalse(Author::exists(9999999));
		$this->assertFalse(Author::exists(array('conditions' => 'author_id=999999')));
	}

	public function testFindByCallStatic()
	{
		$this->assertEquals('Tito',Author::find_by_name('Tito')->name);
		$this->assertEquals('Tito',Author::find_by_author_id_and_name(1,'Tito')->name);
		$this->assertEquals('George W. Bush',Author::find_by_author_id_or_name(2,'Tito',array('order' => 'author_id desc'))->name);
		$this->assertEquals('Tito',Author::find_by_name(array('Tito','George W. Bush'),array('order' => 'name desc'))->name);
	}

	public function testFindByCallStaticNoResults()
	{
		$this->assertNull(Author::find_by_name('SHARKS WIT LASERZ'));
		$this->assertNull(Author::find_by_name_or_author_id());
	}

	/**
	 * @expectedException ActiveRecord\DatabaseException
	 */
	public function testFindByCallStaticInvalidColumnName()
	{
		Author::find_by_sharks();
	}

	public function testFindAllByCallStatic()
	{
		$x = Author::find_all_by_name('Tito');
		$this->assertEquals('Tito',$x[0]->name);
		$this->assertEquals(1,count($x));

		$x = Author::find_all_by_author_id_or_name(2,'Tito',array('order' => 'name asc'));
		$this->assertEquals(2,count($x));
		$this->assertEquals('George W. Bush',$x[0]->name);
	}

	public function testFindAllByCallStaticNoResults()
	{
		$x = Author::find_all_by_name('SHARKSSSSSSS');
		$this->assertEquals(0,count($x));
	}

	public function testFindAllByCallStaticWithArrayValuesAndOptions()
	{
		$author = Author::find_all_by_name(array('Tito','Bill Clinton'),array('order' => 'name desc'));
		$this->assertEquals('Tito',$author[0]->name);
		$this->assertEquals('Bill Clinton',$author[1]->name);
	}

	/**
	 * @expectedException ActiveRecord\ActiveRecordException
	 */
	public function testFindAllByCallStaticUndefinedMethod()
	{
		Author::find_sharks('Tito');
	}

	/**
	 * @expectedException ActiveRecord\ActiveRecordException
	 */
	public function testFindByCallStaticWithInvalidFieldName()
	{
		Author::find_by_some_invalid_field_name('Tito');
	}

	public function testFindWithSelect()
	{
		$author = Author::first(array('select' => 'name, 123 as bubba'));
		$this->assertEquals('Tito',$author->name);
		$this->assertEquals(123,$author->bubba);
	}

	public function testFindWithSelectNonSelectedFieldsShouldNotHaveAttributes()
	{
		$author = Author::first(array('select' => 'name, 123 as bubba'));
		try {
			$author->id;
			$this->fail('expected ActiveRecord\UndefinedPropertyExecption');
		} catch (ActiveRecord\UndefinedPropertyException $e) {
			;
		}
	}

	public function testJoinsOnModelWithInferredStuff()
	{
		$x = JoinBook::first(array('joins' => array('Author','LEFT JOIN authors a ON(books.secondary_author_id=a.author_id)')));
		$this->assertTrue(strpos(JoinBook::table()->last_sql,'INNER JOIN authors ON(books.author_id=authors.author_id)') !== false);
		$this->assertTrue(strpos(JoinBook::table()->last_sql,'LEFT JOIN authors a ON(books.secondary_author_id=a.author_id)') !== false);
	}

	public function testJoinsOnModelWithExplicitPkAndTable()
	{
		JoinBook::first(array('joins' => array('JoinAuthor','LEFT JOIN authors a ON(books.secondary_author_id=a.author_id)')));
		$this->assertTrue(strpos(JoinBook::table()->last_sql,'INNER JOIN authors ON(books.author_id=authors.author_id)') !== false);
		$this->assertTrue(strpos(JoinBook::table()->last_sql,'LEFT JOIN authors a ON(books.secondary_author_id=a.author_id)') !== false);
	}

	public function testGroup()
	{
		$venues = Venue::all(array('group' => 'state'));
		$this->assertTrue(count($venues) > 0);
		$this->assertTrue(strpos(ActiveRecord\Table::load('Venue')->last_sql, 'GROUP BY state') !== false);
	}

	public function testEscapeQuotes()
	{
		$author = Author::find_by_name("Tito's");
		$this->assertNotEquals("Tito's",Author::table()->last_sql);
	}
};
?>
