<?php

App::import('Model', 'Entity.Entity');

class EntityModel extends EntityAppModel {
	protected $entity;
	
	public function toEntity($data) {
		if (empty($this->entity)) return null;
		
		if (empty($data[$this->name]['id'])) return null;
		
		$class = $this->entity;
		if (!class_exists($class)) {
			if (!App::import('Model', $class)) {
				return null;
			}
		}
		
		$entity = new $class();
		$entity->init($this, $data);
		return $entity;
	}
	
	public function toEntities($list_of_data) {
		$result = array();
		foreach ($list_of_data as $data) {
			$result[] = $this->toEntity($data);
		}
		return $result;
	}
	
	public function afterFind($result, $primary) {
		$result = parent::afterFind($result, $primary);
		
		if ($primary and !empty($this->entity)) {
			$result = $this->toEntities($result);
		}
		return $result;
	}
}

