<?php
/**
 * @package ActiveRecord
 * @subpackage CallBack
 */
namespace ActiveRecord;
use Closure;

/**
 * Callbacks allow the programmer to hook into the life cycle of an ActiveRecord object. You can control
 * the state of your object by declaring certain methods to be called before or after methods
 * are invoked on your object inside of ActiveRecord.
 *
 * @package ActiveRecord
 * @subpackage CallBack
 */
class CallBack
{
	/**
	 * Array of callbacks that are available to use.
	 * @access private
	 * @static
	 * @var array
	 */
	static private $DEFAULT_CALLBACKS = array(
		'after_construct',
		'before_save',
		'after_save',
		'before_create',
		'after_create',
		'before_update',
		'after_update',
		'before_validation',
		'after_validation',
		'before_validation_on_create',
		'after_validation_on_create',
		'before_validation_on_update',
		'after_validation_on_update',
		'before_destroy',
		'after_destroy'
	);

	/**
	 * Container for reflection class of given model
	 * @access private
	 * @var object
	 */
	private $klass;

	/**
	 * @access private
	 * @var array
	 */
	private $registry = array();

	/**
	 * @param object ActiveRecord\Model
	 * @return void
	 */
	public function __construct($model_class_name)
	{
		$this->klass = Reflections::instance()->get($model_class_name);
		$this->registry = array_fill_keys(self::$DEFAULT_CALLBACKS, array());
		$this->register_all();
	}

	/**
	 * Get the default/available callbacks
	 * @static
	 * @see ActiveRecord\CallBack::$DEFAULT_CALLBACKS
	 * @return array
	 */
	public static function get_allowed_call_backs()
	{
		return self::$DEFAULT_CALLBACKS;
	}

	/**
	 * Get the registered callbacks
	 * @return array
	 */
	public function get_registry()
	{
		return $this->registry;
	}

	/**
	 * Send a notification which will invoke methods inside the registry array based
	 * on the name passed.
	 *
	 * This is the only piece of the CallBack class that carries its own logic for the
	 * model object. For (after|before)_(create|update) callbacks, it will merge with
	 * a generic 'save' callback which is called first for the lease amount of precision.
	 *
	 * Returns null if no such name exists within the registry.
	 * Returns false if the following:
	 *  a method was invoked that was for a before_* callback and that
	 *  method returned false. If this happens, execution of any other callbacks after
	 *  the offending callback will not occur.
	 * @param str
	 * @return mixed
	 */
	public function send($model, $name)
	{
		if (array_key_exists($name, $this->registry))
		{
			$registry = $this->registry[$name];

			if (preg_match('/(after|before)_(create|update)/', $name))
			{
				$temporal_save = str_replace(array('create', 'update'), 'save', $name);
				$registry = array_merge($this->registry[$temporal_save], $registry);
			}

			foreach ($registry as $method)
			{
				if ($method instanceof Closure)
					$ret = $method($model);
				else
					$ret = $model->$method();

				if (false === $ret && substr($name, 0, 6) === 'before')
					return false;
			}
			return true;
		}
		return null;
	}

	/**
	 * Registers the default/generic callbacks on the model such as
	 * before_save so that you do not have to define them in a static
	 * declaration inside the model. You would only need to define the method
	 * itself on the model.
	 * @access private
	 * @return void
	 */
	private function register_all()
	{
		foreach (array_values(self::$DEFAULT_CALLBACKS) as $name)
		{
			//load the generic/default method on model to be invoked
			$this->register($name, $name);
			$this->register($name, $this->klass->getStaticPropertyValue($name, null));
		}
	}

	/**
	 * @param str
	 * @param array
	 * @param array
	 * @return void or false
	 */
	public function register($name, $definition, $options=array())
	{
		$options = array_merge(array('prepend' => false), $options);

		if (is_null($definition))
			return false;

		if (!is_array($definition))
			$definition = array($definition);

		foreach ($definition as $method)
		{
			if (!($method instanceof Closure) && !$this->klass->hasMethod($method))
				continue;

			if ($options['prepend'])
				array_unshift($this->registry[$name], $method);
			else
				$this->registry[$name][] = $method;
		}
	}
}
?>