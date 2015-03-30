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
	
	/** @return Nette\Database\Table\Selection */
	public function inRooms($user = '') {
		if ($user == '') {
			return;
		}
		return $this->database->table('in_chatroom')
				->where('user_id', $user)
				->order('entered ASC');
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
	public function leaveRoom($who, $where) {
		return $this->database->table('in_chatroom')
				->where('user_id', $who)
				->where ('chatroom_id', $where)
				->delete();
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
				->order('user.name ASC');
	}
	
	/** @return boolean */
	public function isModerator($user = '', $room = '') {
		if ($user == '' || $room == '') {
			return;
		}
		return $this->database->table('room')
				->where('owner_user_id', $user)
				->where('id', $room)
				->count();
	}
	
	/** @return Nette\Database\Table\ActiveRow */
	public function getCandidate($chatroom = '') {
		if ($chatroom == '') {
			return;
		}
		return $this->database->table('in_chatroom')
				->where('chatroom_id', $chatroom)
				->order('entered ASC')
				->limit(1)
				->fetch();
	}
	
	/** @return int Number of updated chatrooms */
	public function setModerator($user = '', $room = '') {
		if ($user == '' || $room == '') {
			return;
		}
		return $this->database->table('room')
				->where('id', $room)
				->update(array(
					'owner_user_id' => $user
				));
	}
	
	/** @return int Number of kicked users */
	public function kick($user, $room) {
		if (is_null($user) || is_null($room)) {
			return;
		}
		return $this->database->table('in_chatroom')
				->where('user_id', $user)
				->where('chatroom_id', $room)
				->delete();
	}
	
	/** @return  int Number of deleted chatrooms */
	public function deleteRoom($room) {
		return $this->database->table('room')
				->where('id', $room)
				->delete();
	}
	
	/** @return Nette\Database\Table\ActiveRow */
	public function createRoom($data) {
		if (isset($data["locked"])  && $data["locked"] == TRUE && isset($data["password"])) {
			return $this->database->table('room')->insert(array(
				'title'	=> $data["title"],
				'owner_user_id'	=> $data["user_id"],
				'lock'	=> 't',
				'description'	=> $data["description"],
				'password'	=> $data["password"]
			));
		} else {
			return $this->database->table('room')->insert(array(
				'title'	=> $data["title"],
				'owner_user_id'	=> $data["user_id"],
				'description'	=> $data["description"]
			));
		}
	}
	
	/** @return int */
	public function updateRoom($data) {
		if (isset($data["locked"])  && $data["locked"] == TRUE && isset($data["password"])) {
			return $this->database->table('room')
					->where('id', $data["chatroom_id"])
					->update(array(
						'title'			=> $data["title"],
						'lock'			=> 't',
						'description'	=> $data["description"] ,
						'password'		=> $data["password"]
					));
		} else {
			return $this->database->table('room')
					->where('id', $data["chatroom_id"])
					->update(array(
						'title'			=> $data["title"],
						'description'	=> $data["description"],
						'lock'			=> 'f',
						'password'		=> null
					));
		}
	}
}