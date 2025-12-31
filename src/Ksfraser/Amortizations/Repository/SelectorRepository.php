<?php
namespace Ksfraser\Amortizations\Repository;
class SelectorRepository {
	private $tableName = 'selector_options';
	private $data = [];

	public function add($option) {
		$this->data[] = $option;
		return true;
	}

	public function getTableName() {
		return $this->tableName;
	}

	public function getById($id) {
		foreach ($this->data as $option) {
			if (isset($option['id']) && $option['id'] == $id) {
				return $option;
			}
		}
		return null;
	}

	public function getAll() {
		return $this->data;
	}
}
