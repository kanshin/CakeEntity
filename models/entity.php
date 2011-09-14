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
	
	public function save($fields = null) {
		$Model = $this->getModel();
		
		if ($fields) {
			foreach ((array) $fields as $field) {
				$value = isset($this->{$field}) ? $this->{$field} : null;
				$Model->saveField($field, $value);
			}
		} else {
			$data = Set::reverse($this);
			return $Model->save($data);
		}
	}
	
	// Authorization =========================================
	
	public function isAuthorized($requester, $action) {
		return true;
	}
	
	// Magic actions =========================================
	
	public function __toString() {
		$html = '<div class="entity">';
		foreach ((array) $this as $key => $val) {
			$html .= '<strong class="key">'. h($key). '</strong>'
					.'<span clas="value">'. h(strval($val)). '</span> ';
		}
		$html .= '</div>';
		
		return $html;
	}
	
	private function magicExists($key) {
		if ($key[0] == '_') return false;
		if (isset($this->{$key})) return true;
		if ($this->isAllowed($key)) return true;
		return false;
	}
	
	private function magicFetch($key, &$value) {
		if ($key[0] == '_') return null;
		
		if (isset($this->{$key})) {
			$value = $this->{$key};
			return true;
		}
		
		if ($this->isAllowed($key)) {
			$value = $this->{$key}();
			
			// if property exists, this means cache the result of method.
			if (property_exists($this, $key)) {
				$this->{$key} = $value;
			}
			return true;
		}
		
		return false;
	}
	
	// ArrayAccess implementations ===========================
	
	public function offsetExists($key) {
		return $this->magicExists($key);
	}
	
	public function offsetGet($key) {
		if ($this->magicFetch($key, $value)) {
			return $value;
		}
		
		if (self::$modifier == null) {
			self::$modifier = new EntityModifier();
			self::$modifierMethods = get_class_methods(self::$modifier);
		}
		
		foreach (self::$modifierMethods as $method) {
			if (self::$modifier->{$method}($this, $key, $value)) {
				return $value;
			}
		}
		
		return null;
	}
	
	public function offsetSet($key, $value) {
		$this->{$key} = $value;
	}
	
	public function offsetUnset($key) {
		unset($this->{$key});
	}
	
	static protected $modifier = null;
	static protected $modifierMethods = null;
}

class EntityModifier {
	public function reverse($entity, $key, &$value) {
		if (!preg_match('/^reverse_(.+)$/', $key, $match)) return false;
		
		$key = $match[1];
		$value = $entity[$key];
		if (is_null($value)) return false;
		
		if (is_array($value)) {
			$value = array_reverse($value);
		} else {
			$value = implode('', array_reverse(str_split(strval($value))));
			
		}
		
		return true;
	}
}

