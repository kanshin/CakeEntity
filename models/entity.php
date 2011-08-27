<?php

class Entity extends Object implements ArrayAccess {
	public $_modelName_;
	
	/**
	 *	Initialize entity attibutes.
	 *	
	 *	@param $model base model object
	 *	@param $data array of data, same structure with the one returned by find('first')
	 */
	public function init(EntityModel $model, $data) {
		assert('is_array($data)');
		
		$this->_modelName_ = $model->name;
		
		foreach ($data as $modelClass => $values) {
			if ($modelClass == $model->name) {
				// 自分のクラスのデータだったら、プロパティとして登録する
				
				foreach ($values as $key => $val) {
					$this->{$key} = $val;
				}
			} else {
				// 別のクラスのデータだったら、そのクラスのエンティティとして登録する
				
				$name = Inflector::underscore($modelClass);
				
				$association = $model->findAssociation($modelClass);
				if ($association) {
					$anotherModelClass = $association['className'];
					
					$another = ClassRegistry::init($anotherModelClass);
					
					if ($another and is_a($another, 'EntityModel')) {
						$values = $another->entity(array($anotherModelClass => $values));
					}
				}
				
				$this->{$name} = $values;
			}
		}
	}
	
	public function getModel() {
		return ClassRegistry::init($this->_modelName_);
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
	
	// ArrayAccess implementations ===========================
	
	public function offsetExists($offset) {
		return isset($this->{$offset});
	}
	
	public function offsetGet($offset) {
		return isset($this->{$offset}) ? $this->{$offset} : null;
	}
	
	public function offsetSet($offset, $value) {
		$this->{$offset} = $value;
	}
	
	public function offsetUnset($offset) {
		unset($this->{$offset});
	}
}

