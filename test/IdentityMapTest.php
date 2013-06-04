<?php 
include 'helpers/config.php';

class IdentityMapTest extends DatabaseTest
{
	public function set_up($connection_name = NULL)
	{
		parent::set_up($connection_name);
		ActiveRecord\Config::instance()->set_identity_map(true);
	}

	public function tear_down()
	{
		ActiveRecord\Config::instance()->set_identity_map(false);
		parent::tear_down();
	}


	public function test_persistence_between_model_instances()
	{
		$author1 = Author::find(1);
		$author2 = Author::find(1);

		$this->assert_equals($author1->name, $author2->name);

		$author1->name = 'A New Title';

		$this->assert_equals($author1->name, $author2->name);
	}


	public function test_same_instance_returned_in_relationships()
	{
		$host1 = Host::find(1);
		$event = $host1->events[0];
		$host2 = $event->host;

		$this->assert_equals($host1->name, $host2->name);

		$host1->name = "New Host Name";

		$this->assert_equals($host1->name, $host2->name);
	}


	public function test_fill_in_missing_attributes()
	{
		$venue1 = Venue::find(1, array('select' => 'id, name'));

		$this->assert_equals(2, count($venue1->attributes()));
		$this->assert_contains('id', array_keys($venue1->attributes()));
		$this->assert_contains('name', array_keys($venue1->attributes()));

		$venue2 = Venue::find(1, array('select' => 'id, state, phone'));
		
		$this->assert_equals(4, count($venue1->attributes()));
		$this->assert_contains('id', array_keys($venue1->attributes()));
		$this->assert_contains('name', array_keys($venue1->attributes()));
		$this->assert_contains('state', array_keys($venue1->attributes()));
		$this->assert_contains('phone', array_keys($venue1->attributes()));
	}


	public function test_relationship_belongs_to()
	{
		$author = Author::find(1);
		$book = Book::find(1);

		$this->assert_same($author, $book->author);
	}


	public function test_relationship_has_many()
	{
		$author = Author::find(1);
		$book = Book::find(1);
		$books = $author->books;

		$this->assert_equals(1, count($books));
		$this->assert_same($book, $books[0]);
	}


	public function test_eager_load_belongs_to()
	{
		$book = Book::find(1, array('include' => array('author')));
		$author = Author::find(1);

		$this->assert_same($author, $book->author);
	}


	public function test_eager_load_has_many()
	{
		$author1 = Author::find(1, array('include' => array('books')));
		$author2 = Author::find(1, array('include' => array('books')));
		$book = Book::find(1);

		$this->assert_equals(1, count($author1->books));
		$this->assert_same($book, $author1->books[0]);
	}
}