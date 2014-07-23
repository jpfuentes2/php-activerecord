<?php
class Unit extends ActiveRecord\Model
{
    static $alias_attribute = array(
        'created_at' => 'created_time',
        'updated_at' => 'updated_time',
    );
};
?>
