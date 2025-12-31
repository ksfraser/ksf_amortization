<?php
namespace Ksfraser\Amortizations\Handlers;
class AjaxSelectPopulator {
	public function setFunctionName($name) {
		$this->functionName = $name;
		return $this;
	}
	public function setSourceFieldId($id) {
		$this->sourceFieldId = $id;
		return $this;
	}
}
