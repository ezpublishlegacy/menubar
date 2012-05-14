<?php

class MenubarOperator
{
	var $Operators;

	function __construct(){
		$this->Operators = array('menubar');
	}

	function &operatorList(){
		return $this->Operators;
	}

	function namedParameterPerOperator(){
		return true;
	}

	function namedParameterList(){
		return array(
			'menubar' => array(
				'menubar_id' => array('type' => 'mixed', 'required' => false, 'default' => false),
				'options' => array('type' => 'array', 'required' => false, 'default' => array())
			)
		);
	}

	function modify(&$tpl, &$operatorName, &$operatorParameters, &$rootNamespace, &$currentNamespace, &$operatorValue, &$namedParameters, &$placement){
		switch($operatorName){
			default:{
				if(!$operatorValue || ($operatorValue && is_object($operatorValue))){
					eZDebug::createAccumulatorGroup('menubar_total', 'Menubar Operator Total');
					if(get_class($operatorValue)!='Menubar'){
						if($namedParameters['menubar_id'] && is_array($namedParameters['menubar_id'])){
							array_swap($namedParameters, 'menubar_id', 'options', false);
						}
						$operatorValue = self::menubar($namedParameters, $operatorValue, true);
					}
					$operatorValue = self::displayMenubar($tpl, $operatorValue);
					return true;
				}
				break;
			}
		}
		return false;
	}

	static function displayMenubar(&$tpl, $menubar){
		eZDebug::accumulatorStart('compile_menubar', 'menubar_total', 'Compile Menubar Template');
		$result = TemplateDataOperator::includeTemplate($tpl, 'design:menu/menubar.tpl', array(
			'menubar' => $menubar
		));
		eZDebug::accumulatorStop('compile_menubar');
		return $result;
	}

	static function initializeMenubarOperator(){
		$ContentINI = eZINI::instance('content.ini');
		$MenuINI = eZINI::instance('menu.ini');
		Menubar::$Settings = array(
			'RootNodeID' => $ContentINI->variable('NodeSettings','RootNode'),
			'MenuContentSettings' => $MenuINI->group('MenuContentSettings'),
			'MenubarSettings' => eZINI::instance('menubar.ini')->groups(),
			'CurrentNode' => false
		);
		if(SiteUtils::isContentPage()){
			Menubar::$Settings['CurrentNode'] = eZContentObjectTreeNode::fetch($GLOBALS['eZRequestedModuleParams']['parameters']['NodeID']);
		}
		$GLOBALS[Menubar::OBJECT_CACHE_KEY] = array();
	}

	static function inMenubar($id, eZContentObjectTreeNode $object){
		$Exists = false;
		if(is_string($id)  && isset($GLOBALS[Menubar::OBJECT_CACHE_KEY][$id])){
			$Exists = in_array($object->remoteID(), $GLOBALS[Menubar::OBJECT_CACHE_KEY][$id]);
		}
		return $Exists;
	}

	static function menubar($parameters, $object=false, $isOperator=false){
		if(!$object || is_object($object)){
			eZDebug::accumulatorStart('menubar_total');
			// initial variable initialization
			$MenubarID = false;
			$Options = $parameters;
			if($parameters){
				if($isOperator){
					$MenubarID = $Options['menubar_id'];
					$Options = $Options['options'];
				}
				// check if a "menubar_id" has been set in the options
				if(is_array($Options) && isset($Options['menubar_id'])){
					$MenubarID = $Options['menubar_id'];
					unset($Options['menubar_id']);
				}
			}
			$Options = self::processOptions('menubar', $Options);

			// create instance of the "Menubar" class
			$Menubar = new Menubar($MenubarID, $Options['menubar']);

			// attempt to set static-based items, otherwise attempt a fetch based on the object provided
			if(!$Menubar->setItems($Options['static']['items'])){
				if(!$Menubar->processContentItems($Options['fetch'], $object)){
					// eZDebug::writeError($Menubar, __METHOD__);
					eZDebug::accumulatorStop('menubar_total');
					return $Menubar;
				}
			}

			// handle the additional static-based items
			if($Options['static']['append'] || $Options['static']['include']){
				eZDebug::accumulatorStart('additional_static', 'menubar_total', "Additional Static-Based Items");
				$Static = $Options['static'];
				// add appended items to the end of the include array
				foreach($Static['append'] as $Key=>$Item){
					$Static['include'][] = $Item;
				}
				// process the static include/append items
				$Menubar->processIncludes($Static['include']);
				eZDebug::accumulatorStop('additional_static');
			}

			$Menubar->applyDelimiters();

//	eZDebug::writeDebug($Menubar, __METHOD__.' [$Menubar - '.$Menubar->ID.']');
			eZDebug::accumulatorStop('menubar_total');
			return $Menubar;
		}
		return false;
	}

	static function Options($operator){
		$Options = array(
			'menubar' => array(
//				'menubar_id'=>false,

				// menubar-based parameters
				'include_root_node' => false,
				'menu_depth' => 1,
				'delimiter' => false,
				'allow_menu_display' => false,
				'header' => false,
				'in_menubar' => '',
				'item_limit' => false,

				// menubar-based class parameters
				'orientation' => 'vertical',
				'class' => '',

				// fetch-based parameters
				'root_node_id' => Menubar::$Settings['RootNodeID'],
				'identifier_list' => 'LeftIdentifierList',
				'fetch_parameters' => array(),
				'use_parent' => true,
				'current_only' => false,

				// static-based parameters
				'include' => array(),
				'append' => array(),
				'items' => array()
			)
		);
		return $operator ? $Options[$operator] : $Options;
	}

	static function processOptions($operator, $options){
		$Options = array_merge(self::Options($operator), $options);
		switch($operator){
			case 'menubar':{
				$Options = array(
					'menubar' => array_extract_key($Options, array(
						'include_root_node', 'menu_depth', 'current_only', 'delimiter', 'allow_menu_display', 'header', 'in_menubar', 'item_limit', 'orientation', 'class'
					)),
					'fetch' => array_extract_key($Options, array(
						'root_node_id', 'identifier_list', 'fetch_parameters', 'use_parent'
					)),
					'static' => array_extract_key($Options, array(
						'include', 'append', 'items'
					))
				);
			}
		}
		return $Options;
	}
}


?>