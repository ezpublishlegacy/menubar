<?php

class MenubarObjectRelationList
{

	static function fetch($attribute){
		$Content = current($attribute->content());
		foreach($Content as $Key=>$Item){
			if(!$Item['in_trash']){
			    $myNode = eZContentObjectTreeNode::fetch($Item['node_id']);
				if (is_object($myNode)) $NodeList[] = eZContentObjectTreeNode::fetch($Item['node_id']);
			}
		}
		return $NodeList;
	}

}

?>