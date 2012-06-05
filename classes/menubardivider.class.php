<?php

class MenubarDivider extends PersistentObject
{

	protected $Content = '';
	protected $hasContent = false;

	function __construct($content=''){
		if($content){
			$this->hasContent = (bool) $this->Content = $content;
		}
	}

	function getContent(){
		return $this->hasContent ? $this->Content : '';
	}

	function setContent($content){
		$this->hasContent = (bool) $this->Content = $content;
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
				'has_content' => array(
					'name' => 'hasContent',
					'datatype' => 'boolean',
					'default' => false,
					'required' => false
				)
			)
		);
	}

}

?>