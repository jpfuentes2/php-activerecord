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
 * <li>only: only include these attributes</li>
 * <li>except: exclude these attributes</li>
 * <li>methods: run these methods and include their return values</li>
 * <li>include: list of associations to include</li>
 * </ul>
 * 
 * Example usage:
 * 
 * <code>
 * # include the attributes id and name
 * # run $model->encoded_description() and include its return value
 * # include the comments association
 * $model->to_json(array(
 *   'only' => array('id','name'),
 *   'methods' => array('encoded_description'),
 *   'include' => array('comments')
 * ));
 * 
 * # exclude the password field from being included
 * $model->to_xml(array('exclude' => 'password')));
 * </code>
 * 
 * @package ActiveRecord
 */
abstract class Serialization
{
	protected $model;
	protected $options;
	protected $attributes;

	public function __construct(Model $model, &$options)
	{
		$this->model = $model;
		$this->options = $options;
		$this->parse_options();
	}

	private function check_only(&$attributes)
	{
		if (isset($this->options['only']))
		{
			$this->options_to_a('only');
			$exclude = array_diff(array_keys($this->model->attributes()),$this->options['only']);
			$attributes = array_diff_key($this->model->attributes(),array_flip($exclude));
		}

		return $attributes;
	}

	private function check_except(&$attributes)
	{
		if (isset($this->options['except']))
		{
			$this->options_to_a('except');
			$attributes = array_diff_key($this->model->attributes(),array_flip($this->options['except']));
		}

		return $attributes;
	}

	private function check_methods(&$attributes)
	{
		if (isset($this->options['methods']))
		{
			$this->options_to_a('methods');

			foreach ($this->options['methods'] as $method)
			{
				if (method_exists($this->model, $method))
					$attributes[$method] = $this->model->$method();
			}
		}

		return $attributes;
	}

	private function check_include(&$attributes)
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
						$attributes[$association] = $serialized->to_a();;
					}
					else
					{
						$includes = array();

						foreach ($assoc as $a)
						{
							$serialized = new $serializer_class($a, $options);
							$includes[] = $serialized->to_a();
						}

						$attributes[$association] = $includes;
					}

				} catch (UndefinedPropertyException $e) {
					;//move along
				}
			}
		}

		return $attributes;
	}

	private function parse_options()
	{
		$attributes = $this->model->attributes();
		$attributes = $this->check_only($attributes);
		$attributes = $this->check_except($attributes);
		$attributes = $this->check_methods($attributes);
		$attributes = $this->check_include($attributes);

		$this->attributes = $attributes;
	}

	final protected function options_to_a($key)
	{
		if (!is_array($this->options[$key]))
			$this->options[$key] = array($this->options[$key]);
	}

	final public function to_a()
	{
		return $this->attributes;
	}

	final public function __toString()
	{
		return $this->to_s();
	}

	abstract public function to_s();
};

/**
 * JSON serializer.
 * 
 * @package ActiveRecord
 * @subpackage Internal
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
 * @subpackage Internal
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