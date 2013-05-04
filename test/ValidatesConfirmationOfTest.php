<?php

class UserConfirmation extends ActiveRecord\Model
{
	static $table_name = 'users';

	static $validates_confirmation_of = array(
		array('password')
	);

	public function set_password($password)
	{
		$this->assign_attribute('password', $password);
	}

	public function get_password()
	{
		return $this->read_attribute('password');
	}

	public function set_password_confirmation($password_confirmation)
	{
		$this->assign_attribute('password_confirmation', $password_confirmation);
	}

	public function get_password_confirmation()
	{
		return $this->read_attribute('password_confirmation');
	}
}

class ValidatesConfirmationOfTest extends DatabaseTest
{
	public function test_confirmation_absent()
	{
		try {
			$user = new UserConfirmation(array('password' => '12345'));
			$user->is_valid();
		} catch (\ActiveRecord\UndefinedPropertyException $e) {
			$this->assert_string_starts_with('Undefined property: UserConfirmation->password_confirmation in', $e->getMessage());
		}
	}

	public function test_confirmation_not_equal()
	{
		$user = new UserConfirmation(array('password' => '12345', 'password_confirmation' => '54321'));
		$this->assert_false($user->is_valid());
	}

	public function test_confirmation_not_identical()
	{
		$user = new UserConfirmation(array('password' => '12345', 'password_confirmation' => 12345));
		$this->assert_false($user->is_valid());
	}

	public function test_confirmation_ok()
	{
		$user = new UserConfirmation(array('password' => 12345, 'password_confirmation' => 12345));
		$this->assert_true($user->is_valid());
	}

	public function test_custom_message()
	{
		UserConfirmation::$validates_confirmation_of[0]['message'] = 'This is a custom error message';

		$user = new UserConfirmation(array('password' => 12345, 'password_confirmation' => '54321'));
		$user->is_valid();

		$this->assert_equals('This is a custom error message', $user->errors->on('password'));
	}
};
?>
