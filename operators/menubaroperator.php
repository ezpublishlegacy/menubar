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
			'CurrentNode' => false,
			'TopLevelNodes' => array_values($ContentINI->group('NodeSettings'))
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
			if($parameters){
				if($isOperator){
					$MenubarID = $parameters['menubar_id'];
					$parameters = $parameters['options'];
				}
				// check if a "menubar_id" has been set in the options
				if(is_array($parameters) && isset($parameters['menubar_id'])){
					$MenubarID = $parameters['menubar_id'];
					unset($parameters['menubar_id']);
				}
			}

			$Options = OptionsHandler::create('MenubarOperator');
			if($Options->process($parameters, 'menubar')){
				// create instance of the "Menubar" class
				$Menubar = new Menubar($MenubarID, $Options->export('menubar', true));

				// attempt to set static-based items, otherwise attempt a fetch based on the object provided
				if(!$Menubar->setItems($Options->get('items'))){
					if(!$Menubar->processContentItems($Options->get('fetch', true), $object)){
						// eZDebug::writeError($Menubar, __METHOD__);
						eZDebug::accumulatorStop('menubar_total');
						return $Menubar;
					}
				}

				// handle the additional static-based items
				if(($hasAppend = $Options->has('append')) || $Options->has('include')){
					eZDebug::accumulatorStart('additional_static', 'menubar_total', "Additional Static-Based Items");
					$Includes = $Options->get('include');
					if($hasAppend){
						// add appended items to the end of the include array
						foreach($Options->get('append') as $Key=>$Item){
							$Includes[] = $Item;
						}
					}
					// process the static include/append items
					$Menubar->processIncludes($Includes);
					eZDebug::accumulatorStop('additional_static');
				}

				$Menubar->applyDelimiters();
				eZDebug::accumulatorStop('menubar_total');
				return $Menubar;
			}
		}
		return false;
	}

	static function Options(){
		$Options = array(
			'menubar' => array(
				'scheme' => array(
					// menubar-based parameters
					'include_root_node' => array(
						'type' => 'boolean',
						'default' => false
					),
					'menu_depth' => array(
						'type' => 'integer',
						'default' => 1
					),
					'delimiter' => array(
						'type' => 'boolean | string',
						'default' => false
					),
					'allow_menu_display' => array(
						'type' => 'boolean',
						'default' => false
					),
					'header' => array(
						'type' => 'array | boolean | string',
						'default' => false
					),
					'in_menubar' => array(
						'type' => 'string',
						'default' => ''
					),
					'item_limit' => array(
						'type' => 'integer',
						'default' => 0
					),
					'split' => array(
						'type' => 'array | boolean',
						'default' => false
					),

					// menubar-based class parameters
					'orientation' => array(
						'type' => 'string',
						'default' => 'vertical'
					),
					'class' => array(
						'type' => 'string',
						'default' => ''
					),

					// fetch-based parameters
					'root_node_id' => array(
						'type' => 'integer',
						'default' => (int) Menubar::$Settings['RootNodeID']
					),
					'identifier_list' => array(
						'type' => 'string',
						'default' => 'LeftIdentifierList'
					),
					'fetch_parameters' => array(
						'type' => 'array',
						'default' => array()
					),
					'use_parent' => array(
						'type' => 'boolean',
						'default' => true
					),
					'current_only' => array(
						'type' => 'boolean',
						'default' => false
					),

					// static-based parameters
					'include' => array(
						'type' => 'array',
						'default' => array()
					),
					'append' => array(
						'type' => 'array',
						'default' => array()
					),
					'items' => array(
						'type' => 'array',
						'default' => array()
					)
				),
				'group' => array(
					array(
						'name' => 'menubar',
						'options' => array(
							'include_root_node', 'menu_depth', 'current_only', 'delimiter', 'allow_menu_display', 'header', 'in_menubar', 'item_limit', 'split', 'orientation', 'class'
						)
					),
					array(
						'name' => 'fetch',
						'trigger' => 'root_node_id',
						'options' => array(
							'root_node_id', 'identifier_list', 'fetch_parameters', 'use_parent'
						)
					),
					array(
						'name' => 'static',
						'options' => array(
							'include', 'append', 'items'
						)
					)
				)
			)
		);
		foreach($Options as $Operator=>$Configuration){
			$Options[$Operator] = new OptionsScheme($Configuration);
		}
		return $Options;
	}

}


?>