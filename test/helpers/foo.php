<?php

namespace foo\bar\biz;

class User extends \ActiveRecord\Model {
	static $has_many = array(
		array('user_newsletters'),
		array('newsletters', 'through' => 'user_newsletters'),
		array('active_services'),
		array('services', 'through' => 'active_services')
	);

}

class Newsletter extends \ActiveRecord\Model {
	static $has_many = array(
		array('user_newsletters'),
		array('users', 'through' => 'user_newsletters'),
	);
}

class UserNewsletter extends \ActiveRecord\Model {
	static $belong_to = array(
		array('user'),
		array('newsletter'),
	);
}

class Service extends \ActiveRecord\Model {
	static $has_many = array(
		array('active_services', 'foreign_key' => 'serv_id'),
		array('users', 'through' => 'active_services')
	);
}
class ActiveService extends \ActiveRecord\Model {
	static $belongs_to = array(
		array('user'),
		array('service')
	);
}

# vim: ts=4 noet nobinary
?>
