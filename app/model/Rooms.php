<?php

namespace App\Model;
use Nette;



class Rooms extends Nette\Object {
	/** @var Nette\Database\Inject */
	private $database;
	
	const TABLE_ROOM = 'room',
			TABLE_INCHATROOM = 'in_chatroom',
			COLUMN_USER = 'user_id',
			COLUMN_ENTERED = 'entered',
			COLUMN_LAST_MSG = 'last_message',
			COLUMN_CHATROOM_ID = 'chatroom_id',
			COLUMN_OWNER = 'owner_user_id',
			COLUMN_PASSWORD = 'password',
			COLUMN_TITLE = 'title',
			COLUMN_LOCK = 'lock',
			COLUMN_DESCRIPTION = 'description',
			COLUMN_ID = 'id';
	
	public function __construct(Nette\Database\Context $database) {
		$this->database = $database;
	}
	
	/** @return Nette\Database\Table\Selection */
	public function getAll() {
		return $this->database->table(self::TABLE_ROOM);
	}
	
	/** @return Nette\Database\Table\ActiveRow */
	public function get($id = '') {
		return $this->database->table(self::TABLE_ROOM)->get($id);
	}
	
	/** @return Nette\Database\Table\Selection */
	public function inRooms($user = '') {
		if ($user == '') {
			return;
		}
		return $this->database->table(self::TABLE_INCHATROOM)
				->where(self::COLUMN_USER, $user)
				->order(self::COLUMN_ENTERED . ' ASC');
	}
	
	/** @return Nette\Database\Table\ActiveRow */
	public function getInRoom($id = '', $room = '') {
		if ($id == '' || $room == '') {
			return;
		}
		return $this->database->table(self::TABLE_INCHATROOM)
				->where(self::COLUMN_USER, $id)
				->where(self::COLUMN_CHATROOM_ID, $room)
				->fetch();
	}
	
	/** @return int */
	public function enterRoom($who, $where) {
		return $this->database->table(self::TABLE_INCHATROOM)
				->insert(array(
					self::COLUMN_USER		=>	$who,
					self::COLUMN_CHATROOM_ID	=>	$where
				));
	}
	
	/** @return int */
	public function leaveRoom($who, $where) {
		return $this->database->table(self::TABLE_INCHATROOM)
				->where(self::COLUMN_USER, $who)
				->where (self::COLUMN_CHATROOM_ID, $where)
				->delete();
	}
	
	/** @return int */
	public function updateRoomLastMsg($id, $room, $time = 'now()') {
		return $this->database->table(self::TABLE_INCHATROOM)
				->where(self::COLUMN_USER, $id)
				->where(self::COLUMN_CHATROOM_ID, $room)
				->update(array(
					self::COLUMN_LAST_MSG => $time
				));
	}
	
	/** @return Nette\Database\Table\Selection */
	public function getRoomUsers($id = '') {
		if ($id == '') {
			return;
		}
		return $this->database->table(self::TABLE_INCHATROOM)
				->where(self::COLUMN_CHATROOM_ID, $id)
				->order('user.name ASC');
	}
	
	/** @return boolean */
	public function isModerator($user = '', $room = '') {
		if ($user == '' || $room == '') {
			return;
		}
		return $this->database->table(self::TABLE_ROOM)
				->where(self::COLUMN_OWNER, $user)
				->where(self::COLUMN_ID, $room)
				->count();
	}
	
	/** @return Nette\Database\Table\ActiveRow */
	public function getCandidate($chatroom = '') {
		if ($chatroom == '') {
			return;
		}
		return $this->database->table(self::TABLE_INCHATROOM)
				->where(self::COLUMN_CHATROOM_ID, $chatroom)
				->order(self::COLUMN_ENTERED . ' ASC')
				->limit(1)
				->fetch();
	}
	
	/** @return int Number of updated chatrooms */
	public function setModerator($user = '', $room = '') {
		if ($user == '' || $room == '') {
			return;
		}
		return $this->database->table(self::TABLE_ROOM)
				->where(self::COLUMN_ID, $room)
				->update(array(
					self::COLUMN_OWNER => $user
				));
	}
	
	/** @return int Number of kicked users */
	public function kick($user, $room) {
		if (is_null($user) || is_null($room)) {
			return;
		}
		return $this->database->table(self::TABLE_INCHATROOM)
				->where(self::USER, $user)
				->where(self::COLUMN_CHATROOM_ID, $room)
				->delete();
	}
	
	/** @return  int Number of deleted chatrooms */
	public function deleteRoom($room) {
		return $this->database->table(self::TABLE_ROOM)
				->where(self::COLUMN_ID, $room)
				->delete();
	}
	
	/** @return Nette\Database\Table\ActiveRow */
	public function createRoom($data) {
		if (isset($data["locked"]) && $data["locked"] == TRUE && isset($data["password"])) {
			return $this->database->table(self::TABLE_ROOM)->insert(array(
				self::COLUMN_TITLE	=> $data["title"],
				self::COLUMN_OWNER	=> $data["user_id"],
				self::COLUMN_LOCK	=> 't',
				self::COLUMN_DESCRIPTION	=> $data["description"],
				self::COLUMN_PASSWORD	=> $data["password"]
			));
		} else {
			return $this->database->table(self::TABLE_ROOM)->insert(array(
				self::COLUMN_TITLE	=> $data["title"],
				self::COLUMN_OWNER	=> $data["user_id"],
				self::COLUMN_DESCRIPTION	=> $data["description"]
			));
		}
	}
	
	/** @return int */
	public function updateRoom($data) {
		if (isset($data["locked"])  && $data["locked"] == TRUE && isset($data["password"])) {
			return $this->database->table(self::TABLE_ROOM)
					->where(self::COLUMN_ID, $data["chatroom_id"])
					->update(array(
						self::COLUMN_TITLE			=> $data["title"],
						self::COLUMN_LOCK			=> 't',
						self::COLUMN_DESCRIPTION	=> $data["description"] ,
						self::COLUMN_PASSWORD		=> $data["password"]
					));
		} else {
			return $this->database->table(self::TABLE_ROOM)
					->where(self::COLUMN_ID, $data["chatroom_id"])
					->update(array(
						self::COLUMN_TITLE			=> $data["title"],
						self::COLUMN_DESCRIPTION	=> $data["description"],
						self::COLUMN_LOCK			=> 'f',
						self::COLUMN_PASSWORD		=> null
					));
		}
	}
}