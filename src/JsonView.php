<?php

/**
 * Description of JsonView
 *
 * @author ibanez
 */
class JsonView {

	public $ObjectRef;
	private $blocks = [];

	public function loadTemplate($template) {
		unset($template);
	}

	public function MergeBlock($blockName, $value) {
		$this->blocks[$blockName] = $value;
	}

	public function PlugIn($param1, $rowsLimit, $page, $total = '', $param2 = '', $param3 = '') {
		unset($param1);
		unset($rowsLimit);
		unset($page);
		unset($total);
		unset($param2);
		unset($param3);
	}

	public function Show() {
		$rest = Restful::create();
		$rest->printREST($this->blocks);
	}

}
