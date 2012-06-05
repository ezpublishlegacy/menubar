<?php

class MenubarSplit
{

	protected $Options;
	protected $Division = false;
	protected $Headers = false;

	function __construct($options){
		if($options){
			$Options = $this->Options = OptionsHandler::create('MenubarSplit');
			if($this->Options->process($options)){
				$isNamePriority = $this->Options->is('type', array('name', 'priority'));

				// convert "division" option into an array for "name" and "priority" options if needed
				if($isNamePriority && $this->Options->has('division') && !$this->Options->is(true, 'division', 'array')){
					$Division = preg_split('/\s*,\s*/', $this->Options->get('division'));
					// set "division" options values to be integers for "priority" option
					if($this->Options->is('type', 'priority')){
						foreach($Division as $Key=>$Priority){
							$Division[$Key] = (int) $Priority;
						}
					}
					$this->Options->set('division',  $Division);
				}
				$this->Division = $this->Options->get('division');

				// convert "headers" option into an array
				if($this->Options->has('headers')){
					if(!$this->Options->is(true, 'headers', 'array')){
						$this->Options->set('headers',  preg_split('/\s*,\s*/', $this->Options->get('headers')));
					}
					if($isNamePriority && (count($this->Options->get('headers')) > count($this->Division))){
						array_unshift($this->Division, $this->Options->is('type', 'name') ? 'a' : 0);
					}
				}
				$this->Headers = $this->Options->get('headers');
			}
		}
	}

	function checkSplitPoint(eZContentObjectTreeNode $object, $index){
		if($Division = $this->Division ? (is_array($this->Division) ? current($this->Division) : $this->Division) : false){
			$Divider = new MenubarDivider();
			$isSplitPoint = false;
			switch($this->Options->get('type')){
				case 'limit':{
					$isSplitPoint = $index && ($index % $Division)==0;
					break;
				}
				case 'name':{
					$Name = $object->getName();
					if($Name[0]>=strtolower($Division) || $Name[0]>=strtoupper($Division)){
						$isSplitPoint = true;
						if($this->Headers && $Header=current($this->Headers)){
							$Divider->setContent($Header);
							next($this->Headers);
						}
						next($this->Division);
						break;
					}
					break;
				}
				case 'priority':{
					if($object->Priority >= $Division){
						$isSplitPoint = true;
						if($this->Headers && $Header=current($this->Headers)){
							$Divider->setContent($Header);
							next($this->Headers);
						}
						next($this->Division);
						break;
					}
					break;
				}
				default:{
					if($isSplitPoint = $item->ClassIdentifier == 'menubar_divider'){
						$Divider->setContent($object->getName());
					}
				}
			}
			if($isSplitPoint){
				return $Divider;
			}
		}
		return false;
	}

	function getOption($name){
		if($this->Options->has($name)){
			return $this->Options->get($name);
		}
		return false;
	}

	function hasDivisions(){
		return (bool) $this->Division;
	}

	function isType($type){
		return $this->Options->get('type') == $type;
	}

	static function Options(){
		$Configuration = array(
			'associative' => false,
			'convert' => function(&$list, $options, $count){
				if(is_bool($options[$count-1])){
					$list[$count-1] = 'columnize';
				}
			},
			'scheme' => array(
				'type' => array(
					'type' => 'string',
					'default' => 'content',
					'values' => array('content', 'limit', 'name', 'priority')
				),
				'division' => array(
					'type' => 'array | integer | string',
					'default' => null
				),
				'headers' => array(
					'type' => 'array | string',
					'default' => null
				),
				'columnize' => array(
					'type' => 'boolean',
					'default' => false
				)
			)
		);
		return new OptionsScheme($Configuration);
	}

}

?>