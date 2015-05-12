<?php

namespace App\Model;
use Nette;



class Messages extends Nette\Object {
	/** @var Nette\Database\Inject */
	private $database;
	
	const TABLE_NAME = 'message',
			COLUMN_CHATROOM_ID = 'chatroom_id',
			COLUMN_FROM = 'from_user_id',
			COLUMN_TO = 'to_user_id',
			COLUMN_ID = 'id',
			COLUMN_TIME = 'time',
			COLUMN_MESSAGE = 'message';
			
	
	public function __construct(Nette\Database\Context $database) {
		$this->database = $database;
	}
	
	/** return @return int */
	public function createPublicMsg($room, $uid, $msg = '') {
		return $this->database->table(self::TABLE_NAME)->insert(array(
			self::COLUMN_FROM => $uid,
			self::COLUMN_MESSAGE => $msg,
			self::COLUMN_CHATROOM_ID => $room
		));
	}
	
	/** return @return int */
	public function createUserMsg($room, $uid, $receiver, $msg = '') {
		return $this->database->table(self::TABLE_NAME)->insert(array(
			self::COLUMN_CHATROOM_ID	=> $room,
			self::COLUMN_FROM			=> $uid,
			self::COLUMN_MESSAGE		=> $msg,
			self::COLUMN_TO				=> $receiver
		));
	}
	
	/** @return Nette\Database\Table\ActiveRow */
	public function get($id = '') {
		return $this->database->table(self::TABLE_NAME)->get($id);
	}
	
	/** @return Nette\Database\Table\Selection */
	public function getLimitedMessages($room, $user, $count = 10) {
		return $this->database->table(self::TABLE_NAME)
				->where(self::COLUMN_CHATROOM_ID, $room)
				->where(self::COLUMN_TO . ' IS NULL OR ' . self::COLUMN_TO . ' = ? OR ' . self::COLUMN_FROM . ' = ?', $user, $user)
				->order(self::COLUMN_TIME . ' DESC')
				->limit($count);
	}
	
	/** @return Nette\Database\Table\Selection */
	public function getNewMessages($room, $user, $last = '1.1.1970') {
		return $this->database->table(self::TABLE_NAME)
				->where(self::COLUMN_CHATROOM_ID, $room)
				->where(self::COLUMN_TO . ' IS NULL OR ' . self::COLUMN_TO . ' = ? OR ' . self::COLUMN_FROM . ' = ?', $user, $user)
				->where(self::COLUMN_TIME . ' > ?', $last);
	}
}