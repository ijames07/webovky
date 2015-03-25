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
	public function createPublicMsg($room, $uid, $msg = '') {
		return $this->database->table('message')->insert(array(
			'from_user_id' => $uid,
			'message' => $msg,
			'chatroom_id' => $room
		));
	}
	
	/** return @return int */
	public function createUserMsg($room, $uid, $receiver, $msg = '') {
		return $this->database->table('message')->insert(array(
			'chatroom_id'	=> $room,
			'from_user_id'	=> $uid,
			'message'		=> $msg,
			'to_user_id'	=> $receiver
		));
	}
	
	/** @return Nette\Database\Table\ActiveRow */
	public function get($id = '') {
		return $this->database->table('message')->get($id);
	}
	
	/** @return Nette\Database\Table\Selection */
	public function getLimitedMessages($room, $user, $count = 10) {
		return $this->database->table('message')
				->where('chatroom_id', $room)
				->where('to_user_id IS NULL OR to_user_id = ? OR from_user_id = ?', $user, $user)
				->order('time DESC')
				->limit($count);
	}
	
	/** @return Nette\Database\Table\Selection */
	public function getNewMessages($room, $user, $last = '1.1.1970') {
		return $this->database->table('message')
				->where('chatroom_id', $room)
				->where('to_user_id IS NULL OR to_user_id = ? OR from_user_id = ?', $user, $user)
				->where('time > ?', $last);
	}
}