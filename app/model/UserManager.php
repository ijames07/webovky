<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings,
	Nette\Security\Passwords;


/**
 * Users management.
 */
class UserManager extends Nette\Object implements Nette\Security\IAuthenticator
{
	const
		TABLE_NAME = 'user',
		COLUMN_ID = 'id',
		COLUMN_NICK = 'nickname',
		COLUMN_NAME = 'name',
		COLUMN_SURNAME = 'surname',
		COLUMN_GENDER = 'gender',
		COLUMN_EMAIL = 'email',
		COLUMN_PASSWORD_HASH = 'password',
		COLUMN_ROLE = 'role';


	/** @var Nette\Database\Context */
	private $database;


	public function __construct(Nette\Database\Context $database)
	{
		$this->database = $database;
	}


	/**
	 * Performs an authentication.
	 * @return Nette\Security\Identity
	 * @throws Nette\Security\AuthenticationException
	 */
	public function authenticate(array $credentials)
	{
		list($email, $password) = $credentials;

		$row = $this->database->table(self::TABLE_NAME)->where(self::COLUMN_EMAIL, $email)->fetch();

		if (!$row) {
			throw new Nette\Security\AuthenticationException('The username is incorrect.', self::IDENTITY_NOT_FOUND);

		} elseif (!Passwords::verify($password, $row[self::COLUMN_PASSWORD_HASH])) {
			throw new Nette\Security\AuthenticationException('The password is incorrect.', self::INVALID_CREDENTIAL);

		} elseif (Passwords::needsRehash($row[self::COLUMN_PASSWORD_HASH])) {
			$row->update(array(
				self::COLUMN_PASSWORD_HASH => Passwords::hash($password),
			));
		}

		$arr = $row->toArray();
		unset($arr[self::COLUMN_PASSWORD_HASH]);
		return new Nette\Security\Identity($row[self::COLUMN_ID], $row[self::COLUMN_ROLE], $arr);
	}


	/**
	 * Adds new user.
	 * @param  string
	 * @param  string
	 * @return Nette\Database\Table\ActiveRow
	 */
	public function add($user) {
		try {
			return $this->database->table(self::TABLE_NAME)->insert(array(
				self::COLUMN_NICK			=>	$user->nick,
				self::COLUMN_PASSWORD_HASH	=>	Passwords::hash($user->password),
				self::COLUMN_EMAIL			=>	$user->email,
				self::COLUMN_GENDER			=>	intval($user->gender) == 0 ? 'f' : 'm',
				self::COLUMN_NAME			=>	$user->name,
				self::COLUMN_SURNAME		=>	$user->surname
			));
		} catch (Nette\Database\UniqueConstraintViolationException $e) {
			throw new DuplicateNameException;
		}
	}
	
	/** @return int */
	public function getIDByNick($name) {
		if ($name == '') {
			return;
		}
		return $this->database->table(self::TABLE_NAME)
				->where(self::COLUMN_NAME, $name)->fetch()->id;
	}
	
	/** @return Nette\Database\Table\ActiveRow */
	public function get($id = '') {
		if ($id == '') {
			return;
		}
		return $this->database->table(self::TABLE_NAME)
				->get($id);
	}
	
	/** @return int */
	public function update($data) {
		try {
			if (!empty($data["newpw1"])) {
				return $this->database->table(self::TABLE_NAME)
						->where('id', $data["user_id"])
						->update(array(
							self::COLUMN_EMAIL	=>	$data['email'],
							self::COLUMN_NAME	=>	$data['name'],
							self::COLUMN_SURNAME	=>	$data['surname'],
							self::COLUMN_NICK	=>	$data['nick'],
							self::COLUMN_PASSWORD_HASH	=> Passwords::hash($data["newpw1"])
						));
			} else {
				return $this->database->table(self::TABLE_NAME)
					->where('id', $data["user_id"])
					->update(array(
						self::COLUMN_EMAIL	=>	$data['email'],
						self::COLUMN_NAME	=>	$data['name'],
						self::COLUMN_SURNAME	=>	$data['surname'],
						self::COLUMN_NICK	=>	$data['nick']
					));
			}
		} catch (Nette\Database\UniqueConstraintViolationException $e) {
			throw new DuplicateNameException;
		}
	}

}



class DuplicateNameException extends \Exception {
	
}
