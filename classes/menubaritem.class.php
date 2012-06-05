<?php

class MenubarItem extends MenubarItemObject
{

	const CLASS_NAME = 'MenubarItem';

	public $Delimiter;

	protected $Priority;
	protected $Menu;
	protected $Options = array();

	function __construct($options=false){
		if($options){
			$Options = $this->Options = OptionsHandler::create('MenubarItem');
			if($Options->process($options)){
				if($Options->has('switch', true)){
					$Options->set('inherit', self::executeConditional($Options->get('switch', true)), true);
				}
				if($Options->has('menu')){
					$this->Menu = MenubarOperator::menubar(array(
						'items' => $Options->get('menu')
					));
				}
				parent::__construct($Options->get('inherit', true));
			}
		}
	}

	function processContentObjectTreeNode(eZContentObjectTreeNode $object, $options=false){
		$Result = parent::processContentObjectTreeNode($object);
		$this->Priority = $object->Priority;
		if($options){
			eZDebug::accumulatorStart('menubar_submenu', 'menubar_total', "Menubar Item Menu Generation");
			$this->Menu = MenubarOperator::menubar($options, $object);
			eZDebug::accumulatorStop('menubar_submenu');
		}
		if(Menubar::$Settings['CurrentNode'] && in_array($object->NodeID, Menubar::$Settings['CurrentNode']->pathArray())){
			$this->addClass(($object->NodeID==Menubar::$Settings['CurrentNode']->NodeID) ? 'current' : 'current-parent');
		}
		return true;
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
				'delimiter' => array(
					'name' => 'Delimiter',
					'datatype' => 'string',
					'default' => '',
					'required' => false
				),
				'menu' => array(
					'name' => 'Menu',
					'datatype' => 'mixed',
					'default' => false,
					'required' => false
				),
				'priority'=>array(
					'name' => 'Priority',
					'datatype' => 'integer',
					'default' => 0,
					'required' => true
				)
			)
		));
	}

	static function Options(){
		$Configuration = array(
			'base' => parent::CLASS_NAME,
			'scheme' => array(
				'condition' => array(
					'type' => 'array',
					'default' => array()
				),
				'conditional' => array(
					'type' => 'array',
					'default' => array()
				),
				'placement' => array(
					'type' => 'integer',
					'default' => 0
				),
				'menu' => array(
					'type' => 'array | object',
					'default' => array()
				)
			),
			'group' => array(
				array(
					'name' => 'inherit',
					'options' => '%base%'
				),
				array(
					'name' => 'switch',
					'trigger' => 'condition',
					'options' => array('condition', 'conditional')
				),
				array(
					'name' => 'additional',
					'options' => array('placement', 'menu')
				)
			)
		);
		return new OptionsScheme($Configuration);
	}

	protected static function executeConditional($options){
		eZDebug::accumulatorStart('execute_conditional', 'menubar_total', 'Execute Conditional Menubar Item');
		$Condition = $options['condition'];
		$Options = $options['conditional'];
		switch($Condition[0]){
			case 'fetch':{
				$ModuleFunction = new eZModuleFunctionInfo($Condition[1]);
				$ModuleFunction->loadDefinition();
				$Result = $ModuleFunction->execute($Condition[2], isset($Condition[3]) ? $Condition[3] : false);
				break;
			}
			case 'operator':{
				break;
			}
			case 'result':{
				$Result = $Condition[1];
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
			return $Options[$Result];
		}

		eZDebug::accumulatorStop('execute_conditional');
		eZDebug::writeError('The result of the executed condition is not a boolean value.', 'Invalid Condition Result ['.__METHOD__.']');
		return null;
	}

}

?>