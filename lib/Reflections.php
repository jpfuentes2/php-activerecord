<?php
/**
 * @package ActiveRecord
 * @subpackage Reflections
 */
namespace ActiveRecord;
use ReflectionClass;

/**
 * Simple class that caches reflections of classes.
 *
 * @package ActiveRecord
 * @subpackage Reflections
 */
class Reflections extends Singleton
{
	/**
	 * Current reflections
	 * @var array
	 */
	private $reflections = array();

	/**
	 * Instantiates a new ReflectionClass for the given class and
	 * returns $this so you can do Reflections::instance()->add('class')->get();
	 * @param string
	 * @return object $this
	 */
	public function add($class=null)
	{
		$class = $this->get_class($class);

		if (!isset($this->reflections[$class]))
			$this->reflections[$class] = new ReflectionClass($class);

		return $this;
	}

	/**
	 * Get a cached ReflectionClass.
	 * @param string
	 * @return null or ReflectionClass instance
	 */
	public function get($class=null)
	{
		$class = $this->get_class($class);

		if (isset($this->reflections[$class]))
			return $this->reflections[$class];

		return null;
	}

	/**
	 * Retreive a class name to be reflected
	 * @param mixed
	 * @return string
	 */
	private function get_class($mixed=null)
	{
		if (is_object($mixed))
			return get_class($mixed);
		elseif (!is_null($mixed))
			return $mixed;
		else
			return $this->get_called_class();
	}
}
?>