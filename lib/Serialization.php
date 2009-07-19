<?php
/**
 * @package ActiveRecord
 */
namespace ActiveRecord;
use XmlWriter;

/**
 * Base class for Model serializers.
 *
 * All serializers support the following options:
 *
 * <ul>
 * <li><b>only:</b> a string or array of attributes to be included.</li>
 * <li><b>excluded:</b> a string or array of attributes to be excluded.</li>
 * <li><b>methods:</b> a string or array of methods to invoke. The method's name will be used as a key for the final attributes array
 * along with the method's returned value</li>
 * <li><b>include:</b> a string or array of associated models to include in the final serialized product.</li>
 * </ul>
 *
 * Example usage:
 *
 * <code>
 * # include the attributes id and name
 * # run $model->encoded_description() and include its return value
 * # include the comments association
 * # include posts association with its own options (nested)
 * $model->to_json(array(
 *   'only' => array('id','name', 'encoded_description'),
 *   'methods' => array('encoded_description'),
 *   'include' => array('comments', 'posts' => array('only' => 'id'))
 * ));
 *
 * # exclude the password field from being included
 * $model->to_xml(array('exclude' => 'password')));
 * </code>
 *
 * @package ActiveRecord
 * @link http://www.phpactiverecord.org/guides/utilities#topic-serialization
 */
abstract class Serialization
{
	protected $model;
	protected $options;
	protected $attributes;

	/**
	 * Constructs a {@link Serialization} object.
	 *
	 * @param Model $model The model to serialize
	 * @param array &$options Options for serialization
	 * @return Serialization
	 */
	public function __construct(Model $model, &$options)
	{
		$this->model = $model;
		$this->options = $options;
		$this->attributes = $model->attributes();
		$this->parse_options();
	}

	private function parse_options()
	{
		$this->check_except();
		$this->check_methods();
		$this->check_include();
		$this->check_only();
	}

	private function check_only()
	{
		if (isset($this->options['only']))
		{
			$this->options_to_a('only');
			$exclude = array_diff(array_keys($this->attributes),$this->options['only']);
			$this->attributes = array_diff_key($this->attributes,array_flip($exclude));
		}
	}

	private function check_except()
	{
		if (isset($this->options['except']))
		{
			$this->options_to_a('except');
			$this->attributes = array_diff_key($this->attributes,array_flip($this->options['except']));
		}
	}

	private function check_methods()
	{
		if (isset($this->options['methods']))
		{
			$this->options_to_a('methods');

			foreach ($this->options['methods'] as $method)
			{
				if (method_exists($this->model, $method))
					$this->attributes[$method] = $this->model->$method();
			}
		}
	}

	private function check_include()
	{
		if (isset($this->options['include']))
		{
			$this->options_to_a('include');

			$serializer_class = get_class($this);

			foreach ($this->options['include'] as $association => $options)
			{
				if (!is_array($options))
				{
					$association = $options;
					$options = array();
				}

				try {
					$assoc = $this->model->$association;

					if (!is_array($assoc))
					{
						$serialized = new $serializer_class($assoc, $options);
						$this->attributes[$association] = $serialized->to_a();;
					}
					else
					{
						$includes = array();

						foreach ($assoc as $a)
						{
							$serialized = new $serializer_class($a, $options);
							$includes[] = $serialized->to_a();
						}

						$this->attributes[$association] = $includes;
					}

				} catch (UndefinedPropertyException $e) {
					;//move along
				}
			}
		}
	}

	final protected function options_to_a($key)
	{
		if (!is_array($this->options[$key]))
			$this->options[$key] = array($this->options[$key]);
	}

	/**
	 * Returns the attributes array.
	 * @return array
	 */
	final public function to_a()
	{
		return $this->attributes;
	}

	/**
	 * Returns the serialized object as a string.
	 * @see to_s
	 * @return string
	 */
	final public function __toString()
	{
		return $this->to_s();
	}

	/**
	 * Performs the serialization.
	 * @return string
	 */
	abstract public function to_s();
};

/**
 * JSON serializer.
 *
 * @package ActiveRecord
 */
class JsonSerializer extends Serialization
{
	public function to_s()
	{
		return json_encode($this->attributes);
	}
}

/**
 * XML serializer.
 *
 * @package ActiveRecord
 */
class XmlSerializer extends Serialization
{
	private $writer;

	public function to_s()
	{
		return $this->xml_encode();
	}

	private function xml_encode()
	{
		$this->writer = new XmlWriter();
		$this->writer->openMemory();
		$this->writer->startDocument('1.0', 'UTF-8');
		$this->writer->startElement(strtolower(denamespace(($this->model))));
		$this->write($this->attributes);
		$this->writer->endElement();
		$this->writer->endDocument();
		return $this->writer->outputMemory(true);
	}

	private function write($data)
	{
		foreach ($data as $attr => $value)
		{
			if (is_array($value))
			{
				$this->writer->startElement($attr);
				$this->write($value);
				$this->writer->endElement();
				continue;
			}

			$this->writer->writeElement($attr, $value);
		}
	}
}
?>