<?php

class eZRootHandler
{

	static function process($object){
		$Link = $object->urlAlias();
		if(preg_match("#^[a-zA-Z0-9]+:#", $Link) || substr($Link, 0, 2)=='//'){
			return $Link;
		}
		if(strlen($Link)>0 && $Link[0]!='/'){
			$Link = "/$Link";
		}
		eZURI::transformURI($Link, true, 'relative');
		return $Link;
	}

}

?>