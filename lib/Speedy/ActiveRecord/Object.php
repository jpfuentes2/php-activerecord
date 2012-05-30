<?php
/**
 * Basic object class that supports mixins
 * @author Zachary Quintana
 * @package Speedy\ActiveRecord
 */
namespace Speedy\ActiveRecord;

class Object {
	
	/**
	 * Method mixins
	 * @var array of objects
	 */
	protected $_mixins = array();
	
	/**
	 * Mixin objects
	 * @var array of mixin instances
	 */
	protected $_mixinObjs = array();
	
	/**
	 * $var boolean
	 */
	protected $_mixinsLoaded = false;
	
	
	/**
	 * Checks if mixins already loaded
	 * @return boolean
	 */
	protected function _loadedMixins() {
		return $this->_mixinsLoaded;
	}
	
	/**
	 * Checks if mixin is already loaded
	 * @return boolean
	 */
	protected function _hasMixin($mixin) {
		return isset($this->_mixinObjs[$mixin]);
	}
	
	/**
	 * loads mixins
	 * @return $this
	 */
	protected function _loadMixins() {
		if ($this->_loadedMixins()) return $this;
		if ($this->respondsTo('__loadMixins')) {
			$this->_mixins	= array_merge($this->_mixins, (array) $this->__loadMixins());
		}
		
		
		foreach ($this->_mixins as $class => $options) {
			if (is_int($class)) {
				$class = $options;
			}
			
			if (!class_exists($class)) {
				continue;
			}
			
			if (isset($this->_mixinObjs[$class])) {
				continue;
			}
			
			$instance	= new $class((is_array($options) ? $options : null));
			
			$this->_mixinObjs[$class] = $instance;
		}
		
		$this->_mixinsLoaded = true;
		return $this;
	}
	
	/**
	 * Attempts to call a mixin
	 * 
	 */
	protected function _callMixin($name, $args) {
		foreach ($this->_getMixins() as $instance) {
			if (method_exists($instance, $name)) {
				return call_user_func_array(array($instance, $name), $args);
			}
		}
		
		throw new Exception("No method exists " . get_class($this) . "#$name");
		
		return null;
	}
	
	/**
	 * Gets a mixin
	 * @return mixin instance
	 */
	private function _getMixin($mixin) {
		if (empty($this->_mixinObjs) && !$this->_loadedMixins()) {
			$this->_loadMixins();
		}
		
		return ($this->_hasMixin($mixin)) ? $this->_mixinObjs[$mixin] : null;
	}
	
	/**
	 * Gets all mixins
	 * @return array of mixin instances
	 */
	private function _getMixins() {
		if (empty($this->_mixinObjs) && !$this->_loadedMixins()) {
			$this->_loadMixins();
		}
	
		return $this->_mixinObjs;
	}
	
	public function respondsTo($method) {
		return method_exists($this, $method);
	}
	
	/**
	 * Magic methods for magic getters, setters, and methods
	 */
	public function __call($name, $args) {
		if (!$this->_loadedMixins()) {
			$this->_loadMixins();
		}
		
		return $this->_callMixin($name, $args);
	}
}
