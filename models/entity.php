<?php

class Entity extends Object implements ArrayAccess {
	public function allows() {
		return array();
	}
	
	public function isAllowed($method) {
		if (!EntityAccessor::methodExists($this, $method)) {
			return false;
		}
		
		if (EntityAccessor::propertyExists($this, $method)) {
			return true;
		}
		
		$allows = $this->allows();
		if (empty($allows)) {
			return false;
		}
		
		if ($allows == '*') {
			return true;
		}
		
		return in_array($method, $allows);
	}
	
	/**************************************
	 * Model bindings					  *
	 **************************************/
	
	public $_name_;
	private $modelClass;
	
	/**
	 *	Bind the entity and its source model.
	 *	
	 *	@param $model base model object
	 *	@param $data array of data, same structure with the one returned by find('first')
	 *	@access ment to be public only for EntityModel.
	 */
	public function bind(EntityModel $model, $data) {
		assert('is_array($data)');
		
		$this->_name_ = $model->alias;
		$this->modelClass = $model->name;
		
		foreach ($data as $modelClass => $values) {
			if ($modelClass == $model->alias) {
				/* if the data is array of values for my class, 
				   use them as a property */
				
				foreach ($values as $key => $val) {
					$model->assignProperty($this, $key, $val);
				}
			} else {
				/* if not for my class, assign as another entity */
				
				$model->assignProperty($this, $modelClass, $values);
			}
		}
	}
	
	public function getModel() {
		return ClassRegistry::init(array(
			'class' => $this->modelClass, 
			'alias' => $this->_name_, 
			'type' => 'Model', 
		));
	}
	
	public function isEqual($other) {
		if (!$other) {
			return false;
		}
		
		if (empty($other->id)) {
			return false;
		}
		
		if (get_class($other) != get_class($this)) {
			return false;
		}
		
		return (strval($other->id) == strval($this->id));
	}
	
	public function save($fields = null) {
		$Model = $this->getModel();
		$Model->id = isset($this->id) ? $this->id : null;
		
		if ($fields) {
			foreach ((array) $fields as $field) {
				$value = isset($this->{$field}) ? $this->{$field} : null;
				$ok = $Model->saveField($field, $value);
				if (!$ok) {
					return false;
				}
			}
			return true;
		} else {
			$Model->create();
			
			$data = $this->toArray();
			return $Model->save($data);
		}
	}
	
	// Magic actions =========================================
	
	public function __toString() {
		$html = '<div class="'. $this->_name_. '">';
		foreach (EntityAccessor::properties($this) as $key => $val) {
			$html .= '<strong class="key">'. h($key). '</strong>'
					.'<span clas="value">'. h(strval($val)). '</span>';
		}
		$html .= '</div>';
		
		return $html;
	}
	
	public function toArray() {
		$data = Set::reverse($this);
		
		foreach (array_keys($data[$this->_name_]) as $name) {
			if (!is_array($data[$this->_name_][$name])) {
				continue;
			}
			
			if (Set::numeric(array_keys($data[$this->_name_][$name]))) {
				// has many association
				
				$list = $data[$this->_name_][$name];
				unset($data[$this->_name_][$name]);
				
				$name = Inflector::classify($name);
				$data[$name] = array();
				foreach ($list as $sub) {
					if (is_array($sub)) {
						$sub = current($sub);
					}
					$data[$name][] = $sub;
				}
			} else {
				// has one association
				
				$sub = $data[$this->_name_][$name];
				unset($data[$this->_name_][$name]);
				
				$data[$name] = $sub;
			}
		}
		
		return $data;
	}
	
	private function magicFetch($key, &$value) {
		if ($key[0] == '_') {
			return null;
		}
		
		if (isset($this->{$key})) {
			$value = $this->{$key};
			return true;
		}
		
		if ($this->isAllowed($key)) {
			$value = $this->{$key}();
			
			// if property exists, this means cache the result of method.
			if (EntityAccessor::propertyExists($this, $key)) {
				$this->{$key} = $value;
			}
			return true;
		}
		
		$Model = $this->getModel();
		if ($key == $Model->alias) {
			$value = $this;
			return true;
		}
		
		return false;
	}
	
	// ArrayAccess implementations ===========================
	
	public function offsetExists($key) {
		return $this->magicFetch($key, $value);
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

class EntityAccessor {
	static public function methodExists(Entity $entity, $method) {
		try {
			$ref = new ReflectionMethod($entity, $method);
			return $ref->isPublic();
		} catch (ReflectionException $e) {
		}
		return false;
	}
	
	static public function propertyExists(Entity $entity, $property) {
		try {
			$ref = new ReflectionProperty($entity, $property);
			return $ref->isPublic();
		} catch (ReflectionException $e) {
		}
		return false;
	}
	
	static public function properties(Entity $entity) {
		$properties = get_object_vars($entity);
		unset($properties['_name_']);
		return $properties;
	}
}

class EntityModifier {
	public function reverse($entity, $key, &$value) {
		if (!preg_match('/^reverse_(.+)$/', $key, $match)) {
			return false;
		}
		
		$key = $match[1];
		$value = $entity[$key];
		if (is_null($value)) {
			return false;
		}
		
		if (is_array($value)) {
			$value = array_reverse($value);
		} else {
			$value = implode('', array_reverse(str_split(strval($value))));
			
		}
		
		return true;
	}
}

?>
