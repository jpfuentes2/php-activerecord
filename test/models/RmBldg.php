<?
class RmBldg extends ActiveRecord\Model
{
	static $table = 'rm-bldg';

	static $validates_presence_of = array(
		array('property' => 'space_out', 'message' => 'is missing!@#'),
		array('property' => 'rm_name')
	);

	static $validates_length_of = array(
		array('property' => 'space_out', 'within' => array(1, 5)),
		array('property' => 'space_out', 'minimum' => 9, 'too_short' => 'var is too short!! it should be at least %d long')
	);

	static $validates_inclusion_of = array(
		array('property' => 'space_out', 'in' => array('jpg', 'gif', 'png'), 'message' => 'extension %s is not included in the list'),
	);

	static $validates_exclusion_of = array(
		array('property' => 'space_out', 'in' => array('jpeg'))
	);

	static $validates_format_of = array(
		array('property' => 'space_out', 'with' => '/\A([^@\s]+)@((?:[-a-z0-9]+\.)+[a-z]{2,})\Z/i' )
	);

	static $validates_numericality_of = array(
		array('property' => 'space_out', 'less_than' => 9, 'greater_than' => '5'),
		array('property' => 'rm_id', 'less_than' => 10, 'odd' => null)
	);
}
?>