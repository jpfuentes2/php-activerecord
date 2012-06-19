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
	
	
		foreach ($this->_mixins as $mixin => $options) {
			if (is_int($mixin)) {
				$mixin = $options;
			}
	
			if (isset($this->_mixinObjs[$mixin])) {
				continue;
			}
	
			if (is_string($mixin) && class_exists($mixin)) {
				$class = $mixin;
			} elseif (is_string($mixin)) {
				import($mixin);
				$class	= \Speedy\Loader::toClass($mixin);
			}
	
			if (!$class) {
				continue;
			}
	
			$instance	= new $class($this, (is_array($options) ? $options : null));
			$this->_addPropertiesFromMixin($instance);
			$this->_mixinObjs[$mixin] = $instance;
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
			if ($instance instanceof \Speedy\Object && $instance->respondsTo($name)) {
				return call_user_func_array(array($instance, $name), $args);
			}
		}
	
		return parent::__call($name, $args);
	}
	
	/**
	 * Adds properties to owning class
	 * @param object $mixin
	 * @return object $this
	 */
	private function _addPropertiesFromMixin(object $mixin) {
		$callbacks = array('before_save', 'before_create', 'before_update', 'before_validation',
				'before_validation_on_create', 'before_validation_on_update', 'before_destroy',
				'after_save', 'after_create', 'after_update', 'after_validation', 
				'after_validation_on_create', 'after_validation_on_update', 'after_destory');
	
		foreach ($callbacks as $callback) {
			if (isset($mixin->{$callback})) {
				$this->{$callback} = array_merge(
				(is_array($this->{$callback})) ? array() : $this->{$callback},
				$mixin->{$callback}
				);
			}
		}
	
		return $this;
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
