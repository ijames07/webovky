<?php

namespace App\Model;
use Nette;



class Messages extends Nette\Object {
	/** @var Nette\Database\Inject */
	private $database;
	
	public function __construct(Nette\Database\Context $database) {
		$this->database = $database;
	}
	
	/** return @return int */
	public function createPublicMsg($uid, $room, $msg = '') {
		return $this->database->table('message')->insert(array(
			'from_user_id' => $uid,
			'message' => $msg,
			'chatroom_id' => $room
		));
	}
	
	/** return @return int */
	public function createUserMsg($uid, $receiver, $msg = '') {
		return $this->database->table('message')->insert(array(
			'from_user_id' => $uid,
			'message' => $msg,
			'to_user_id' => $receiver
		));
	}
	
	/** @return Nette\Database\Table\ActiveRow */
	public function get($id = '') {
		return $this->database->table('message')->get($id);
	}
	
	/** @return Nette\Database\Table\Selection */
	public function getMessagesInRoom($id = '', $last = '01.01.1970') {
		if ($id == '') {
			return;
		}
		return $this->database->table('message')
				->where('chatroom_id', $id)
				->where('time >= ?', $last)
				->order('time DESC');
	}
	
	/** @return Nette\Database\Table\Selection */
	public function getLimitedMessages($room, $count = 10) {
		return $this->database->table('message')
				->where('chatroom_id', $room)
				->order('time DESC')
				->limit($count);
	}
	
	/** @return Nette\Database\Table\Selection */
	public function getNewMessages($room, $last = 'now()') {
		return $this->database->table('message')
				->where('chatroom_id', $room)
				->where('time > ?', $last);
	}
}