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
			'menubar'=>array(
				'parameters'=>array('type'=>'mixed', 'required'=>true, 'default'=>false)
			)
		);
	}

	function modify(&$tpl, &$operatorName, &$operatorParameters, &$rootNamespace, &$currentNamespace, &$operatorValue, &$namedParameters, &$placement){
		switch($operatorName){
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
		$result=$tpl->fetch('design:menu/menubar.tpl');
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

	static function menubar(&$tpl, &$operatorValue, $parameters){
		eZDebug::accumulatorStart('menubar_total');
		if(is_object($parameters) && get_class($parameters)=='Menubar'){
			$operatorValue = self::displayMenubar($tpl, $parameters);
			eZDebug::accumulatorStop('menubar_total');
			return true;
		}
		if($Menubar = Menubar::initialize($parameters)){
			$operatorValue = self::displayMenubar($tpl, $Menubar);
			eZDebug::accumulatorStop('menubar_total');
			return true;
		}
		eZDebug::accumulatorStop('menubar_total');
		return false;
	}

	static function operatorDefaults($operatorName=false){
		$Defaults = array(
			'menubar'=>array(
				'root_node_id'=>eZINI::instance('content.ini')->variable('NodeSettings','RootNode'),
				'orientation'=>'vertical',
				'class'=>false,
				'include_root_node'=>false,
				'menu_depth'=>1,
				'show_header'=>false,
				'identifier_list'=>'LeftIdentifierList',
				'fetch_parameters'=>false,
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