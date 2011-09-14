<?php

App::import('Model', 'Entity.Entity');

class EntityModel extends EntityAppModel {
	public $entity;
	protected $savedEntityStates = array();
	
	/*
	 *	Convert passed $data structure into coresponding entity object.
	 *	@param $data Hash to be converted. If omitted, $this->data will be converted.
	 *	@returns Entity object
	 */
	protected function convertToEntity($data) {
		if (is_null($data) or empty($data[$this->name]['id'])) return null;
		
		return $this->entity($data);
	}
	
	protected function convertToEntities($list_of_data) {
		if (!Set::numeric(array_keys($list_of_data))) {
			return $this->convertToEntity($list_of_data);
		}
		
		$result = array();
		foreach ($list_of_data as $data) {
			$result[] = $this->convertToEntity($data);
		}
		return $result;
	}
	
	public function entity($data = null) {
		if ($data) {
			$class = $this->entityClassForData($data[$this->name]);
		} else {
			$class = $this->entityClass();
		}
		
		if (!class_exists($class)) {
			if (!App::import('Model', $class)) {
				$class = 'Entity';
			}
		}
		
		$entity = new $class();
		$entity->init($this, $data);
		return $entity;
	}
	
	public function beforeFind($queryData) {
		$this->saveEntityState();
		
		if (!empty($queryData['entity'])) {
			$this->entity = true;
		}
		
		return parent::beforeFind($queryData);
	}
	
	public function afterFind($result, $primary) {
		$result = parent::afterFind($result, $primary);
		
		if ($this->entity and $primary and is_array($result)) {
			$result = $this->convertToEntities($result);
		}
		
		$this->restoreEntityState();
		return $result;
	}
	
	protected function saveEntityState() {
		$this->savedEntityStates[] = $this->entity;
	}
	
	protected function restoreEntityState() {
		$this->entity = array_pop($this->savedEntityStates);
	}
	
	protected function entityClass() {
		return $this->name. 'Entity';
	}
	
	protected function entityClassForData($data) {
		return $this->entityClass();
	}
	
	public function allEntities($params = array()) {
		$params['entity'] = true;
		return $this->find('all', $params);
	}
	
	public function entities($params = array()) {
		return $this->allEntities($params);
	}
	
	public function call__($method, $params) {
		$to_entity = false;
		$all = false;
		
		if (preg_match('/^(entity|(?:all)?entities)by(.+)$/i', $method, $matches)) {
			$to_entity = true;
			$all = (strtolower($matches[1]) == 'allentities');
			$method = ($all ? 'findAllBy' : 'findBy'). $matches[2];
		}
		
		$return = parent::call__($method, $params);
		
		if ($to_entity and !is_null($return)) {
			$return = ($all ? $this->convertToEntities($return) : $this->convertToEntity($return));
		}
		
		return $return;
	}
	
	public function paginate($conditions, $fields, $order, $limit, $page, $recursive, $extra) {
		$params = compact('conditions', 'fields', 'order', 'limit', 'page');
		
		if ($recursive != $this->recursive) {
			$params['recursive'] = $recursive;
		}
		
		$type = !empty($extra['type']) ? $extra['type'] : 'all';
		
		return $this->find($type, array_merge($params, $extra));
	}
	
	public function count($conditions = null) {
		return $this->find('count', array(
			'conditions' => $conditions, 
			'recursive' => -1
		));
	}
	
	public function assignAttribute(Entity $entity, $original_name, $value) {
		$name = Inflector::underscore($original_name);
		
		$association = $this->getAssociationData($original_name);
		if ($association) {
			$anotherModelClass = $association['className'];
			$another = ClassRegistry::init($anotherModelClass);
			
			if ($another and is_a($another, 'EntityModel')) {
				switch ($association['type']) {
					case 'hasOne':
					case 'belongsTo':
						$data = array($anotherModelClass => $value);
						$value = $another->entity($data);
						break;
						
					case 'hasMany':
						$result = array();
						foreach ($value as $columns) {
							$data = array($anotherModelClass => $columns);
							$result[] = $another->entity($data);
						}
						$name = Inflector::pluralize($name);
						$value = $result;
						break;
				}
			}
		}
		
		$entity->{$name} = $value;
	}
	
	public function getAssociationData($name) {
		foreach ($this->__associations as $type) {
			if (!empty($this->{$type}[$name])) {
				return $this->{$type}[$name] + array('type' => $type, 'name' => $name);
			}
		}
		
		return null;
	}
}

