<?php

class AuthorizationComponent extends Object {
	protected $controller;
	protected $action;
	protected $modelClass;
	
	public function startup($controller) {
		$this->controller = $controller;
		$this->action = $controller->action;
		$this->modelClass = $controller->modelClass;
	}
	
	public function authorize($requester, Entity $entity = null) {
		$controller = $this->controller;
		
		if ($entity) {
			$result = $entity->isAuthorized($requester, $controller->action);
			if ($this->checkAuthorized($result)) {
				return;
			}
		}
		
		$Model = $this->getModel();
		if ($Model) {
			try {
				$result = $Model->isAuthorized($requester, $controller, $action);
				if ($this->checkAuthorized($result)) {
					return;
				}
			} catch (Exception $e) {
			}
		}
		
		try {
			$result = $controller->isAuthorized();
			if ($this->checkAuthorized($result)) {
				return;
			}
		} catch (Exception $e) {
		}
		
		trigger_error(sprintf(
			__('%sController::isAuthorized() is not defined.', true), $controller->name
		), E_USER_WARNING);
	}
	
	protected function checkAuthorized($result) {
		if ($result === true) {
			return true;
		}
		
		if ($result === false) {
			$this->controller->cakeError(403);
		}
		
		return false;
	}
	
	protected function getModel() {
		if (empty($this->{$this->modelClass})) return null;
		return $this->{$this->modelClass};
	}
}

