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
	
	/** @return int */
	public function inRoom($user = '') {
		if ($user == '') {
			return;
		}
		return $this->database->table('in_chatroom')
				->where('user_id', $user)
				->fetch()->chatroom_id;
	}
	
	/** @return int */
	public function enterRoom($who, $where) {
		$this->database->table('in_chatroom')
				->where('user_id', $who)
				->delete();
		return $this->database->table('in_chatroom')
				->insert(array(
					'user_id'		=>	$who,
					'chatroom_id'	=>	$where
				));
	}
	
	/** @return int */
	public function updateLastMsg($id) {
		return $this->database->table('in_chatroom')
				->where('user_id', $id)
				->update(array(
					'last_message' => 'now()'
				));
	}
	
	/** @return Nette\Database\Table\Selection */
	public function getRoomUsers($id = '') {
		if ($id == '') {
			return;
		}
		return $this->database->table('in_chatroom')
				->where('chatroom_id', $id)
				->order('last_message DESC');
	}
}