<?php

namespace ActiveRecord;

use IteratorAggregate;
use ArrayIterator;

/**
 * The use of StrongParameters is implementation dependent.
 *
 * You probably want to integrate this somewhere in your application where the POST data is processed.
 *
 * For example, in your Controller class you can process the POST data:
 *
 *   public function beforeFilter()
 *   {
 *       $this->request->params = new ActiveRecord\StrongParameters($_POST);
 *   }
 *
 * This assumes that your POST data is grouped as follows;
 *
 *   $_POST = array(
 *       "user" => array(
 *           "name" => "Foo Bar",
 *           "bio" => "I'm Foo Bar",
 *           "email" => "foo@bar.baz"
 *       )
 *   )
 *
 * And then in the UserController class you can access the data
 *
 *   public function update_profile()
 *   {
 *       $user = User::find($this->request->params['id']);
 *       $user->update_attributes($this->user_params());
 *       $this->redirect('back');
 *   }
 *
 *   protected function user_params()
 *   {
 *       return $this->request->params->requireParam('user')->permit('name', 'bio', 'email');
 *   }
 *
 *
 * @package ActiveRecord
 */
class StrongParameters implements IteratorAggregate
{
	/**
	 * Array containing data
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * Array containing permitted attributes
	 *
	 * @var array
	 */
	protected $permitted = array();

	/**
	 * Construct StrongParameters object with data
	 *
	 * @param array $data
	 * @return void
	 */
	public function __construct(array $data)
	{
		$this->data = $this->parse($data);
	}

	/**
	 * Recursively parse array into StrongParameter
	 * @param array $data
	 * @return array
	 */
	protected function parse(array $data)
	{
		return array_map(function($value)
		{
			if (!is_array($value))
			{
				return $value;
			}
			return new StrongParameters($value);
		}, $data);
	}

	/**
	 * Permit the specified attributes to be returned for mass assignment.
	 *
	 * @param mixed $attrs,...
	 * @return this
	 */
	public function permit($attrs = array()/* [, ...$attr] */)
	{
		if(func_num_args() > 1)
		{
			$attrs = func_get_args();
		}
		elseif(!is_array($attrs))
		{
			$attrs = array($attrs);
		}
		$this->permitted = $attrs;
		return $this;
	}

	public function fetch($key)
	{
		if (isset($this->data[$key])) {
			return $this->data[$key];
		}
		return null;
	}

	public function requireParam($key)
	{
		$param = $this->fetch($key);
		if (empty($param)) {
			throw new ParameterMissingException("Missing param '$key'");
		}
		return $param;
	}

	/**
	 * Required method for IteratorAggregate interface.
	 *
	 * @return ArrayIterator iterator for permitted data.
	 */
	public function getIterator()
	{
		$permitted_data = array_intersect_key($this->data, array_flip($this->permitted));
		return new ArrayIterator($permitted_data);
	}

}
