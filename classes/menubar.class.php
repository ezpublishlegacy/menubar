<?php

class Menubar extends PersistentObject
{
	static $ClassName='menu';

	public $Orientation;
	public $hasItems;

	protected $Class;
	protected $Items;
	protected $ContentParameters;

	private $RootNode;

	function __construct($items=false, $orientation=false, $class=false){
		// set object Orientation property
		$this->Orientation = $orientation;
		if(!$this->Orientation){
			$this->Orientation = MenubarOperator::getOperatorDefaultParameter('menubar','orientation');
		}
		// set object hasItems and Items properties as needed
		$this->hasItems = false;
		if($items){
			$this->Items = $items;
			$this->hasItems = true;
		}
		// set user defined class list
		if($class){
			$this->Class = $class;
		}
	}

	function compileClassList(){
		$ClassList = self::$ClassName.' '.$this->Orientation;
		if(isset($this->Delimiter) && $this->Delimiter){
			$ClassList .= ' delimiter';
		}
		if($this->Class){
			$ClassList .= ' '.$this->Class;
		}
		return $ClassList;
	}

	function fetchParent(){
		return $this->RootNode->fetchParent();
	}

	function fetchParentNodeID(){
		return $this->RootNode->ParentNodeID;
	}

	function getItems(){
		if($this->hasItems){
			return $this->Items;
		}
		return false;
	}

	function getItemCount(){
		return is_array($this->Items) ? count($this->Items) : 0;
	}

	protected function compileContentParameters($parameters){
		eZDebug::accumulatorStart('content_parameters', 'menubar_total', 'Compile Content Parameters');
		if($parameters['root_node_id']){
			$FetchParameters = array(
				'ClassFilterType'=>'include'
			);
			$ItemParameters['MenuDepth'] = $parameters['menu_depth'];
			$ItemParameters['RootNodeID'] = $parameters['root_node_id'];
			$ItemParameters['Delimiter'] = $parameters['delimiter'];
			$ItemParameters['useMenuDisplay'] = $parameters['use_menu_display'];
			if($this->RootNode = eZContentObjectTreeNode::fetch($parameters['root_node_id'])){
				$FetchParameters['ClassFilterArray'] = eZINI::instance('menu.ini')->variable('MenuContentSettings', $parameters['identifier_list']);
				$FetchParameters['SortBy'] = $this->RootNode->sortArray();
				if($parameters['fetch_parameters']){
					$FetchParameters = array_merge($FetchParameters, $parameters['fetch_parameters']);
				}
				$this->ContentParameters = (object)array(
					'ItemParameters'=>$ItemParameters,
					'FetchParameters'=>array_merge($FetchParameters, array('Depth'=>1))
				);
			eZDebug::accumulatorStart('content_parameters');
				return true;
			}
		}
		eZDebug::accumulatorStart('content_parameters');
		return false;
	}

	static function definition(){
		return array(
			'fields'=>array(
				'orientation'=>array(
					'name'=>'Orientation',
					'datatype'=>'string',
					'default'=>'vertical',
					'required'=>true
				),
				'has_items'=>array(
					'name'=>'hasItems',
					'datatype'=>'boolean',
					'default'=>false,
					'required'=>true
				)
			),
			'function_attributes'=>array(
				'class'=>'compileClassList',
				'items'=>'getItems',
				'item_count'=>'getItemCount'
			)
		);
	}

	static function initialize($parameters, $serialize=false){
		$parameters = array_merge(MenubarOperator::operatorDefaults('menubar'), $parameters);
		$Menubar = new self($parameters['items'], $parameters['orientation'], $parameters['class']);
		$Menubar->Delimiter = $parameters['delimiter'];
		if($Menubar->hasItems){
			$ItemCount = $Menubar->getItemCount()-1;
			foreach($Menubar->Items as $Key=>$Item){
				$Menubar->Items[$Key] = MenubarItem::createFromParameters($Item);
				if($Key < $ItemCount){
					$Menubar->Items[$Key]->Delimiter = $Menubar->Delimiter;
				}
			}
			return $Menubar;
		}
		if($Menubar->compileContentParameters($parameters)){
			$Menubar->Items = self::generateMenubarTree($Menubar->ContentParameters, $serialize);
			if($parameters['include_root_node']){
				array_unshift($Menubar->Items, MenubarItem::createFromContentObjectTreeNode($Menubar->RootNode, true));
			}
			self::processMenuIncludes($Menubar, $parameters);
			if($Menubar->getItemCount()){
				$Menubar->hasItems=true;
			}
			if(!$Menubar->hasItems && $parameters['use_parent']){
				$Menubar = self::initialize(array_merge($parameters, array(
					'root_node_id'=>$Menubar->fetchParentNodeID(),
					'use_parent'=>false,
				)));
			}
			return $Menubar;
		}
		return false;
	}

	protected static function generateMenubarTree($parameters, $serialize=false, $current_depth=1){
		eZDebug::accumulatorStart('generate_tree', 'menubar_total', 'Generate Menubar Tree');
		if($NodeList = eZContentObjectTreeNode::subTreeByNodeID($parameters->FetchParameters, $parameters->ItemParameters['RootNodeID'])){
			$NodeListCount = count($NodeList)-1;
			foreach($NodeList as $Key=>$Node){
				$NodeList[$Key] = MenubarItem::createFromContentObjectTreeNode($Node);
				if($Key < $NodeListCount){
					$NodeList[$Key]->Delimiter = $parameters->ItemParameters['Delimiter'];
				}
				if($Node->childrenCount() && $parameters->ItemParameters['MenuDepth'] > $current_depth){
					if($parameters->ItemParameters['useMenuDisplay']){
						MenubarItem::fetchMenuDisplay($NodeList[$Key]);
						continue;
					}
					$parameters->ItemParameters['RootNodeID'] = $Node->NodeID;
					$NodeList[$Key]->setMenu(new self(self::generateMenubarTree($parameters, $serialize, $current_depth+1)));
				}
			}
			eZDebug::accumulatorStop('generate_tree');
			return $NodeList;
		}
		eZDebug::accumulatorStop('generate_tree');
		return array();
	}

	protected static function processMenuIncludes($object, $parameters){
		$ItemCount = $object->getItemCount();
		foreach($parameters['append'] as $Key=>$Item){
			$Item['placement'] = $ItemCount+$Key;
			$parameters['include'][] = $Item;
		}
		if($parameters['include']){
			$Insertions = 0;
			foreach($parameters['include'] as $Item){
				$MenuItem = MenubarItem::createFromParameters($Item);
				if(isset($Item['placement'])){
					array_splice($object->Items, $Item['placement']+$Insertions, 0, array($MenuItem));
					$Insertions++;
				}
			}
		}
		return false;
	}

}

?>