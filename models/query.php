<?php

class Query extends Object implements ArrayAccess, IteratorAggregate, Countable {
	static protected $defaults = array();
	
	static public function setDefaultOptions($Model, $options = array()) {
		self::$defaults[$Model->alias] = $options;
	}
	
	static public function defaultOptions($Model) {
		if (isset(self::$defaults[$Model->alias])) {
			return self::$defaults[$Model->alias];
		}
		
		return array();
	}
	
	protected $Model;
	protected $options;
	public $type;
	
	public function __construct($Model, $type, $options = array(), $default = true) {
		$this->Model = $Model;
		$this->type = $type;
		$this->options = $options;
		
		if ($default) {
			$this->options += self::defaultOptions($Model);
		}
	}
	
	public function result() {
		return $this->Model->find($this->type, $this->options);
	}
	
	public function type($newType) {
		return new Query($this->Model, $newType, $this->options, false);
	}
	
// 	public function __call($method, $args) {
// 		return new Query($this->Model, $newType, $this->options, false);
// 	}
// 	
	public function offsetExists($key) {
		return array_key_exists($key, $this->options);
	}
	
	public function offsetGet($key) {
		return $this->options[$key];
	}
	
	public function offsetSet($key, $value) {
		$this->options[$key] = $value;
	}
	
	public function offsetUnset($key) {
		unset($this->options[$key]);
	}
	
	public function getIterator() {
		return $this->result();
	}
	
	public function count($total = false) {
	}
}

?>
