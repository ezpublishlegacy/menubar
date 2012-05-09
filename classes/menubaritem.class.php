<?php

class MenubarItem extends MenubarItemObject
{

	public $Delimiter;

	protected $Menu;

	function __construct($options=false){
		if($options && $this->Options = self::processOptions($options)){
			if($this->Options['switch']['condition']){
				$this->Options['inherit'] = self::executeConditional($this->Options['switch']['condition'], $this->Options['switch']['conditional']);
			}
			if($this->Options['additional']['menu']){
				$this->Menu = MenubarOperator::menubar(array(
					'items' => $this->Options['additional']['menu']
				));
			}
			parent::__construct($this->Options['inherit']);
		}
	}

	function processContentObjectTreeNode(eZContentObjectTreeNode $object, $options=false){
		$Result = parent::processContentObjectTreeNode($object);
		if($options){
			eZDebug::accumulatorStart('menubar_submenu', 'menubar_total', "Menubar Item Menu Generation");
			$this->Menu = MenubarOperator::menubar($options, $object);
			eZDebug::accumulatorStop('menubar_submenu');
		}

		if(Menubar::$Settings['CurrentNode'] && in_array($object->NodeID, Menubar::$Settings['CurrentNode']->pathArray())){
			$this->addClass( ($object->NodeID==Menubar::$Settings['CurrentNode']->NodeID) ? 'current' : 'current-parent');
		}
	}

	static function cacheMenubarItem($key, $item){
		if(!isset($GLOBALS[Menubar::OBJECT_CACHE_KEY][$key])){
			$GLOBALS[Menubar::OBJECT_CACHE_KEY][$key] = array();
		}
		$GLOBALS[Menubar::OBJECT_CACHE_KEY][$key][] = $item->remoteID();
	}

	static function definition(){
		return self::extendDefinition(parent::definition(), array(
			'fields' => array(
				'delimiter'=>array(
					'name'=>'Delimiter',
					'datatype'=>'string',
					'default'=>'',
					'required'=>false
				),
				'menu'=>array(
					'name'=>'Menu',
					'datatype'=>'mixed',
					'default'=>false,
					'required'=>false
				)
			)
		));
	}

	static function Options(){
		return array(
			// inherit parameters
			'content' => '',
			'link' => '',
			'is_external' => false,
			'class' => '',

			// switch-based parameters
			'condition' => false,
			'conditional' => false,

			// additional parameters
			'placement' => false,
			'menu' => false
		);
	}

	static function processOptions($options, $subset=false){
		$Options = array_merge(self::Options(), $options);
		$Options = array(
			'inherit' => array_extract_key($Options, array(
				'content', 'link', 'is_external', 'class'
			)),
			'switch' => array_extract_key($Options, array(
				'condition', 'conditional'
			)),
			'additional' => array_extract_key($Options, array(
				'placement', 'menu'
			))
		);
		return $subset ? $Options[$subset] : $Options;
	}

	protected static function executeConditional($condition, $options){
		eZDebug::accumulatorStart('execute_conditional', 'menubar_total', 'Execute Conditional Menubar Item');
		switch($condition[0]){
			case 'fetch':{
				$ModuleFunction = new eZModuleFunctionInfo($condition[1]);
				$ModuleFunction->loadDefinition();
				$Result = $ModuleFunction->execute($condition[2], isset($condition[3]) ? $condition[3] : false);
				break;
			}
			case 'operator':{
				break;
			}
			case 'result':{
				$Result = $condition[1];
				break;
			}
			default:{
				eZDebug::accumulatorStop('execute_conditional');
				eZDebug::writeError('Unable to determine the method of execution for the condition.', 'Invalid Condition ['.__METHOD__.']');
				return null;
			}
		}

		// check $Result type to confirm a boolean value
		if(is_bool($Result)){
			return $options[$Result];
		}

		eZDebug::accumulatorStop('execute_conditional');
		eZDebug::writeError('The result of the executed condition is not a boolean value.', 'Invalid Condition Result ['.__METHOD__.']');
		return null;
	}

}

?>