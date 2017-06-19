<?php

namespace ActiveRecord;

use IteratorAggregate;
use ArrayIterator;
use ArrayAccess;
use InvalidArgumentException;

/**
 * The use of StrongParameters is implementation dependent.
 *
 * You probably want to integrate this somewhere in your application where the POST data is processed.
 *
 * For example, in your controller class you can process the request data:
 *
 *   public function beforeFilter()
 *   {
 *       $this->params = new ActiveRecord\StrongParameters(array_merge($_GET, $_POST));
 *   }
 *
 * This example assumes that your request data is structured as follows;
 *
 *   array(
 *       "id" => 1,
 *       "user" => array(
 *           "name" => "Foo Bar",
 *           "bio" => "I'm Foo Bar",
 *           "email" => "foo@bar.baz"
 *       )
 *   )
 *
 * And then to use the data in a controller:
 *
 *   public function update_profile()
 *   {
 *       $user = User::find($this->params['id']);
 *       $user->update_attributes($this->user_params());
 *       $this->redirect('back');
 *   }
 *
 *   protected function user_params()
 *   {
 *       return $this->params->require_param('user')->permit('name', 'bio', 'email');
 *   }
 *
 *
 * @package ActiveRecord
 */
class StrongParameters implements IteratorAggregate, ArrayAccess
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
			if (!is_hash($value))
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

	/**
	 * Fetch a value from the data
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function fetch($key)
	{
		if (isset($this->data[$key])) {
			return $this->data[$key];
		}
		return null;
	}

	/**
	 * Fetch a required value from the data. If the param doesn't exist an error
	 * is thrown
	 *
	 * @param string $key
	 * @throws ActiveRecord\ParameterMissingException
	 * @return mixed
	 */
	public function requireParam($key)
	{
		$param = $this->fetch($key);
		if (empty($param)) {
			throw new ParameterMissingException("Missing param '$key'");
		}
		return $param;
	}

	/**
	 * @see requireParam
	 */
	public function require_param($key)
	{
		return $this->requireParam($key);
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

	/**
	 * Required method for ArrayAccess interface.
	 *
	 * @param string $offset
	 * @return bool true if offset exists
	 */
	public function offsetExists($offset)
	{
		return isset($this->data[$offset]);
	}

	/**
	 * Required method for ArrayAccess interface.
	 *
	 * @param string $offset
	 * @return mixed
	 * @see fetch
	 */
	public function offsetGet($offset)
	{
		return $this->fetch($offset);
	}

	/**
	 * Required method for ArrayAccess interface.
	 *
	 * @param string $offset
	 * @param mixed $value
	 * @return void
	 * @throws InvalidArgumentException when offset is null
	 */
	public function offsetSet($offset, $value)
	{
		if (is_null($offset)) {
			throw new InvalidArgumentException('offset cannot be null');
		}
		$this->data[$offset] = $value;
	}

	/**
	 * Required method for ArrayAccess interface.
	 *
	 * @param string $offset
	 * @return void
	 */
	public function offsetUnset($offset)
	{
		unset($this->data[$offset]);
	}

}
