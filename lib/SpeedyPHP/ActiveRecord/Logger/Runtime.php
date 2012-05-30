<?php 
namespace SpeedyPHP\ActiveRecord\Logger;

use \ActiveRecord\Singleton;

class Runtime extends Singleton {
	
	public $log = array();
	
	
	public function log($sql) {
		$this->log[]	= array(
			'sql'				=> $sql,
			'execution_time'	=> 'Err',
			'number_rows'		=> 'Err'
		);	
		
		return $this;
	}
	
	public function push_execution_time($execution_time = -1) {
		$index	= count($this->get_log()) - 1;
		$this->log[$index]['execution_time']= $execution_time;
		return $this;
	}
	
	public function push_number_rows($rows = -1) {
		$index	= count($this->get_log()) - 1;
		$this->log[$index]['number_rows']	= $rows;
		return $this;
	}
	
	public function get_log() {
		return $this->log;
	}
	
	public function count() {
		return count($this->log);
	}
	
}

?>
