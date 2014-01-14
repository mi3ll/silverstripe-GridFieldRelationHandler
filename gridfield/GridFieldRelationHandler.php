<?php

abstract class GridFieldRelationHandler implements GridField_ColumnProvider, GridField_HTMLProvider, GridField_ActionProvider {
	protected $targetFragment;
	protected $useToggle;

	protected $columnTitle;
	protected $buttonTitles = array(
		'saveGridRelation' => '',
		'cancelGridRelation' => '',
		'toggleGridRelation' => '',
	);

	public function __construct($useToggle = true, $targetFragment = 'before') {
		$this->targetFragment = $targetFragment;
		$this->useToggle = $useToggle;
	}

	public function setUseToggle($useToggle) {
		$this->useToggle = (bool)$useToggle;
		return $this;
	}

	public function setColumnTitle($columnTitle) {
		$this->columnTitle = $columnTitle;
		return $this;
	}

	public function getColumnTitle() {
		return $this->columnTitle;
	}

	public function setButtonTitle($name, $title) {
		if(isset($this->buttonTitles[$name]))
			$this->buttonTitles[$name] = $title;
		return $this;
	}

	public function getButtonTitle($name) {
		if(isset($this->buttonTitles[$name]))
			return $this->buttonTitles[$name];
		else
			return false;
	}

	protected function getState($gridField) {
		static $state = null;
		if(!$state) {
			$state = $gridField->State->GridFieldRelationHandler;
			$this->setupState($state);
		}
		return $state;
	}

	protected function setupState($state) {
		if(!isset($state->RelationVal)) {
			$state->RelationVal = 0;
			$state->FirstTime = 1;
		} else {
			$state->FirstTime = 0;
		}
		if(!isset($state->ShowingRelation)) {
			$state->ShowingRelation = 0;
		}
	}

	public function augmentColumns($gridField, &$columns) {
		$state = $this->getState($gridField);
		if($state->ShowingRelation || !$this->useToggle) {
			if(!in_array('RelationSetter', $columns)) {
				array_unshift($columns, 'RelationSetter');
			}
			if($this->useToggle && ($key = array_search('Actions', $columns)) !== false) {
				unset($columns[$key]);
			}
		}
	}

	public function getColumnsHandled($gridField) {
		return array('RelationSetter');
	}

	public function getColumnMetadata($gridField, $columnName) {
		if($columnName == 'RelationSetter') {
			return array(
				'title' => $this->columnTitle ? $this->columnTitle : 'Relation Status'
			);
		}
		return array();
	}

	public function getColumnAttributes($gridField, $record, $columnName) {
		return array('class' => 'col-noedit');
	}

	protected function getFields($gridField) {
		$state = $this->getState($gridField);
		if(!$this->useToggle) {
			$fields = array(
				Object::create(
					'GridField_FormAction',
					$gridField,
					'relationhandler-saverel',
					($title = $this->getButtonTitle('saveGridRelation')) ? $title : _t('GridFieldRelationHandler.SAVE_RELATION', 'Save changes'),
					'saveGridRelation',
					null
				)
			);
		} elseif($state->ShowingRelation) {
			$fields = array(
				Object::create(
					'GridField_FormAction',
					$gridField,
					'relationhandler-cancelrel',
					($title = $this->getButtonTitle('cancelGridRelation')) ? $title : _t('GridFieldRelationHandler.CANCLSAVE_RELATION', 'Cancel changes'),
					'cancelGridRelation',
					null
				),
				Object::create(
					'GridField_FormAction',
					$gridField,
					'relationhandler-saverel',
					($title = $this->getButtonTitle('saveGridRelation')) ? $title : _t('GridFieldRelationHandler.SAVE_RELATION', 'Save changes'),
					'saveGridRelation',
					null
				)
			);
		} else {
			$fields = array(
				Object::create(
					'GridField_FormAction',
					$gridField,
					'relationhandler-togglerel',
					($title = $this->getButtonTitle('toggleGridRelation')) ? $title : _t('GridFieldRelationHandler.TOGGLE_RELATION', 'Change relation status'),
					'toggleGridRelation',
					null
				)
			);
		}
		return new ArrayList($fields);
	}

	public function getHTMLFragments($gridField) {
		Requirements::javascript(basename(dirname(__DIR__)) . '/javascript/GridFieldRelationHandler.js');
		$saveRelation = 
		$data = new ArrayData(array(
			'Fields' => $this->getFields($gridField)
		));
		return array(
			$this->targetFragment => $data->renderWith('GridFieldRelationHandlerButtons')
		);
	}

	public function getActions($gridField) {
		return array('saveGridRelation', 'cancelGridRelation', 'toggleGridRelation');
	}

	public function handleAction(GridField $gridField, $actionName, $arguments, $data) {
		if(in_array($actionName, array_map('strtolower', $this->getActions($gridField)))) {
			return $this->$actionName($gridField, $arguments, $data);
		}
	}

	protected function toggleGridRelation(GridField $gridField, $arguments, $data) {
		$state = $this->getState($gridField);
		$state->ShowingRelation = true;
	}

	protected function cancelGridRelation(GridField $gridField, $arguments, $data) {
		$state = $this->getState($gridField);
		$state->ShowingRelation = false;
		$state->FirstTime = true;
	}

	protected function saveGridRelation(GridField $gridField, $arguments, $data) {
		$state = $this->getState($gridField);
		$state->ShowingRelation = false;
	}
}
