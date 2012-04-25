<?php

class MenubarOperator
{
	var $Operators;

	function __construct(){
		$this->Operators = array('menubar', 'menubar_items', 'in_menubar', 'display_menubar');
	}

	function &operatorList(){
		return $this->Operators;
	}

	function namedParameterPerOperator(){
		return true;
	}

	function namedParameterList(){
		return array(
			'menubar'=>array(
				'parameters'=>array('type'=>'mixed', 'required'=>true, 'default'=>false)
			),
			'menubar_items'=>array(
				'parameters'=>array('type'=>'mixed', 'required'=>false, 'default'=>false)
			),
			'in_menubar'=>array(
				'menubar_id'=>array('type'=>'string', 'required'=>true, 'default'=>false)
			)
		);
	}

	function modify(&$tpl, &$operatorName, &$operatorParameters, &$rootNamespace, &$currentNamespace, &$operatorValue, &$namedParameters, &$placement){
		switch($operatorName){
			case 'display_menubar':{
				$Menubar = $operatorValue;
				$operatorValue = '';
				return self::menubar($tpl, $operatorValue, $Menubar);
			}
			case 'in_menubar':{
				if(is_object($operatorValue) && get_class($operatorValue)=='eZContentObjectTreeNode'){
					return self::inMenubar($tpl, $operatorValue, $namedParameters);
				}
				eZDebug::writeError('The operator value is not a content object tree node.', __METHOD__);
				break;
			}
			case 'menubar_items':{
				$MenubarItems=false;
				foreach(current($operatorValue->content()) as $Key=>$Value){
					$ObjectNode=eZContentObjectTreeNode::fetch($Value['node_id']);
					$MenubarItems[]=MenubarItem::convertContentObjectTreeNode($ObjectNode, false);
				}
				$operatorValue=$MenubarItems;
				return true;
			}
			default:{
				eZDebug::createAccumulatorGroup('menubar_total', 'Menubar Operator Total');
				return self::menubar($tpl, $operatorValue, $namedParameters['parameters']);
			}
		}
		return false;
	}

	static function displayMenubar(&$tpl, $menubar){
		eZDebug::accumulatorStart('compile_menubar', 'menubar_total', 'Compile Menubar Template');
		$tpl->setVariable('menubar', $menubar);
		$result = $tpl->fetch('design:menu/menubar.tpl');
		if($tpl->hasVariable('menubar')){
			$tpl->unsetVariable('menubar');
		}
		eZDebug::accumulatorStop('compile_menubar');
		return $result;
	}

	static function getOperatorDefaultParameter($operatorName, $parameterName){
		$Defaults = self::operatorDefaults($operatorName);
		return isset($Defaults[$parameterName]) ? $Defaults[$parameterName] : null;
	}

	static function inMenubar(&$tpl, &$operatorValue, $parameters){
		$Exists=false;
		if(Menubar::hasMenubar($parameters['menubar_id'])){
			$RemoteID = $operatorValue->remoteID();
			$Menubar = Menubar::getMenubar($parameters['menubar_id']);
			foreach($Menubar->getItems() as $Item){
				if($Node = $Item->getNode()){
					if($Exists = ($RemoteID==$Node->remoteID())){
						break;
					}
				}
			}
		}
		$operatorValue = $Exists;
		return true;
	}

	static function menubar(&$tpl, &$operatorValue, $parameters){
		eZDebug::accumulatorStart('menubar_total');
		if(is_object($parameters) && get_class($parameters)=='Menubar'){
			$operatorValue = self::displayMenubar($tpl, $parameters);
			eZDebug::accumulatorStop('menubar_total');
			return true;
		}
		$parameters = array_merge(self::operatorDefaults('menubar'), $parameters);
		if($Menubar = Menubar::initialize($parameters)){
			$operatorValue = $parameters['display'] ? self::displayMenubar($tpl, $Menubar) : $Menubar;
			eZDebug::accumulatorStop('menubar_total');
			return true;
		}
		eZDebug::accumulatorStop('menubar_total');
		return false;
	}

	static function operatorDefaults($operatorName=false){
		$Defaults = array(
			'menubar'=>array(
				'menubar_id'=>false,
				'root_node_id'=>eZINI::instance('content.ini')->variable('NodeSettings','RootNode'),
				'orientation'=>'vertical',
				'display'=>true,
				'class'=>false,
				'include_root_node'=>false,
				'menu_depth'=>1,
				'identifier_list'=>'LeftIdentifierList',
				'fetch_parameters'=>false,
				'use_menu_display'=>false,
				'use_parent'=>true,
				'include'=>array(),
				'append'=>array(),
				'delimiter'=>false,
				'items'=>false
			)
		);
		return $operatorName ? $Defaults[$operatorName] : $Defaults;
	}

}

?>