<?php
include 'helpers/config.php';

class NotModel {};

class IsNotBob extends Author
{
	public static $table_name = 'authors';
	public function named_scopes()
    {
        return array(
            'is_tito'=>array(
                'conditions'=>'name="tito"',
            ),
            'last_two'=>array(
                'order'=>'created_at DESC',
                'limit'=>2,
            ),
        );
    }
    
    public function default_scope()
    {
    	return array(
			'conditions'=>'name != "Uncle Bob"',
		);
		
    }
}

class ScopeTest extends DatabaseTest
{
	public function set_up($connection_name=null)
	{
		parent::set_up($connection_name);
	}
	public function tear_down()
	{
		parent::tear_down();
	}
	
	
	public function test_is_tito_scope()
	{
		$tito = IsNotBob::scoped()->is_tito()->all();
		$this->assertEquals(1,count($tito));
		$this->assertEquals('Tito', $tito[0]->name);
	}
	
	public function test_normal_find_works_correctly()
	{
		$tito = IsNotBob::find('all',array('conditions'=>array('name'=>'tito')));
		
		$this->assertEquals(1,count($tito));
		$this->assertEquals('Tito', $tito[0]->name);
	}
	
	public function test_default_scope_loads_everyone_but_Bob()
	{
		$authors = IsNotBob::scoped()->all();
		foreach($authors as $author)
		{
			if($author->name == 'Uncle Bob')
			{
				$this->fail('Bob loaded');
			}
		}
		return true;
		
	}
	
	public function test_conditions_are_appended_to_scope_that_makes_find_impossible()
	{
		$no_one_that_exists = IsNotBob::scoped()->is_tito()->all(
			array('conditions'=>array('name'=>'something')));
		$this->assertEquals(0,count($no_one_that_exists));
	}
	
	public function test_conditions_are_appended_to_scopes_that_makes_find_impossible_2()
	{
		$billy = IsNotBob::scoped()->is_tito()->all(
			array('conditions'=>array('name'=>'Bill Clinton')));
		$this->assertEquals(0,count($billy));
	}
}
	