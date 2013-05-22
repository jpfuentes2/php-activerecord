<?php 
include 'helpers/config.php';

class IdentityMapTest extends DatabaseTest
{
	public function test_persistence_between_model_instances()
	{
		$author1 = Author::find(1);
		$author2 = Author::find(1);

		$this->assert_equals($author1->name, $author2->name);

		$author1->name = 'A New Title';

		$this->assert_equals($author1->name, $author2->name);
	}
}