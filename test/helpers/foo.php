<?php

namespace foo\bar\biz;

class User extends \ActiveRecord\Model {
	static $has_many = array(
		array('user_newsletters', 'class_name' => '\foo\bar\biz\UserNewsletter'),
		array('newsletters', 'class_name' => '\foo\bar\biz\Newsletter',
		      'through' => 'user_newsletters')
	);

}

class Newsletter extends \ActiveRecord\Model {
	static $has_many = array(
		array('user_newsletters', 'class_name' => '\foo\bar\biz\UserNewsletter'),
		array('users', 'class_name' => '\foo\bar\biz\User',
		      'through' => 'user_newsletters')
	);
}

class UserNewsletter extends \ActiveRecord\Model {
	static $belong_to = array(
		array('user', 'class_name' => '\foo\bar\biz\User'),
		array('newsletter', 'class_name' => '\foo\bar\biz\Newsletter'),
	);
}

# vim: ts=4 noet nobinary
?>