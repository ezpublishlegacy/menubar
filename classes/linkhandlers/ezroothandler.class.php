<?php

class eZRootHandler
{

	static function process($menuitem){
		$OperatorValue = $menuitem->getNode()->urlAlias();
		if(preg_match("#^[a-zA-Z0-9]+:#", $OperatorValue) || substr($OperatorValue, 0, 2)=='//'){
			return $OperatorValue;
		}
		if(strlen( $OperatorValue)>0 && $OperatorValue[0]!='/'){
			$OperatorValue = "/$OperatorValue";
		}
		eZURI::transformURI($OperatorValue, true, 'relative');
		return $OperatorValue;
	}

}

?>