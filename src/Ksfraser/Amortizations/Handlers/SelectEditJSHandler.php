<?php
namespace Ksfraser\Amortizations\Handlers;
class SelectEditJSHandler {
	public function getHtml() {
		return '<div>SelectEditJSHandler HTML</div>';
	}
	public function setFunctionName($name) {
		$this->functionName = $name;
		return $this;
	}
	public function setSourceFieldId($id) {
		$this->sourceFieldId = $id;
		return $this;
	}
}
