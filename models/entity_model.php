<?php

App::import('Model', 'Entity.Entity');

class EntityModel extends EntityAppModel {
	protected $convertToEntity;
	
	/*
	 *	Convert passed $data structure into coresponding entity object.
	 *	@param $data Hash to be converted. If omitted, $this->data will be converted.
	 *	@returns Entity object
	 */
	public function toEntity($data = null) {
		if (is_null($data)) {
			$data = $this->data;
		}
		
		$class = $this->entityClass();
		if (empty($class)) return null;
		
		if (empty($data[$this->name]['id'])) return null;
		
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
			$result[] = (is_null($data) ? null : $this->toEntity($data));
		}
		return $result;
	}
	
	public function beforeFind($queryData) {
		$this->convertToEntity = !empty($queryData['entity']);
		
		return parent::beforeFind($queryData);
	}
	
	public function afterFind($result, $primary) {
		$result = parent::afterFind($result, $primary);
		
		if ($this->convertToEntity and $primary) {
			$result = $this->toEntities($result);
		}
		
		return $result;
	}
	
	public function entityClass() {
		return $this->name. 'Entity';
	}
	
	public function call__($method, $params) {
		$to_entity = false;
		$all = false;
		
		if (preg_match('/^(entity|allentities)by(.+)$/i', $method, $matches)) {
			$to_entity = true;
			$all = (strtolower($matches[1]) == 'allentities');
			$method = ($all ? 'findAllBy' : 'findBy'). $matches[2];
		}
		
		$return = parent::call__($method, $params);
		
		if ($to_entity and !is_null($return)) {
			$return = ($all ? $this->toEntities($return) : $this->toEntity($return));
		}
		
		return $return;
	}
}

