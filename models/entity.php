<?php

class Entity extends Object implements ArrayAccess {
	public function allows() {
		return array();
	}
	
	public function isAllowed($method) {
		if (!method_exists($this, $method)) return false;
		
		if (property_exists($this, $method)) return true;
		
		$allows = $this->allows();
		if (empty($allows)) return false;
		if ($allows == '*') return true;
		
		return in_array($method, $allows);
	}
	
	public $_name_;
	
	/**
	 *	Initialize entity attibutes.
	 *	
	 *	@param $model base model object
	 *	@param $data array of data, same structure with the one returned by find('first')
	 */
	public function init(EntityModel $model, $data) {
		assert('is_array($data)');
		
		$this->_name_ = $model->name;
		
		foreach ($data as $modelClass => $values) {
			if ($modelClass == $model->alias) {
				// 自分のクラスのデータだったら、ここの値を属性として登録する
				
				foreach ($values as $key => $val) {
					$model->assignAttribute($this, $key, $val);
				}
			} else {
				// 別のクラスのデータだったら、そのクラスのエンティティとして登録する
				
				$model->assignAttribute($this, $modelClass, $values);
			}
		}
	}
	
	public function getModel() {
		return ClassRegistry::init($this->_name_);
	}
	
	public function __toString() {
		$html = '<div class="entity">';
		foreach ((array) $this as $key => $val) {
			$html .= '<strong class="key">'. h($key). '</strong>'
					.'<span clas="value">'. h(strval($val)). '</span> ';
		}
		$html .= '</div>';
		
		return $html;
	}
	
	// Magic actions =========================================
	
	private function magicExists($key) {
		if ($key[0] == '_') return false;
		if (isset($this->{$key})) return true;
		if ($this->isAllowed($key)) return true;
		return false;
	}
	
	private function magicFetch($key) {
		if ($key[0] == '_') return null;
		
		if (isset($this->{$key})) {
			return $this->{$key};
		}
		
		if ($this->isAllowed($key)) {
			$value = $this->{$key}();
			
			// if property exists, this means cache the result of method.
			if (property_exists($this, $key)) {
				$this->{$key} = $value;
			}
			return $value;
		}
		
		return null;
	}
	
	public function __get($name) {
		return $this->magicFetch($name);
	}
	
	// ArrayAccess implementations ===========================
	
	public function offsetExists($offset) {
		return $this->magicExists($offset);
	}
	
	public function offsetGet($offset) {
		return $this->magicFetch($offset);
	}
	
	public function offsetSet($offset, $value) {
		$this->{$offset} = $value;
	}
	
	public function offsetUnset($offset) {
		unset($this->{$offset});
	}
}

