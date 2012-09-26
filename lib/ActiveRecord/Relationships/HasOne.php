<?php
namespace ActiveRecord\Relationships;


use ActiveRecord\Model;
/**
 * One-to-one relationship.
 *
 * <code>
 * # Table name: states
 * # Primary key: id
 * class State extends ActiveRecord\Model {}
 *
 * # Table name: people
 * # Foreign key: state_id
 * class Person extends ActiveRecord\Model {
 *   static $has_one = array(array('state'));
 * }
 * </code>
 *
 * @package ActiveRecord\Relationships
 * @see http://www.phpactiverecord.org/guides/associations
 */


class HasOne extends HasMany
{
};
?>