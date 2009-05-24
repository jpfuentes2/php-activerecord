<?php
include 'helpers/config.php';

class ActiveRecordWriteTest extends DatabaseTest
{
	public function testSave()
	{
		$venue = new Venue(array('name' => 'Tito'));
		$venue->save();
	}

	public function testInsert()
	{
		$author = new Author(array('name' => 'Blah Blah'));
		$author->insert();
		$this->assertNotNull(Author::find($author->id));
	}

	public function testSaveAutoIncrementId()
	{
		$venue = new Venue(array('name' => 'Bob'));
		$venue->save();
		$this->assertTrue($venue->id > 0);
	}

	public function testDelete()
	{
		$author = Author::find(1);
		$author->delete();

		$this->assertFalse(Author::exists(1));
	}

	public function testDeleteByFindAll()
	{
		$books = Book::all();

		foreach ($books as $model)
			$model->delete();

		$res = Book::all();
		$this->assertEquals(0,count($res));
	}

	public function testUpdate()
	{
		$book = Book::find(1);
		$new_name = 'new name';
		$book->name = $new_name;
		$book->update();

		$this->assertSame($new_name, $book->name);
		$this->assertSame($new_name, $book->name, Book::find(1)->name);
	}

	public function testUpdateAttributes()
	{
		$book = Book::find(1);
		$new_name = 'How to lose friends and alienate people'; // jax i'm worried about you
		$attrs = array('name' => $new_name);
		$book->update_attributes($attrs);

		$this->assertSame($new_name, $book->name);
		$this->assertSame($new_name, $book->name, Book::find(1)->name);
	}

	/**
	 * @expectedException ActiveRecord\UndefinedPropertyException
	 */
	public function testUpdateAttributesUndefinedProperty()
	{
		$book = Book::find(1);
		$book->update_attributes(array('name' => 'new name', 'invalid_attribute' => true , 'another_invalid_attribute' => 'blah'));
	}

	public function testUpdateAttribute()
	{
		$book = Book::find(1);
		$new_name = 'some stupid self-help book';
		$book->update_attribute('name', $new_name);

		$this->assertSame($new_name, $book->name);
		$this->assertSame($new_name, $book->name, Book::find(1)->name);
	}

	/**
	 * @expectedException ActiveRecord\UndefinedPropertyException
	 */
	public function testUpdateAttributeUndefinedProperty()
	{
		$book = Book::find(1);
		$book->update_attribute('invalid_attribute', true);
	}

	public function testSaveNullValue()
	{
		$book = Book::first();
		$book->name = null;
		$book->save();
		$this->assertTrue(Book::first()->name === null);
	}

	public function testSaveBlankValue()
	{
		$book = Book::first();
		$book->name = '';
		$book->save();
		$this->assertTrue(Book::first()->name === '');
	}

	public function testDirtyAttributes()
	{
		$book = $this->makeNewBookAnd();
		$this->assertEquals(array('name','special'),array_keys($book->dirty_attributes()));
	}

	public function testDirtyAttributesClearedAfterSaving()
	{
		$book = $this->makeNewBookAnd('save');
		$this->assertTrue(strpos($book->table()->last_sql,'(name,special)') !== false);
		$this->assertEquals(null,$book->dirty_attributes());
	}

	public function testDirtyAttributesClearedAfterInserting()
	{
		$book = $this->makeNewBookAnd('insert');
		$this->assertEquals(null,$book->dirty_attributes());
	}

	public function testNoDirtyAttributesButStillInsertRecord()
	{
		$book = new Book;
		$this->assertEquals(null,$book->dirty_attributes());
		$book->save();
		$this->assertEquals(null,$book->dirty_attributes());
		$this->assertNotNull($book->id);
	}

	public function testDirtyAttributesClearedAfterUpdating()
	{
		$book = Book::first();
		$book->name = 'rivers cuomos';
		$book->update();
		$this->assertEquals(null,$book->dirty_attributes());
	}

	public function testDirtyAttributesAfterReloading()
	{
		$book = Book::first();
		$book->name = 'rivers cuomos';
		$book->reload();
		$this->assertEquals(null,$book->dirty_attributes());
	}

	public function testDirtyAttributesWithMassAssignment()
	{
		$book = Book::first();
		$book->set_attributes(array('name' => 'rivers cuomo'));
		$this->assertEquals(array('name'), array_keys($book->dirty_attributes()));
	}

	public function testTimestampsSetBeforeSave()
	{
		$author = new Author;
		$author->save();
		$this->assertNotNull($author->created_at, $author->updated_at);

		$author->reload();
		$this->assertNotNull($author->created_at, $author->updated_at);
	}

	public function testTimestampsUpdatedAtOnlySetBeforeUpdate()
	{
		$author = Author::find(1);
		$created_at = $author->created_at;
		$updated_at = $author->updated_at;
		$author->name = 'test';
		$author->save();

		$this->assertNotNull($author->updated_at);
		$this->assertSame($created_at, $author->created_at);
		$this->assertNotEquals($updated_at, $author->updated_at);
	}

	public function testCreate()
	{
		$author = Author::create(array('name' => 'Blah Blah'));
		$this->assertNotNull(Author::find($author->id));
	}

	public function testCreateShouldSetCreatedAt()
	{
		$author = Author::create(array('name' => 'Blah Blah'));
		$this->assertNotNull($author->created_at);
	}

	private function makeNewBookAnd($method=null)
	{
		$book = new Book();
		$book->name = 'rivers cuomo';
		$book->special = 1;

		if ($method)
			$book->$method();

		return $book;
	}
};