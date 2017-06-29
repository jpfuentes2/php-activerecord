<?php
include 'helpers/config.php';
class NullScope extends Author
{
	public static $table_name = 'authors';
  	public static $default_scope = array(
		'conditions'=>'parent_author_id IS NULL',
	);
}

class IsNotBob extends Author
{
	public static $table_name = 'authors';
	
	public static $has_many = array(
		array('parents','foreign_key'=>'parent_author_id','class_name'=>'IsNotBob'),
	);
	
	public static $named_scopes = 
            array(
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
    public $scoped_name = null;
    /** 
    * Applied to every query unless the default scope is disabled
    */
  	public function default_scope()
	{
		if($this->scoped_name)
		{
			return array('conditions'=>array('name = "'.$this->scoped_name.'"'));
		}
		return array('conditions'=>array('name != "Uncle Bob"'));
	} 
	
	public static function is_tito_join()
	{
		return static::scoped()->joins('LEFT JOIN authors a2 on a2.author_id = authors.author_id')
			->where('a2.name ="tito"');
	}
	
	public static function is_bob_join()
	{
		return static::scoped()->joins('LEFT JOIN authors a3 on a3.author_id = authors.author_id')
			->where('a3.name ="Uncle Bob"');
	}
    
    public static function is_tito_call()
    {
    	return self::scoped()->where('name="tito"');
    }

	public static function is_bob_call()
	{
		return self::scoped()->where('name=? AND name = ?','bob','bob');
	}
    
    /** Parameterized Scope */
    public static function last_few($number)
    {
    	return self::scoped()->limit($number);
    }
}
class IsNotBobNoDefault extends IsNotBob
{
	public function default_scope()
	{
		return null;
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
	
	public function test_join()
	{
		$tito = IsNotBobNoDefault::is_tito_join()->all();
		$this->assertEquals(1,count($tito));
		$this->assertEquals('Tito', $tito[0]->name);
	}
	
	public function test_double_join()
	{
		$impossible_double_join = IsNotBobNoDefault::is_tito_join()->is_bob_join()->all();
		$this->assertEquals(0,count($impossible_double_join));
	}
	
	
	public function test_class_with_no_default_scope_also_behaves_correctly()
	{
		$tito = IsNotBobNoDefault::is_tito()->all();
		$this->assertEquals(1,count($tito));
		$this->assertEquals('Tito', $tito[0]->name);
	}
	
	public function test_combining_scopes_creates_a_valid_query()
	{
		$scope = IsNotBob::is_bob_call()->is_tito_call();
		$scope->all();
	}

	public function test_calling_count_on_a_scope_instance()
	{
		$count = IsNotBob::scoped()->disable_default_scope()->count();
		$this->assertEquals(4,$count);
	}
	
	public function test_calling_count_on_a_scope_instance_with_options()
	{
		$count = IsNotBob::scoped()->disable_default_scope()->count(array('conditions'=>array('name'=>'tito')));
		$this->assertEquals(1,$count);
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
	
	public function test_disabled_default_scope_with_array_conditions_in_search()
	{
		$hasBob = IsNotBob::scoped()->disable_default_scope()->find(array('parent_author_id'=>1));
		$this->assertEquals(1,count($hasBob));
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
	
	public function test_null_scope_gets_removed_correctly()
	{
		$author1 = NullScope::find(1);
		$author1->parent_author_id = null;
		$author1->save();
		
		$author = NullScope::scoped()->all(array('conditions'=>
				array('parent_author_id'=>1))
			);
		$this->assertEquals(0,count($author));
			
		$author = NullScope::scoped()->disable_default_scope()->all(array('conditions'=>
				array('parent_author_id'=>1))
			);
		$this->assertEquals('Bill Clinton',$author[0]->name);
	}
	
	public function test_using_null_in_a_comparison()
	{
		$author1 = NullScope::find(1);
		$author1->parent_author_id = null;
		$author1->save();
		
		$author = NullScope::scoped()->disable_default_scope()->all(
			array('conditions'=>
				array('parent_author_id'=>null))
			);
		$this->assertEquals('Tito',$author[0]->name);
	}
	public function test_relation_with_scope()
	{
		$tito = IsNotBob::find(1);
		$bush = IsNotBob::find(2);
		$bush->parent_author_id = 4;
		$bush->save();
		
		$this->assertEquals(1,count($tito->parents));
		$this->assertEquals(0,count($bush->parents));
	}
	
	public function test_query_with_null_condition_is_generated_correctly_through_scope()
	{
		$result = IsNotBob::all(array('conditions'=>array('updated_at'=>null,'author_id'=>3)));
		$this->assertEquals(1,count($result));
	}
	
	public function test_scope_on_a_model_instance()
	{
		$first = IsNotBob::find(1);
		$this->assertEquals('Tito',$first->name);
		
		$first->scoped_name = 'George W. Bush';
		$result = $first->scope()->first();
		$this->assertEquals('George W. Bush',$result->name);
	}
	
	public function test_adding_a_scope_returns_instance_of_scope_and_not_query()
	{
		$scope = IsNotBob::is_tito_call();
		$this->assertInstanceOf('ActiveRecord\Scopes', $scope);
	}
	
	public function test_call_to_exists()
	{
		$exists = IsNotBob::scoped()->disable_default_scope()->exists();
		$this->assertTrue($exists);
	}
	public function test_call_to_exists_with_conditions()
	{
		$exists = IsNotBob::scoped()->disable_default_scope()->exists(
			array('conditions'=>array('name'=>'made up name')));
		$this->assertFalse($exists);
		$exists = IsNotBob::scoped()->disable_default_scope()->exists(
			array('conditions'=>array('name'=>'Tito')));
		$this->assertTrue($exists);
	}
	
	public function test_calling_undefined_method_on_a_scope()
	{
		try
		{
			IsNotBob::is_tito_call()->boogalooga();
		}
		catch(ActiveRecord\ActiveRecordException $e)
		{
			return true;
		}
		$this->fail('No exception was thrown');
	}
	
}
?>