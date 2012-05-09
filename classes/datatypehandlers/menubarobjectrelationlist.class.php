<?php

class MenubarObjectRelationList
{

	static function fetch($attribute){
		$Content = current($attribute->content());
		foreach($Content as $Key=>$Item){
			if(!$Item['in_trash']){
				$NodeList[] = eZContentObjectTreeNode::fetch($Item['node_id']);
			}
		}
		return $NodeList;
	}

}

?>