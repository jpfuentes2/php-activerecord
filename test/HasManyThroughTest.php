<?php
include 'helpers/config.php';
include 'helpers/foo.php';

use foo\bar\biz\User;
use foo\bar\biz\Newsletter;
use foo\bar\biz\Service;
use foo\bar\biz\ActiveService;


class HasManyThroughTest extends DatabaseTest
{
	public function test_gh101_has_many_through()
	{
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

	public function test_gh101_has_many_through_include()
	{
		$user = User::find(1, array(
			'include' => array(
				'user_newsletters'
			)
		));

		$this->assert_equals(1, $user->id);
		$this->assert_equals(1, $user->user_newsletters[0]->id);
	}

	public function test_gh107_has_many_through_include_eager()
	{
		$venue = Venue::find(1, array('include' => array('events')));
		$this->assert_equals(1, $venue->events[0]->id);

		$venue = Venue::find(1, array('include' => array('hosts')));
		$this->assert_equals(1, $venue->hosts[0]->id);
	}

	public function test_gh107_has_many_though_include_eager_with_namespace()
	{
		$user = User::find(1, array(
			'include' => array(
				'newsletters'
			)
		));

		$this->assert_equals(1, $user->id);
		$this->assert_equals(1, $user->newsletters[0]->id);
	}

	public function test_has_many_through_rely_into_through_relation_for_foreign_key()
	{
		$user = User::find(1);
		$this->assert_not_empty($user->services);
		$this->assert_equals(2, $user->services[0]->id);
	}
	public function test_has_many_through_can_be_used_in_joins()
	{
		$users = User::all(array(
			'joins' => array('services'),
			// code is a Service's field
			'conditions' => "code = 'daily_mail'"
		));
		$ids = array_map(function($u){ return $u->id; }, $users);
		$this->assert_count(2, $users);
		$this->assert_equals(array(2,4), $ids);
	}
}
# vim: noet ts=4 nobinary
?>
