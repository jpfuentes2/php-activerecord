<?php
include 'helpers/config.php';

class AssignAttributesTest extends DatabaseTest {

	static $poly_data = array(
		'name' => 'Test Person',
		'venues' => array(
			array(
				'name' => 'Test Venue',
				'city' => 'Test City',
				'state' => 'Test State',
				'address' => 'Test Address'
			),
			array(
				'id' => 1,
			),
			array(
				'id' => 2,
				'name' => 'Not Warner Theater'
			),
		)
	);

	static $poly_data2 = array(
		'name' => 'Test Person',
		'books' => array(
			array(
				'name' => 'Books are awesome',
			),
			array(
				'book_id' => 1,
			),
			array(
				'book_id' => 2,
				'name' => 'Not Another Book'
			),
		),
		'non_existing_relation' => array(
			array(
				'name' => 'Does not exist',
			),
			array(
				'id' => 1,
			),
		)
	);

	static $single_data = array(
		'name' => 'Test Person',
		'awesome_person' => array(
			'author_id' => 2,
		)
	);

	/*public function test_fetch_book_from_author() {
		$book = Book::find(1);
		print_r($book->author);
	}*/

	public function test_poly_relation_assign_attributes() {
		$data = static::$poly_data2;
		Author::$accepts_nested_attributes_for = array('books');
		$author = new Author();
		$author->assign_attributes($data);

		$this->assert_equals(3, count($author->books));
		$this->assert_equals(true, $author->books[0] instanceof Book);
		$this->assert_equals($data['books'][0]['name'], $author->books[0]->name);

		$book = Book::find($data['books'][1]['book_id']);
		$this->assert_equals(true, $author->books[1] instanceof Book);
		$this->assert_equals(1, $author->books[1]->id);
		$this->assert_equals($book->name, $author->books[1]->name);

		$this->assert_equals(true, $author->books[2] instanceof Book);
		$this->assert_equals(2, $author->books[2]->id);
		$this->assert_equals($data['books'][2]['name'], $author->books[2]->name);
	}


	/*public function test_poly_relation_assign_attributes_has_many_through() {
		Host::$accepts_nested_attributes_for = array('venues');
		$host = new Host();
		$host->assign_attributes(static::$poly_data);

		$this->assert_equals(3, count($host->venues));
		$this->assert_equals(true, $host->venues[0] instanceof Venue);
		$this->assert_equals('Test Venue', $host->venues[0]->name);
		$this->assert_equals('Test City', $host->venues[0]->city);
		$this->assert_equals('Test State', $host->venues[0]->state);
		$this->assert_equals('Test Address', $host->venues[0]->address);
		$this->assert_equals(true, $host->venues[1] instanceof Venue);
		$this->assert_equals(1, $host->venues[1]->id);
		$this->assert_equals('Blender Theater at Gramercy', $host->venues[1]->name);
		$this->assert_equals(true, $host->venues[2] instanceof Venue);
		$this->assert_equals(2, $host->venues[2]->id);
		$this->assert_equals('Not Warner Theater', $host->venues[2]->name);
	}*/

}