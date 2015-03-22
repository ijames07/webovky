<?php

namespace App\Presenters;

use Nette;

class ChatroomsPresenter extends \App\Presenters\BasePresenter {
	
	public function startup() {
		parent::startup();
		if (!$this->getUser()->isLoggedIn()) {
			if ($this->user->logoutReason == Nette\Security\IUserStorage::INACTIVITY) {
				$this->flashMessage('Byl jsi odhlášen z důvodu neaktivity. Pro pokračování se musíš přihlásit.');
			}
			$this->redirect('Sign:in', array('backlink' => $this->storeRequest()));
		}
	}
	
	public function actionDefault() {
		$this->template->rooms = $this->context->getService('roomsService')->getAll()->order('created DESC');
		$this->template->title = 'Místnosti';	
	}
	
	public function actionRoom($id = '') {
		if ($id == '') {
			$this->redirect('Chatrooms:');
		}
		
		$rooms = $this->context->getService('roomsService');
		$this->template->messages = $this->context->getService('messagesService')
				->getRoomMsg($id);
		$rooms->enterRoom($this->getUser()->getId(), $id);
		$this->template->room = $rooms->get($id);
		$this->template->users = $rooms->getRoomUsers($id);
	}
	
	public function actionSend() {
		if (!$this->isAjax()) {
			return;
		}
		$msg = $this->request->getPost('msg');	
		if (strpos($msg, '/w') === 0) {
			
		} else {
			// public message to whole chatroom
			$rooms = $this->context->getService('roomsService');
			$user_id = $this->getUser()->getId();
			$chatroom = $rooms->inRoom($user_id);
			$this->context->getService('messagesService')
					->createPublicMsg($user_id, $chatroom, $msg);
			$rooms->updateLastMsg($user_id);
		}
		
		/*
		$msg = explode(' ', $this->request->getPost('msg'));	
		if (strcmp(array_shift($msg), '/w') === 0) {
			// private message to one chat user
			$receiver = array_shift($msg);
			$msg = array_implode(' ', $msg);
			$this->context->getService('messagesService')
					->createPrivateMsg($this->getUser()->getId(), $receiver, $msg);
		} else {
			// public message to whole chatroom
			$rooms = $this->context->getService('roomsService');
			$user_id = $this->getUser()->getId();
			$chatroom = $rooms->inRoom($user_id);
			$msg = array_implode(' ', $msg);
			$this->context->getService('messagesService')
					->createPublicMsg($user_id, $chatroom, $msg);
			$rooms->updateLastMsg($user_id);
		}*/
		$this->payload->message = "OK";
		$this->terminate();
	}
}
		
