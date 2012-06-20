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
	protected $Columnize = false;
	protected $ClassList = array();
	protected $RootNode;
	protected $Items = array();
	protected $MenubarSplit = false;
	protected $SplitPoints = array();

	function __construct($id=false, $options=false){
		$this->ID = $id;
		if(self::hasOptions($options)){
			if(is_array($options)){
				$options = OptionsHandler::instance($options);
			}
			$Options = $this->Options = $options;

			$this->Orientation = $Options->get('orientation');
			$this->Header = new MenubarItemObject($Options->get('header'));

			// generate initial class list array
			$Class = $Options->has('class') ? explode(' ', $Options->get('class')) : array();
			$Class[] = $this->Orientation;
			if($Options->has('delimiter')){
				$Class[] = 'delimiter';
			}
			$this->addClass($Class);

			if($Options->has('split') && $Split = $Options->get('split')){
				if(is_bool($Split)){
					$Split = array('type' => 'content');
				}
				$this->MenubarSplit = new MenubarSplit($Split);
				$this->Columnize = $this->MenubarSplit->getOption('columnize');
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

	function applyDelimiters(){
		if($this->Options->has('delimiter') && $Delimiter=$this->Options->get('delimiter')){
			if($Count = $this->Count-1){
				foreach($this->Items as $Key=>$Item){
					if($Key < $Count){
						$this->Items[$Key]->Delimiter = $Delimiter;
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

	function hasHeader(){
		return $this->Header ? $this->Header->hasContent() : false;
	}

	function isMultiple(){
		return $this->MenubarSplit && $this->MenubarSplit->hasDivisions();
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
				$hasRootNode = true;
				$NodeSortArray = current($RootNode->sortArray());

				// serailize and set object "RootNode" property for possible later use
				$this->RootNode = serialize($RootNode);

				// use the root node sort array if a sorting mechanism has not been provided
				$isNameSplit = ($this->MenubarSplit && $this->MenubarSplit->isType('name'));
				if(!isset($FetchOptions['SortBy']) || $isNameSplit){
					$FetchOptions['SortBy'] = $isNameSplit ? array('name', true) : $NodeSortArray;
				}

				// fetch content object tree node objects
				$this->Items = eZContentObjectTreeNode::subTreeByNodeID($FetchOptions, $options['root_node_id']);
				$this->Count = count($this->Items);
				// reset menubar root node to current root node parent if "use_parent" options is set and current root node is not a top level node
				if(!$this->Count && $options['use_parent'] && !in_array($options['root_node_id'], self::$Settings['TopLevelNodes'])){
					// setting the "$hasReset" variable prevents the header being processed multiple times
					$hasReset = (bool) $this->processContentItems($options, $RootNode->fetchParent());
				}

				if(!$hasReset){
					if($this->Header->hasContent()){
						$Changes = $this->Header->processContentObjectTreeNode($RootNode);
						if($Changes['is_node_name'] && $this->Options->get('in_menubar')){
							// add class "in-menubar" to menubar header if the root node is in the top level items of the specified menubar
							if(MenubarOperator::inMenubar($this->Options->get('in_menubar'), $RootNode)){
								$this->Header->addClass('in-menubar');
							}
						}
					}
					// handle inclusion of the root node provided a root node exists
					if($IncludeRootNode = $this->Options->get('include_root_node')){
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
			$isCurrentOnly = $this->Options->get('current_only');
			$MenuDepth = $this->Options->get('menu_depth');
			$hasDepth = $isCurrentOnly ? false : ($MenuDepth > 1);
			$isMultiple = $this->isMultiple();
			foreach($this->Items as $Key=>$Item){
				if($isMultiple && $SplitPoint=$this->MenubarSplit->checkSplitPoint($Item, $Key)){
					$this->SplitPoints[$Key] = $SplitPoint;
				}
				if($this->ID){
					MenubarItem::cacheMenubarItem($this->ID, $Item);
				}
				$ItemOptions = false;
				if($hasDepth || ($isCurrentOnly && self::$Settings['CurrentNode'] && in_array($Item->NodeID, self::$Settings['CurrentNode']->pathArray()))){
					$ItemOptions = array_merge($options, array(
						'menu_depth' => $MenuDepth - 1,
						'use_parent' => false
					));
					if($ItemOptions['fetch_parameters'] && $ItemOptions['fetch_parameters']['AttributeFilter']){
						if(current(current($options['fetch_parameters']['AttributeFilter'])) == 'priority'){
							unset($ItemOptions['fetch_parameters']['AttributeFilter']);
							if(current(current($Item->sortArray())) == 'priority'){
								$ItemOptions['fetch_parameters']['AttributeFilter'] = array(array('priority', 'between', array(1, 500)));
							}
						}
					}
				}
				$this->Items[$Key] = new MenubarItem();
				$this->Items[$Key]->processContentObjectTreeNode($Item, $ItemOptions);
				if($this->Options->get('allow_menu_display')){
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
				'header' => array(
					'name' => 'Header',
					'datatype' => 'mixed',
					'default' => false,
					'required' => true
				),
				'orientation' => array(
					'name' => 'Orientation',
					'datatype' => 'string',
					'default' => 'vertical',
					'required' => true
				),
				'columnize' => array(
					'name' => 'Columnize',
					'datatype' => 'Boolean',
					'default' => false,
					'required' => false
				),
				'items' => array(
					'name' => 'Items',
					'datatype' => 'array',
					'default' => array(),
					'required' => true
				),
				'item_count' => array(
					'name' => 'Count',
					'datatype' => 'integer',
					'default' => 0,
					'required' => true
				),
				'split_points' => array(
					'name' => 'SplitPoints',
					'datatype' => 'array',
					'default' => array(),
					'required' => true
				)
			),
			'function_attributes' => array(
				'has_header' => 'hasHeader',
				'is_multiple' => 'isMultiple',
				'class' => 'getClassList'
			)
		);
	}

	protected static function hasOptions($options){
		return $options && ((is_object($options) && get_class($options)=='OptionsHandler') || is_array($options));
	}

}

?>