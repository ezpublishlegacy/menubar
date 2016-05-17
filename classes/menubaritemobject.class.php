<?php

class MenubarItemObject extends PersistentObject
{

	const CLASS_NAME = 'MenubarItemObject';

	public $Link = '';
	public $isExternal = false;
	public $Target = '';

	protected $Content = '';
	protected $ClassList = array();
	protected $Options;

	function __construct($options=false){
		if($options){
			$Options = $this->Options = OptionsHandler::create('MenubarItemObject');
			if($Options->process($options)){
				$Options->translateTo($this, array(
					'content' => 'setContent',
					'link' => 'Link',
					'is_external' => 'isExternal',
					'target' => 'Target',
					'class' => 'addClass'
				));
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
		$ID = $object->remoteID();
		if(!isset($GLOBALS['MenubarItemObjectCache'][$ID])){
			eZDebug::accumulatorStart('process_treenode', 'menubar_total', "Process Content Object Tree Node");
			$Result = array('content' => false, 'is_node_name' => false, 'link' => false, 'is_external' => false, 'target' => false);
			$Data = array('Content' => false, 'Link' => false, 'isExternal' => false, 'Target' => false, 'Class' => "node-id-$object->NodeID");

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
						$Data['Content'] = $Attribute->DataText;
						$Result['content'] = true;
						break;
					}
				}
				////////////////////////////////////////////////////////////////////////////////////////////////////////////////

				// poor handling: use an ini setting to determine the attribute to use
				////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				if(isset($DataMap['is_external']) && $DataMap['is_external']->DataInt){
					$Data['isExternal'] = $Result['is_external'] = true;
				}
				if(isset($DataMap['open_in_new_window']) && $DataMap['open_in_new_window']->DataInt){
					$Data['Target'] = $Result['target'] = '_blank';
				}
				////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			}
			eZDebug::accumulatorStop('attribute_handler');
			////////////////////////////////////////////////////////////////////////////////////////////////////////////////

			if($this->Link !== false){
				$LinkHandlerClass = Menubar::$Settings['MenubarSettings']['MenubarItem']['LinkHandlerList'][Menubar::$Settings['MenubarSettings']['MenubarItem']['LinkEngine']];
				eZDebug::accumulatorStart('process_link', 'menubar_total', " - Process Menubar Item Link [$LinkHandlerClass]");
				////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				if($Data['Link'] = call_user_func(array($LinkHandlerClass, 'process'), $object)){
					if(is_array($Data['Link'])){
						// prevent "Content" from being overwritten if "Content" is a "true" string
						if(isset($Data['Link']['content']) && $Data['Link']['content']){
							$Data['Content'] = $Data['Link']['content'];
							$Result['content'] = true;
						}
						$Data['Link'] = $Data['Link']['link'];
					}
					$Result['link'] = true;
				}
				////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				eZDebug::accumulatorStop('process_link');
			}

			// check if "Content" has not already been set to a "true" string
			if(!is_string($this->Content) || !$this->Content){
				$content = $object->getName();
				$DataMap = $object->attribute( 'data_map' );
				if( $object->attribute( 'class_identifier' ) === 'faq_list' && isset( $DataMap['short_name'] ) ) {
					$shortName = $DataMap['short_name']->attribute( 'content' );
					if( empty( $shortName ) === false ) {
						$content = $shortName;
					}
				}
				$Data['Content'] = $content;
				$Result['content'] = $Result['is_node_name'] = true;
			}

			// cache "Data" and "Result" to prevent duplicate processing of the same content object tree node
			$GLOBALS['MenubarItemObjectCache'][$ID] = compact('Data', 'Result');
			eZDebug::accumulatorStop('process_treenode');
		}

		eZDebug::accumulatorStart('set_treenode', 'menubar_total', "Set Content Object Tree Node Data");
		extract($GLOBALS['MenubarItemObjectCache'][$ID]);
		$this->addClass($Data['Class']);
		if($Result['content']){
			$this->setContent($Data['Content']);
		}
		if($Result['link']){
			$this->Link = $Data['Link'];
		}
		if($Result['is_external']){
			$this->isExternal = $Data['isExternal'];
		}
		if($Result['target']){
			$this->Target = $Data['Target'];
		}
		eZDebug::accumulatorStop('set_treenode');

		return $Result;
	}

	function setContent($content){
		if(($isString=is_string($content)) || is_bool($content)){
			$this->Content = $isString ? htmlentities($content, ENT_COMPAT | 'ENT_HTML5' | 'ENT_HTML401', "UTF-8") : $content;
			return true;
		}
		return false;
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
				),
				'target'=>array(
					'name'=>'Target',
					'datatype'=>'string',
					'default'=>'',
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
		$Configuration = array(
			'scheme' => array(
				'content' => array(
					'type' => 'boolean | string',
					'default' => true
				),
				'link' => array(
					'type' => 'string',
					'default' => ''
				),
				'is_external' => array(
					'type' => 'boolean',
					'default' => false
				),
				'target' => array(
					'type' => 'string',
					'default' => ''
				),
				'class' => array(
					'type' => 'array | string',
					'default' => ''
				)
			),
			'default' => 'content'
		);
		return new OptionsScheme($Configuration);
	}

}

?>