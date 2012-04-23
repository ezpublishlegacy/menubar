<?php

class MenubarItem extends PersistentObject
{

	public $Content;
	public $hasMenu;
	public $hasMenuDisplay;
	public $Link;
	public $Delimiter;
	public $isExternal = false;

	protected $Class;
	protected $Node = false;
	protected $Menu = false;
	protected $MenuDisplay = false;
	protected $Settings = false;

	private static $MenuDisplaySettings = false;

	const GLOBALS_KEY = 'MenubarCurrentNodeData';

	function __construct($content, $isExternal=false, $node=false, $isRootItem=false){
		$this->Content = $content;
		$this->hasMenu = false;
		$this->isExternal = $isExternal;
		if($node){
			$this->addClass("node_id_$node->NodeID");
			if($CurrentNodeData = self::getCurrentNodeData()){
				if($node->NodeID==$CurrentNodeData['NodeID']){
					$this->addClass('current');
				}
				if(!$isRootItem && in_array($node->NodeID, $CurrentNodeData['PathArray'])){
					$this->addClass('current-parent');
				}
			}
			$this->Node = serialize($node);
			if(!$this->Settings){
				$this->Settings = eZINI::instance('menubar.ini')->group('MenubarItem');
			}
			$LinkHandlerClass = $this->Settings['LinkHandlerList'][$this->Settings['LinkEngine']];
			eZDebug::accumulatorStart('generate_link', 'menubar_total', "Generate Menubar Item Link [$LinkHandlerClass]");
			$this->Link = call_user_func(array($LinkHandlerClass, 'process'), $this);
			eZDebug::accumulatorStop('generate_link');
		}
	}

	function addClass($class){
		$this->Class[$class] = $class;
	}

	function compileClassList(){
		if($this->Class){
			return implode(' ', $this->Class);
		}
		return false;
	}

	function getMenu(){
		if($this->hasMenu){
			return $this->Menu;
		}
		return false;
	}

	function getMenuDisplay(){
		if($this->hasMenuDisplay){
			return $this->MenuDisplay;
		}
		return false;
	}

	function getNode(){
		if($this->Node){
			return unserialize($this->Node);
		}
		return false;
	}

	function hasLink(){
		return (bool)$this->Link;
	}

	function removeClass($class){
		unset($this->Class[$class]);
	}

	function setMenu($menu){
		$this->Menu = $menu;
		$this->hasMenu = true;
	}

	function setMenuDisplay($items){
		$this->MenuDisplay = $items;
		$this->hasMenuDisplay = true;
	}

	static function createFromContentObjectTreeNode($object, $isRootItem=false){
		eZDebug::accumulatorStart('create_from_treenode', 'menubar_total', 'Create MenubarItem From ContentObjectTreeNode');
		$Content = htmlentities($object->Name, ENT_COMPAT | 'ENT_HTML5' | 'ENT_HTML401', "UTF-8");
		$isExternal = false;
		if($object->ClassIdentifier=='link'){
			$DataMap = $object->dataMap();
			foreach($DataMap as $Identifier=>$Attribute){
				if($Attribute->attribute('data_type_string')=='ezurl' && !empty($Attribude->DataText)){
					$Content = $Attribute->DataText;
					break;
				}
			}
			if(isset($DataMap['open_in_new_window']) && $DataMap['open_in_new_window']->DataInt){
				$isExternal = true;
			}
		}
		$Instance = new self($Content, $isExternal, $object, $isRootItem);
		eZDebug::accumulatorStop('create_from_treenode');
		return $Instance;
	}

	static function createFromParameters($parameters){
		// execute conditional parameter to determine user defined parameters
		if(isset($parameters['condition'])){
			if(!$parameters['conditional']){
				eZDebug::writeError('The "condition" parameter was specified without a "conditional" parameter.', 'Invalid Parameter ['.__METHOD__.']');
				return false;
			}
			// reassign the result parameters to the primary user defined parameters
			$parameters = self::executeConditional($parameters['condition'], $parameters['conditional']);
		}
		$Instance = new self($parameters['content']);
		if(isset($parameters['link'])){
			$Instance->Link = $parameters['link'];
		}
		if(isset($parameters['class'])){
			foreach(explode(' ', $parameters['class']) as $class){
				$Instance->addClass($class);
			}
		}
		return $Instance;
	}

	static function definition(){
		return array(
			'fields'=>array(
				'content'=>array(
					'name'=>'Content',
					'datatype'=>'string',
					'default'=>'',
					'required'=>true
				),
				'link'=>array(
					'name'=>'Link',
					'datatype'=>'string',
					'default'=>'',
					'required'=>true
				),
				'delimiter'=>array(
					'name'=>'Delimiter',
					'datatype'=>'string',
					'default'=>'',
					'required'=>false
				),
				'has_menu'=>array(
					'name'=>'hasMenu',
					'datatype'=>'boolean',
					'default'=>false,
					'required'=>true
				),
				'has_menu_display'=>array(
					'name'=>'hasMenuDisplay',
					'datatype'=>'boolean',
					'default'=>false,
					'required'=>true
				),
				'is_external'=>array(
					'name'=>'isExternal',
					'datatype'=>'boolean',
					'default'=>false,
					'required'=>true
				)
			),
			'function_attributes'=>array(
				'class'=>'compileClassList',
				'menu'=>'getMenu',
				'menu_display'=>'getMenuDisplay',
				'has_link'=>'hasLink',
				'node'=>'getNode'
			)
		);
	}

	static function executeConditional($condition, $options){
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
			eZDebug::accumulatorStop('execute_conditional');
			return $options[$Result];
		}

		eZDebug::accumulatorStop('execute_conditional');
		eZDebug::writeError('The result of the executed condition is not a boolean value.', 'Invalid Condition Result ['.__METHOD__.']');
		return null;
	}

	static function fetchMenuDisplay($item, $return=false){
		if(!self::$MenuDisplaySettings){
			self::$MenuDisplaySettings = eZINI::instance('menubar.ini')->group('MenuDisplay');
		}
		$MenuDisplayItems = unserialize($item->Node)->subTree(array(
			'ClassFilterType'=>'include',
			'ClassFilterArray'=>array(self::$MenuDisplaySettings['ClassIdentifier'])
		));
		if($Count = count($MenuDisplayItems)){
			if($Count > self::$MenuDisplaySettings['AllowedPerItem']){
				$MenuDisplayItems = array_slice($MenuDisplayItems, 0, self::$MenuDisplaySettings['AllowedPerItem']);
			}
			$item->setMenuDisplay($MenuDisplayItems);
			return true;
		}
		return false;
	}

	static function getCurrentNodeData(){
		if(isset($GLOBALS[self::GLOBALS_KEY])){
			return $GLOBALS[self::GLOBALS_KEY];
		}
		if(SiteUtils::isContentPage()){
			$ModuleData = $GLOBALS['eZRequestedModuleParams'];
			$ContentNode = eZContentObjectTreeNode::fetch($ModuleData['parameters']['NodeID']);
			return $GLOBALS[self::GLOBALS_KEY] = array(
				'NodeID'=>$ModuleData['parameters']['NodeID'],
				'PathArray'=>array_slice(array_reverse($ContentNode->pathArray()), 1)
			);
		}
		return $GLOBALS[self::GLOBALS_KEY] = false;
	}

}

?>