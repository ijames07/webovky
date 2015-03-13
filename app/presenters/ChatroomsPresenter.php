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
	
	public function actionRoom($id) {
		
	}
}
		
