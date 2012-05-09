<?php

class Menubar extends PersistentObject
{

	const OBJECT_CACHE_KEY = 'MenubarObjectCache';

	public static $Settings;

	public $ID;
	public $Orientation;
	public $Header = false;

	protected $Count = 0;
	protected $Options;
	protected $ClassList = array();
	protected $RootNode;
	protected $Items = array();

	function __construct($id=false, $options=false){
		$this->ID = $id;
		$this->Orientation = $options['orientation'];
		$this->Options = $options;

		$this->Header = new MenubarItemObject($options['header']);

		// generate initial class list array
		$options['class'] = explode(' ', $options['class']);
		$options['class'][] = $this->Orientation;
		if($options['delimiter']){
			$options['class'][] = 'delimiter';
		}
		$this->addClass($options['class']);
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

	function applyDelimiters(){
		if($this->Options['delimiter']){
			if($Count = $this->Count){
				foreach($this->Items as $Key=>$Item){
					if($Key+1 < $Count){
						$this->Items[$Key]->Delimiter = $this->Options['delimiter'];
					}
				}
				return true;
			}
			// eZDebug::writeError(__METHOD__);
		}
		return false;
	}

	function getClassList($asString=true){
		return $asString ? implode(' ', $this->ClassList) : $this->ClassList;
	}

	function getItems(){
		return $this->Items;
	}

	function getRootNode(){
		return unserialize($this->RootNode);
	}

	function hasHeader(){
		return $this->Header ? $this->Header->hasContent() : false;
	}

	function processContentItems($options, $object=false){
		$hasRootNode = $hasReset = false;
		$DefaultFetchOptions = array(
			'Depth' => 1,
			'ClassFilterType' => 'include',
			'ClassFilterArray' => Menubar::$Settings['MenuContentSettings'][$options['identifier_list']]
		);
		eZDebug::accumulatorStart('process_object', 'menubar_total', "Process Object");
		if($object && is_object($object)){
			switch(get_class($object)){
				case 'eZContentObjectTreeNode':{
					$options['root_node_id'] = $object->NodeID;
					$hasRootNode = true;
					break;
				}
				case 'eZContentObjectAttribute':{
					$DataTypeClassList = self::$Settings['MenubarSettings']['DataTypeSettings']['ClassList'];
					$this->Items = call_user_func(array($DataTypeClassList[$object->DataTypeString], 'fetch'), $object);
					$this->Count = count($this->Items);
					break;
				}
			}
		}
		eZDebug::accumulatorStop('process_object');

		if(!$this->Count){
			eZDebug::accumulatorStart('handle_fetch', 'menubar_total', "Fetch Content Items");
			// configure fetch parameters to match the required array "key" format
			array_key_pascal_case($options['fetch_parameters']);
			$FetchOptions = array_merge($DefaultFetchOptions, $options['fetch_parameters']);

			// fetch root node for a content-based menubar if $object is not a content object tree node
			$RootNode = $hasRootNode ? $object : eZContentObjectTreeNode::fetch($options['root_node_id']);
			if($RootNode){
				// serailize and set object "RootNode" property for possible later use
				$this->RootNode = serialize($RootNode);

				// use the root node sort array if a sorting mechanism has not been provided
				if(!isset($FetchOptions['SortBy'])){
					$FetchOptions['SortBy'] = $RootNode->sortArray();
				}

				// fetch content object tree node objects
				$this->Items = eZContentObjectTreeNode::subTreeByNodeID($FetchOptions, $options['root_node_id']);
				$this->Count = count($this->Items);

				// reset menubar root node to current root node parent if "use_parent" options is set
				if(!$this->Count && $options['use_parent']){
					// setting the "$hasReset" variable prevents the header being processed multiple times
					$hasReset = (bool) $this->processContentItems($options, $RootNode->fetchParent());
				}

				if(!$hasReset){
					if($this->Header->Content){
						$Changes = $this->Header->processContentObjectTreeNode($RootNode);
						if($Changes['is_node_name'] && $this->Options['in_menubar'] && MenubarOperator::inMenubar($this->Options['in_menubar'], $RootNode)){
							$this->Header->addClass('in-menubar');
						}
					}
					// handle inclusion of the root node provided a root node exists
					if($IncludeRootNode = $this->Options['include_root_node']){
						// an object spliced into the array must be place into an array in order to function as intended
						// http://us3.php.net/manual/en/function.array-splice.php
						array_splice($this->Items, (($IncludeRootNode===true || $IncludeRootNode=='prepend') ? 0 : $this->Count), 0, array($RootNode));
						$this->Count++;
					}
				}
			}
			eZDebug::accumulatorStop('handle_fetch');
		}

		if($this->Count && !$hasReset){
			$hasDepth = $this->Options['current_only'] ? false : $this->Options['menu_depth'] > 1;
			foreach($this->Items as $Key=>$Item){
				if($this->ID){
					MenubarItem::cacheMenubarItem($this->ID, $Item);
				}
				$ItemOptions = false;
				if($hasDepth || ($this->Options['current_only'] && self::$Settings['CurrentNode'] && in_array($Item->NodeID, self::$Settings['CurrentNode']->pathArray()))){
					$ItemOptions = array_merge($options, array(
						'menu_depth' => $this->Options['menu_depth'] - 1,
						'use_parent' => false
					));
				}
				$this->Items[$Key] = new MenubarItem();
				$this->Items[$Key]->processContentObjectTreeNode($Item, $ItemOptions);
				if($this->Options['allow_menu_display']){
					$this->Items[$Key]->addClass('has-menu-display');
				}
			}
		}
		return $this->Count;
	}

	function processIncludes($items){
		foreach($items as $Item){
			$Placement = $this->Count;
			if(isset($Item['placement'])){
				$Placement = --$Item['placement'];
				unset($Item['placement']);
			}
			// an object spliced into the array must be place into an array in order to function as intended
			// http://us3.php.net/manual/en/function.array-splice.php
			array_splice($this->Items, $Placement, 0, array(new MenubarItem($Item)));
			$this->Count++;
		}
	}

	function setItems($items){
		if(is_array($items) && $Count = count($items)){
			foreach($items as $Key=>$Item){
				$this->Items[] = new MenubarItem($Item);
			}
			return $this->Count = $Count;
		}
		return false;
	}

	static function definition(){
		return array(
			'fields' => array(
				'id' => array(
					'name' => 'ID',
					'datatype' => 'string',
					'default' => '',
					'required' => true
				),
				'orientation' => array(
					'name' => 'Orientation',
					'datatype' => 'string',
					'default' => 'vertical',
					'required' => true
				),
				'item_count' => array(
					'name' => 'Count',
					'datatype' => 'integer',
					'default' => 0,
					'required' => true
				),
				'header' => array(
					'name' => 'Header',
					'datatype' => 'mixed',
					'default' => false,
					'required' => true
				)
			),
			'function_attributes' => array(
				'has_header' => 'hasHeader',
				'class' => 'getClassList',
				'items'=>'getItems',
				'root_node' => 'getRootNode'
			)
		);
	}

}

?>