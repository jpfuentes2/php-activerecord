<?php
include 'helpers/config.php';

class IsNotBob extends Author
{
	public static $table_name = 'authors';
	public function named_scopes()
    {
        return array(
            'is_tito'=>array(
                'conditions'=>'name="tito"',
            ),
            'some_two'=>array(
                'limit'=>2,
            ),
            'id_greater_than_2'=>array(
				'conditions'=>'author_id <= 2'
			),
        );
    }
    
    /** 
    * Applied to every query unless the default scope is disabled
    */
    public function default_scope()
    {
    	return array(
			'conditions'=>'name != "Uncle Bob"',
		);
    }
    
    /** Parameterized Scope */
    public function is_tito_call()
    {
    	return self::scoped()->where('name="tito"');
    }
    
    /** Parameterized Scope */
    public function last_few($number)
    {
    	return self::scoped()->limit($number);
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
		$authors = IsNotBob::all();
		$this->assertEquals(3,count($authors));
		foreach($authors as $author)
		{
			if($author->name == 'Uncle Bob')
			{
				$this->fail('Bob loaded');
			}
		}
		//$this->fail('just because');
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
	
	public function test_direct_call_to_named_scope()
	{
		$tito = IsNotBob::is_tito()->all();
		$this->assertEquals(1,count($tito));
		$this->assertEquals('Tito', $tito[0]->name);
	}
	
	public function test_direct_call_to_second_named_scope()
	{
		$tito = IsNotBob::some_two()->find('all');
		$this->assertEquals(2,count($tito));
	}
	
	public function test_stacking_three_scopes()
	{
		//Default + some_two() + is_tito
		$tito = IsNotBob::some_two()->is_tito()->find('first');
		$this->assertEquals('Tito',$tito->name);
		$someTwoThatAreNotBob = IsNotBob::some_two()->id_greater_than_2()->all();
		$this->assertEquals(2,count($someTwoThatAreNotBob));
	}
	
	public function test_parameterized_scope()
	{
		$notBob = IsNotBob::scoped()->last_few(2)->all();
		$this->assertEquals(2,count($notBob));
		
	}
	
	public function test_parameterized_scopes_while_disabling_default()
	{
		$hasBob = IsNotBob::scoped()->disable_default_scope()->last_few(4)->all();
		$this->assertEquals(4,count($hasBob));
		$hasBob = IsNotBob::scoped()->last_few(4)->disable_default_scope()->all();
		$this->assertEquals(4,count($hasBob));
	}
	
	public function test_disabling_default_scope()
	{
		$everyone = IsNotBob::scoped()->disable_default_scope()->all();
		$this->assertEquals(4,count($everyone));
	}
	
	public function test_find_uses_default_scope()
	{
		$notBob = IsNotBob::all();
		$this->assertEquals(3,count($notBob));
	}
	
	public function test_count_is_included()
	{
		$this->assertEquals(3,IsNotBob::count());
	}
	
	public function test_count_with_conditions()
	{
		$this->assertEquals(3,IsNotBob::count(array('conditions'=>'author_id IS NOT NULL')));
	}
	
	public function test_find_by_id_with_scope()
	{
		$bushId = 2;
		$this->assertEquals('George W. Bush',IsNotBob::find($bushId)->name);
	}
	
	public function test_find_by_id_beats_out_the_scope()
	{
		$bushId = 4;
		$this->assertEquals('Uncle Bob',IsNotBob::find($bushId)->name);
	}
}
?>