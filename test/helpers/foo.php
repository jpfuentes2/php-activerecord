<?php

namespace foo\bar\biz;

class User extends \ActiveRecord\Model {
	static $has_many = array(
		array('usernewsletters', 'class_name' => '\foo\bar\biz\Usernewsletter'),
		array('newsletters', 'class_name' => '\foo\bar\biz\Newsletter',
		      'through' => 'usernewsletters')
	);

}

class Newsletter extends \ActiveRecord\Model {
	static $has_many = array(
		array('usernewsletters', 'class_name' => '\foo\bar\biz\Usernewsletter'),
		array('users', 'class_name' => '\foo\bar\biz\User',
		      'through' => 'usernewsletters')
	);
}

class Usernewsletter extends \ActiveRecord\Model {
	static $belong_to = array(
		array('user', 'class_name' => '\foo\bar\biz\User'),
		array('newsletter', 'class_name' => '\foo\bar\biz\Newsletter'),
	);
}

# vim: ts=4 noet nobinary
?>