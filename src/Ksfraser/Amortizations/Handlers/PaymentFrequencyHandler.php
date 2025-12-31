<?php
namespace Ksfraser\Amortizations\Handlers;
class PaymentFrequencyHandler {
	public function getHtml() {
		return '<div>PaymentFrequencyHandler HTML</div>';
	}
	public function addFrequency($freq) {
		$this->frequencies[] = $freq;
		return $this;
	}
	public function setSourceFieldId($id) {
		$this->sourceFieldId = $id;
		return $this;
	}
}
