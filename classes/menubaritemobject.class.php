<?php

class MenubarItemObject extends PersistentObject
{

	public $Content = '';
	public $Link = '';
	public $isExternal = false;

	protected $ClassList = array();
	protected $Options;

	function __construct($options=false){
		if($options && $this->Options = self::processOptions($options)){
			if(is_string($this->Options['content'])){
				$this->Options['content'] = htmlentities($this->Options['content'], ENT_COMPAT | 'ENT_HTML5' | 'ENT_HTML401', "UTF-8");
			}
			$this->Content = $this->Options['content'];
			$this->Link = $this->Options['link'];
			$this->isExternal = $this->Options['is_external'];
			if($this->Options['class']){
				$this->addClass($this->Options['class']);
			}
		}
	}

	function addClass($class){
		if($class){
			if(is_array($class)){
				return $this->ClassList = array_merge($this->ClassList, $class);
			}
			return $this->addClass(explode(' ', $class));
		}
		return false;
	}

	function getClassList($asString=true){
		return $asString ? implode(' ', $this->ClassList) : $this->ClassList;
	}

	function hasContent(){
		return (bool) $this->Content;
	}

	function processContentObjectTreeNode(eZContentObjectTreeNode $object){
		eZDebug::accumulatorStart('process_treenode', 'menubar_total', "Process Content Object Tree Node");
		$Result = array('content' => false, 'is_node_name' => false, 'link' => false, 'is_external' => false);

		// "allow custom PHP class to process each type of class {enhancement}" here
		// have the PHP class method return a value for content instead of overwritting
		////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		eZDebug::accumulatorStart('attribute_handler', 'menubar_total', " - Handle Content Object Attributes");
		if($object->ClassIdentifier=='link'){
			$DataMap = $object->dataMap();

			// poor handling: use an ini setting to determine the attribute to use
			////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			foreach($DataMap as $Identifier=>$Attribute){
				if($Attribute->attribute('data_type_string')=='ezurl' && !empty($Attribude->DataText)){
					$this->Content = $Attribute->DataText;
					$Result['content'] = true;
					break;
				}
			}
			////////////////////////////////////////////////////////////////////////////////////////////////////////////////

			// poor handling: use an ini setting to determine the attribute to use
			////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			if(isset($DataMap['open_in_new_window']) && $DataMap['open_in_new_window']->DataInt){
				$this->isExternal = $Result['is_external'] = true;
			}
			////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		}
		eZDebug::accumulatorStop('attribute_handler');
		////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		if($this->Link!==false){
			$LinkHandlerClass = Menubar::$Settings['MenubarSettings']['MenubarItem']['LinkHandlerList'][Menubar::$Settings['MenubarSettings']['MenubarItem']['LinkEngine']];
			eZDebug::accumulatorStart('process_link', 'menubar_total', " - Process Menubar Item Link [$LinkHandlerClass]");
			// directly from MenubarItem::generateItemLink()
			////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			if($this->Link = call_user_func(array($LinkHandlerClass, 'process'), $object)){
				if(is_array($this->Link)){
					// prevent "Content" from being overwritten if "Content" is a "true" string
					if(isset($this->Link['content']) && $this->Link['content']){
						$this->Content = $this->Link['content'];
						$Result['content'] = true;
					}
					$this->Link = $this->Link['link'];
				}
				$Result['link'] = true;
			}
			////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			eZDebug::accumulatorStop('process_link');
		}

		$this->addClass("node-id-$object->NodeID");
		// check if "Content" has not already been set to a "true" string
		if(!is_string($this->Content) || !$this->Content){
			$this->Content = $object->getName();
			$Result['content'] = $Result['is_node_name'] = true;
		}

		if($Result['content']){
			$this->Content = htmlentities($this->Content, ENT_COMPAT | 'ENT_HTML5' | 'ENT_HTML401', "UTF-8");
		}

		eZDebug::accumulatorStop('process_treenode');
		return $Result;
	}

	static function definition(){
		return array(
			'fields' => array(
				'content' => array(
					'name' => 'Content',
					'datatype' => 'string',
					'default' => '',
					'required' => true
				),
				'link' => array(
					'name' => 'Link',
					'datatype' => 'string',
					'default' => '',
					'required' => true
				),
				'is_external'=>array(
					'name'=>'isExternal',
					'datatype'=>'boolean',
					'default'=>false,
					'required'=>true
				)
			),
			'function_attributes' => array(
				'class' => 'getClassList',
				'has_content' => 'hasContent'
			)
		);
	}

	static function Options(){
		return array(
			'content' => '',
			'link' => '',
			'is_external' => false,
			'class' => ''
		);
	}

	static function processOptions($options){
		if(is_bool($options) || is_string($options)){
			$options = array('content' => $options);
		}
		if(is_array($options)){
			if(!isset($options['content'])){
				$options['content'] = true;
			}
			return array_merge(self::Options(), $options);
		}
		return false;
	}

}

?>