<?php
/**
 * These two classes have been <i>heavily borrowed</i> from Ruby on Rails' ActiveRecord so much that
 * this piece can be considered a straight port. The reason for this is that the vaildation process is
 * tricky due to order of operations/events. The former combined with PHP's odd typecasting means
 * that it was easier to formulate this piece base on the rails code.
 * 
 * @package ActiveRecord
 */

namespace ActiveRecord;
use ActiveRecord\Model;
use IteratorAggregate;
use ArrayIterator;

/**
 * @package ActiveRecord
 */
class Validations
{
	protected $model;
	protected $options = array();
	protected $validators = array();
	protected $record;

	public static $VALIDATION_FUNCTIONS = array(
		'validates_presence_of',
		'validates_size_of',
		'validates_length_of',
		'validates_inclusion_of',
		'validates_exclusion_of',
		'validates_format_of',
		'validates_numericality_of',
		'validates_uniqueness_of'
		);

	public static $DEFAULT_VALIDATION_OPTIONS = array(
		'on' => 'save',
		'allow_null' => false,
		'allow_blank' => false,
		'message' => null,
		);

	public static  $ALL_RANGE_OPTIONS = array(
		'is' => null,
		'within' => null,
		'in' => null,
		'minimum' => null,
		'maximum' => null,
		);

	protected static $ALL_NUMERICALITY_CHECKS = array(
		'greater_than' => null,
		'greater_than_or_equal_to'  => null,
		'equal_to' => null,
		'less_than' => null,
		'less_than_or_equal_to' => null,
		'odd' => null,
		'even' => null
		);

	public function __construct(Model $model)
	{
		$this->model = $model;
		$this->record = new Errors($this->model);
	}

	public function validate()
	{
		$reflection = Reflections::instance()->get(get_class($this->model));

		// create array of validators to use from valid functions merged with the static properties
		$this->validators = array_intersect(array_keys($reflection->getStaticProperties()), self::$VALIDATION_FUNCTIONS);

		if (empty($this->validators))
			return $this->record;

		$inflector = Inflector :: instance();

		foreach ($this->validators as $validate)
		{
			$func =	$inflector->variablize($validate);

			$this->$func($reflection->getStaticPropertyValue($validate));
		}

		return $this->record;
	}

	public function validates_presence_of($attrs)
	{
       $configuration = array_merge(self::$DEFAULT_VALIDATION_OPTIONS ,array('message' =>  Errors::$DEFAULT_ERROR_MESSAGES['blank'], 'on' => 'save'));

       foreach ($attrs as $attr)
       {
       		$options = array_merge($configuration, $attr);
			$this->record->add_on_blank($options[0], $options['message']);
       }
	}

	public function validates_inclusion_of($attrs)
	{
		$this->validates_inclusion_or_exclusion_of('inclusion', $attrs);
	}

	public function validates_exclusion_of($attrs)
	{
		$this->validates_inclusion_or_exclusion_of('exclusion', $attrs);
	}

	public function validates_inclusion_or_exclusion_of($type, $attrs)
	{
		$configuration = array_merge(self::$DEFAULT_VALIDATION_OPTIONS, array('message' =>  Errors :: $DEFAULT_ERROR_MESSAGES[$type], 'on' => 'save'));

		foreach ($attrs as $attr)
       	{
       		$options = array_merge($configuration, $attr);
       		$attribute = $options[0];
       		$var = $this->model->$attribute;

       		if (isset($options['in']))
       			$enum = $options['in'];
			elseif (isset($options['within']))
				$enum = $options['within'];

			if (!is_array($enum))
				array($enum);

			$message = str_replace('%s', $var, $options['message']);

			if ($this->is_null_with_option($var, $options) || $this->is_blank_with_option($var, $options))
				continue;

			if ( ( 'inclusion' == $type && !in_array($var, $enum) ) || ( 'exclusion' == $type && in_array($var, $enum)) )
		     	$this->record->add($attribute, $message);
       	}
	}

	public function validates_numericality_of($attrs)
	{
		$configuration = array_merge(self::$DEFAULT_VALIDATION_OPTIONS, array('only_integer' => false));
		//Notice that for fixnum and float columns empty strings are converted to nil.
		//Validates whether the value of the specified attribute is numeric by trying to convert it to a float with Kernel.Float
		//(if only_integer is false) or applying it to the regular expression /\A[+\-]?\d+\Z/ (if only_integer is set to true).
		foreach ($attrs as $attr)
       	{
       		$options = array_merge($configuration, $attr);
       		$attribute = $options[0];
       		$var = $this->model->$attribute;

       		$numericalityOptions = array_intersect_key(self::$ALL_NUMERICALITY_CHECKS, $options);

       		if ($this->is_null_with_option($var, $options))
       			continue;

  			if (true === $options['only_integer'] && !is_integer($var))
			{
       			if (preg_match('/\A[+-]?\d+\Z/', (string)($var)))
       				break;

				if (isset($options['message']))
       				$message = $options['message'];
       			else
       				$message = Errors::$DEFAULT_ERROR_MESSAGES['not_a_number'];

				$this->record->add($attribute, $message);
				continue;
			}
			else
			{
				if (!is_numeric($var))
				{
	   				$this->record->add($attribute, Errors::$DEFAULT_ERROR_MESSAGES['not_a_number']);
   					continue;
  				}

				$var = (float)$var;
			}

       		foreach ($numericalityOptions as $option => $check)
       		{
				$option_value = $options[$option];

       			if ('odd' != $option && 'even' != $option)
       			{
       				$option_value = (float)$options[$option];

       				if (!is_numeric($option_value))
      					 throw new  ValidationsArgumentError("$option must be a number");

       				if (isset($options['message']))
       					$message = $options['message'];
       				else
       					$message = Errors::$DEFAULT_ERROR_MESSAGES[$option];

       				$message = str_replace('%d', $option_value, $message);

       				if ('greater_than' == $option && !($var > $option_value))
       					$this->record->add($attribute, $message);

       				elseif ('greater_than_or_equal_to' == $option && !($var >= $option_value))
       					$this->record->add($attribute, $message);

       				elseif ('equal_to' == $option && !($var == $option_value))
       					$this->record->add($attribute, $message);

       				elseif ('less_than' == $option && !($var < $option_value))
       					$this->record->add($attribute, $message);

       				elseif ('less_than_or_equal_to' == $option && !($var <= $option_value))
       					$this->record->add($attribute, $message);
       			}
       			else
       			{
       				if (isset($options['message']))
       					$message = $options['message'];
       				else
       					$message = Errors::$DEFAULT_ERROR_MESSAGES[$option];

     				if ( ('odd' == $option && !( Utils::is_odd($var))) || ('even' == $option && ( Utils::is_odd($var))))
						$this->record->add($attribute, $message);
       			}
       		}
       	}
	}

	/**
	 * Alias of validatesLengthOf
	 * @param array $attrs
	 */
	public function validates_size_of($attrs)
	{
		$this->validates_length_of($attrs);
	}

	public function validates_format_of($attrs)
	{
		$configuration = array_merge(self::$DEFAULT_VALIDATION_OPTIONS, array('message' =>  Errors::$DEFAULT_ERROR_MESSAGES['invalid'], 'on' => 'save', 'with' => null ));

		foreach ($attrs as $attr)
       	{
			$options = array_merge($configuration, $attr);
			$attribute = $options[0];
			$var = $this->model->$attribute;

			if (is_null($options['with']) || !is_string($options['with']) || !is_string($options['with']))
				throw new ValidationsArgumentError('A regular expression must be supplied as the [with] option of the configuration array.');
			else
				$expression = $options['with'];

			if ($this->is_null_with_option($var, $options) || $this->is_blank_with_option($var, $options))
				continue;

			if (!@preg_match($expression, $var))
				$this->record->add($attribute, $options['message']);
		}
	}

	public function validates_length_of($attrs)
	{
		$configuration = array_merge(self::$DEFAULT_VALIDATION_OPTIONS, array(
          		'too_long'     =>  Errors::$DEFAULT_ERROR_MESSAGES['too_long'],
          		'too_short'    =>  Errors::$DEFAULT_ERROR_MESSAGES['too_short'],
          		'wrong_length' =>  Errors::$DEFAULT_ERROR_MESSAGES['wrong_length']
        ));

       	foreach ($attrs as $attr)
       	{
       		$options = array_merge($configuration, $attr);

      		$range_options = array_intersect(array_keys(self::$ALL_RANGE_OPTIONS), array_keys($attr));
       		sort($range_options);

       		switch (sizeof($range_options))
       		{
       			case 0:
       				throw new  ValidationsArgumentError('Range unspecified.  Specify the [within], [maximum], or [is] option.');

       			case 1:
       				break;

       			default:
       				throw new  ValidationsArgumentError('Too many range options specified.  Choose only one.');
       		}

			$attribute = $options[0];
			$var = $this->model->$attribute;
       		$range_option = $range_options[0];

			if ($this->is_null_with_option($var, $options) || $this->is_blank_with_option($var, $options))
				continue;

       		if ('within' == $range_option || 'in' == $range_option)
       		{
       			$range = $options[$range_options[0]];

       			if (!(Utils::is_a('range', $range)))
       				throw new  ValidationsArgumentError("$range_option must be an array composing a range of numbers with key [0] being less than key [1]");

       			if (is_float($range[0]) || is_float($range[1]))
       				throw new  ValidationsArgumentError("Range values cannot use floats for length.");

       			if ((int)$range[0] <= 0 || (int)$range[1] <= 0)
       				throw new  ValidationsArgumentError("Range values cannot use signed integers.");

       			$too_short = isset($options['message']) ? $options['message'] : $options['too_short'];
       			$too_long =  isset($options['message']) ? $options['message'] : $options['too_long'];

       			$too_short = str_replace('%d', $range[0], $too_short);
       			$too_long = str_replace('%d', $range[0], $too_long);

       			if (strlen($this->model->$attribute) < (int)$range[0])
       				$this->record->add($attribute, $too_short);
       			elseif (strlen($this->model->$attribute) > (int)$range[1])
       				$this->record->add($attribute, $too_long);
       		}

       		elseif ('is' == $range_option || 'minimum' == $range_option || 'maximum' == $range_option)
       		{
       			$option = $options[$range_option];

       			if ((int)$option <= 0)
       				throw new  ValidationsArgumentError("$range_option value cannot use a signed integer.");

       			if (is_float($option))
       				throw new  ValidationsArgumentError("$range_option value cannot use a float for length.");

       			$validityChecks = array('is' => "!=", 'minimum' => "<=", 'maximum' => ">=" );
            	$messageOptions = array('is' => 'wrong_length', 'minimum' => 'too_short', 'maximum' => 'too_long');

             	if (isset($options[$messageOptions[$range_option]]))
            		$message = $options[$messageOptions[$range_option]];
            	else
            		$message = $options['message'];

            	$message = str_replace('%d', $option, $message);

            	$attribute_value = $this->model->$attribute;
            	$option = (int)$attribute_value;

            	if (!is_null($this->model->$attribute))
            	{
            		$check = $validityChecks[$range_option];

            		if ('maximum' == $range_option && strlen($attribute_value) > $option)
            			$this->record->add($attribute, $message);

            		if ('minimum' == $range_option && strlen($attribute_value) < $option)
            			$this->record->add($attribute, $message);

					if ('is' == $range_option && strlen($attribute_value) === $option)
            			$this->record->add($attribute, $message);

            	}
       		}
       	}
	}

	public function validates_uniqueness_of($attrs)
	{
		return ;
	}

	private function is_null_with_option($var, &$options)
	{
		return (is_null($var) && (isset($options['allow_null']) && $options['allow_null']));
	}

	private function is_blank_with_option($var, &$options)
	{
		return (Utils::is_blank($var) && (isset($options['allow_blank']) && $options['allow_blank']));
	}
}

class Errors implements IteratorAggregate
{
   	private $model;
	private $errors;

   	public static $DEFAULT_ERROR_MESSAGES = array(
   		'inclusion' => "is not included in the list",
     	'exclusion' => "is reserved",
      	'invalid' => "is invalid",
      	'confirmation' => "doesn't match confirmation",
      	'accepted ' => "must be accepted",
      	'empty' => "can't be empty",
      	'blank' => "can't be blank",
      	'too_long' => "is too long (maximum is %d characters)",
      	'too_short' => "is too short (minimum is %d characters)",
      	'wrong_length' => "is the wrong length (should be %d characters)",
      	'taken' => "has already been taken",
      	'not_a_number' => "is not a number",
      	'greater_than' => "must be greater than %d",
      	'greater_than_or_equal_to' => "must be greater than or equal to %d",
      	'equal_to' => "must be equal to %d",
      	'less_than' => "must be less than %d",
      	'less_than_or_equal_to' => "must be less than or equal to %d",
      	'odd' => "must be odd",
      	'even' => "must be even"
   	);

   	public function __construct(Model $model)
   	{
		$this->model = $model;
   	}

    public function add($attribute, $msg)
    {
     	if (is_null($msg))
      		$msg = self :: $DEFAULT_ERROR_MESSAGES['invalid'];

      	if (!isset($this->errors[$attribute]))
	      	$this->errors[$attribute] = array($msg);
    	  else
      		$this->errors[$attribute][] = $msg;
    }

    public function add_on_empty($attribute, $msg)
    {
    	if (empty($msg))
    	 	$msg = self::$DEFAULT_ERROR_MESSAGES['empty'];

    	if (empty($this->model->$attribute))
	    	$this->add($attribute, $msg);
    }

    public function __get($var)
    {
    	if (!isset($this->errors[$var]))
    		return null;

    	return $this->errors[$var];
    }

    public function add_on_blank($attribute, $msg)
    {
     	if (is_null($msg))
      		$msg = self :: $DEFAULT_ERROR_MESSAGES['blank'];

      	if (!strlen($this->model->$attribute))
      		$this->add($attribute, $msg);
    }

    public function is_invalid($attribute)
    {
    	return isset($this->errors[$attribute]);
    }

    public function on($attribute)
    {
   	 	if (!isset($this->errors[$attribute]))
   	 		return null;

    	$errors = $this->errors[$attribute];

      	if (null === $errors)
	      	return null;
    	  else
	      	return count($errors) == 1 ? $errors[0] : $errors;
    }

    public function full_messages($implode = array())
    {
    	$fullMessages = array();

    	if ($this->errors)
    	{
		    foreach ($this->errors as $attribute => $messages)
		    {
	    	    foreach ($messages as $msg)
	        	{
	 	     		if (is_null($msg))
	          			continue;

	          		$fullMessages[] =  Utils::human_attribute($attribute) . " " . $msg;
	         	 }
	    	}
    	}

    	if (isset($implode['implode']) && isset($implode['glue']))
    		$fullMessages = implode($implode['glue'], $fullMessages);

      	return $fullMessages;
    }

    public function is_empty()
    {
    	return empty($this->errors);
    }

    public function clear()
    {
		$this->errors = array();
    }

    public function size()
    {
      	if ($this->is_empty())
      		return 0;

    	$count = 0;

      	foreach ($this->errors as $attribute => $error)
      		$count += count($error);

      	return $count;
    }

    public function getIterator()
    {
    	return new ArrayIterator($this->full_messages());
    }
};
?>