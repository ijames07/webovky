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
	
	/** @return Nette\Database\Table\ActiveRow */
	public function inRoom($user = '', $room = '') {
		if ($user == '' || $room == '') {
			return;
		}
		return $this->database->table('in_chatroom')
				->where('user_id', $user)
				->where('chatroom_id', $room)
				->fetch();
	}
	
	/** @return Nette\Database\Table\ActiveRow */
	public function getInRoom($id = '', $room = '') {
		if ($id == '' || $room == '') {
			return;
		}
		return $this->database->table('in_chatroom')
				->where('user_id', $id)
				->where('chatroom_id', $room)
				->fetch();
	}
	
	/** @return int */
	public function enterRoom($who, $where) {
		return $this->database->table('in_chatroom')
				->insert(array(
					'user_id'		=>	$who,
					'chatroom_id'	=>	$where
				));
	}
	
	/** @return int */
	public function updateRoomLastMsg($id, $room, $time = 'now()') {
		return $this->database->table('in_chatroom')
				->where('user_id', $id)
				->where('chatroom_id', $room)
				->update(array(
					'last_message' => $time
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