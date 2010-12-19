<?php
include 'helpers/config.php';
include 'helpers/foo.php';

use foo\bar\biz\User;
use foo\bar\biz\Newsletter;

class HasManyThroughTest extends DatabaseTest {
	public function test_gh101_has_many_through() {
		$user = User::find(1);
		$newsletter = Newsletter::find(1);

		$this->assert_equals($newsletter->id, $user->newsletters[0]->id);
		$this->assert_equals(
			'foo\bar\biz\Newsletter',
			get_class($user->newsletters[0])
		);
		$this->assert_equals($user->id, $newsletter->users[0]->id);
		$this->assert_equals(
			'foo\bar\biz\User',
			get_class($newsletter->users[0])
		);
	}

	public function test_gh101_has_many_through_include() {
		$user = User::find(1, array(
			'include' => array(
				'user_newsletters'
			)
		));

		$this->assert_equals(1, $user->id);
		$this->assert_equals(1, $user->user_newsletters[0]->id);
	}

	public function test_gh101_has_many_through_sub_include() {
		$user = User::find(1, array(
			'include' => array(
				'user_newsletters' => array('newsletter')
			)
		));

		$this->assert_equals(1, $user->id);
		$this->assert_equals(1, $user->user_newsletters[0]->id);
		$this->assert_equals(1, $user->user_newsletters[0]->newsletter->id);
	}

}

# vim: ts=4 noet nobinary
?>
