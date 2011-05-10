<?php

class Entity extends Object implements ArrayAccess {
	/**
	 *	エンティティを生成する。
	 *	
	 *	@param $model 元となるモデル
	 *	@param $data Modelが返す$data配列
	 */
	public function init($model, $data) {
		assert('is_a($model, "AppModel")');
		assert('is_array($data)');
		
		foreach ($data as $modelClass => $values) {
			if ($modelClass == $model->name) {
				// 自分のクラスのデータだったら、プロパティとして登録する
				
				foreach ($values as $key => $val) {
					$this->{$key} = $val;
				}
			} else {
				// 別のクラスのデータだったら、そのクラスのエンティティとして登録する
				
				$another = ClassRegistry::init($modelClass);
				$name = strtolower($modelClass);
				
				if ($another and is_a($another, 'EntityModel')) {
					$values = $another->toEntity(array($modelClass => $values));
				}
				
				
				$this->{$name} = $values;
			}
		}
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
	
	public function offsetExists($offset) {
		return true;
	}
	
	public function offsetGet($offset) {
		return empty($this->{$offset}) ? null : $this->{$offset};
	}
	
	public function offsetSet($offset, $value) {
		$this->{$offset} = $value;
	}
	
	public function offsetUnset($offset) {
		unset($this->{$offset});
	}
}

