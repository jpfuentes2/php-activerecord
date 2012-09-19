<?php
namespace Speedy\ActiveRecord\Relationships;
/**
 * One-to-one relationship.
 *
 * <code>
 * # Table name: states
 * # Primary key: id
 * class State extends Speedy\ActiveRecord\Model {}
 *
 * # Table name: people
 * # Foreign key: state_id
 * class Person extends Speedy\ActiveRecord\Model {
 *   static $has_one = array(array('state'));
 * }
 * </code>
 *
 * @package Speedy\ActiveRecord
 * @see http://www.phpactiverecord.org/guides/associations
 */


class HasOne extends HasMany
{
};
?>