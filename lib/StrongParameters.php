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
 *       foreach($_POST as $group => $params)
 *       {
 *           $this->request->data[$group] = new ActiveRecord\StrongParameters($params);
 *       }
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
 *       return $this->request->data['user']->permit('name', 'bio', 'email');
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
	public function __construct(array $data = array())
	{
		$this->data = $data;
	}

	/**
	 * Permit the specified attributes to be returned for mass assignment.
	 *
	 * @param mixed $attrs,...
	 * @return void
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
