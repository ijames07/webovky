<?php

namespace App\Model;
use Nette;



class Rooms extends Nette\Object {
	/** @var Nette\Database\Inject */
	private $database;
	
	public function __construct(Nette\Database\Context $database) {
		$this->database = $database;
	}
	
	/** @return Nette\Database\Table\Selection */
	public function getAll() {
		return $this->database->table("room");
	}
	
	/** @return Nette\Database\Table\ActiveRow */
	public function get($id = '') {
		return $this->database->table('room')->get($id);
	}
}