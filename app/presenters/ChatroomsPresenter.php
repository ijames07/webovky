<?php

namespace App\Presenters;

use Nette,
	Nette\Application\UI\Form as Form;

class ChatroomsPresenter extends \App\Presenters\BasePresenter {
	
	public function startup() {
		parent::startup();
		if (!$this->getUser()->isLoggedIn()) {
			if ($this->user->logoutReason == Nette\Security\IUserStorage::INACTIVITY) {
				$this->flashMessage('Byl jsi odhlášen z důvodu neaktivity. Pro pokračování se musíš přihlásit.');
			}
			$this->redirect('Sign:in', array('backlink' => $this->storeRequest()));
		}
		$this->template->inRooms = $this->context->getService('roomsService')->inRooms($this->getUser()->getId());
	}
	
	public function actionDefault() {
		$rooms = $this->context->getService('roomsService');
		
		// obdrzeni vsech mistnosti
		$this->template->rooms = $rooms->getAll()->order('created DESC');
		
		// pro odlisny vypis zamknute mistnosti, ve ktere ale uzivatel uz je
		$this->template->in_room = array();
		
		// ziskam vsechny mistnosti, ve kterych je uzivatel
		$user_rooms = $rooms->inRooms($this->getUser()->getId());
		foreach ($user_rooms as $room) {
			array_push($this->template->in_room, $room->chatroom_id);
		}
	}
	
	public function actionLeave($id = '') {
		if ($id == '') {
			return;
		}
		
		$rooms = $this->context->getService('roomsService');
		$user_id = $this->getUser()->getId();
		
		// opusť místnost
		if ($rooms->leaveRoom($user_id, $id) == 1) {
			$this->flashMessage('Opustil jsi místnost.', 'success');
		} else {
			$this->flashMessage('Nejsi v této místnosti. Nemůžeš z ní odejít', 'error');
		}
		
		// je aktualni uzivatel moderator?
		if ($rooms->isModerator($user_id, $id)) {
			
			// vyhledej kandidata na moderatora
			$candidate = $rooms->getCandidate($id);
			
			// zustala prazdna mistnost?
			if ($candidate === false) {
				
				// odstran mistnost
				$rooms->deleteRoom($id);
			} else {
			
				// nastav kandidata jako noveho moderatora mistnosti
				$rooms->setModerator($candidate->id, $id);
			}
		}
		$this->redirect('Chatrooms:');
	}
	
    public function actionUpdate() {
        if (!$this->isAjax()) {
            $this->terminate();
        }
        $room = $this->request->getPost('room');
		
        // Prislo id mistnosti?
        if (empty($room)) {
            $this->terminate();
        }
		$user_id = $this->getUser()->getId();
		$rooms = $this->context->getService('roomsService');
		
        // ziskam activeRow s podrobnostmi
        $in_room = $rooms->getInRoom($user_id, $room);
        if ($in_room === false) {
			
            // nema tu co delat, neni clenem teto mistnosti
            $this->sendResponse(new \Nette\Application\Responses\JsonResponse(array('kick' => 1)));
        }

        // ziskej nove zpravy od posledni kontroly novych zprav
        $messages = $this->context->getService('messagesService')
				->getNewMessages($room, $user_id, $in_room->last_message);
		
        // aktualizuj posledni prectenou zpravu uzivatele
        $rooms->updateRoomLastMsg($user_id, $room);
        $returnMessages = array();
		
        // Napln pole zprav do jednoho pole pro odeslani uzivateli
        foreach ($messages as $msg) {
			$to = null;
			if (!empty($msg->to_user_id)) {
				$to = $msg->ref('to_user_id')->nickname;
			}
            array_push($returnMessages, array(
                'from'  => $msg->ref('from_user_id')->nickname,
                'time'  => date('G:i:s j.n.', strtotime($msg->time)),
                'msg'   => $msg->message,
				'to'	=> $to
            ));
        }
		
        // zadna nova zprava, konec
        if (empty($returnMessages)) {
            $this->terminate();
        }
        // odesli nove zpravy
        $this->sendResponse(new \Nette\Application\Responses\JsonResponse($returnMessages));
        $this->terminate();
    }
	
	public function actionRoom($id = '') {
		// Je zadane nejake id?
		if ($id == '') {
			$this->redirect('Chatrooms:');
		}
		
		$rooms = $this->context->getService('roomsService');
		$user_id = $this->getUser()->getId();
		$room = $rooms->get($id);
		
		// zpracovani formulare vlastnosti mistnosti pro moderatora
		if ($room->owner_user_id == $user_id) {
			$this['formChatroom']->addSubmit('send', 'Upravit');
			$this["formChatroom"]->setDefaults(array(
				'title'			=>	$room->title,
				'description'	=>	$room->description,
				'locked'		=>	$room->lock == 't' ? true : false
			));
			$this["formChatroom"]->addHidden('chatroom_id', $room->id);
		}
		
		// je zadano id mistnosti, ktera existuje?
		if (empty($room)) {
			$this->flashMessage('Litujeme, ale tato místnost byla již smazána', 'info');
			$this->redirect('Chatrooms:');
		}
		
		// ziskani detailu o vstupu do mistnosti
		$in_room = $rooms->getInRoom($user_id, $id);
		
		$messages = $this->context->getService('messagesService');
		
		// Je uzivatel v teto mistnosti?
		if ($in_room === false) {
			// PRVNI VSTUP
			// Pokud je zamknuta mistnost a zaroven neni uzivatel v teto mistnosti
			// zkontroluj jeho zadane heslo
			if ($room->lock == 't') {
				$pw = $this->request->getPost('pw');
				if (!strcmp($pw, $room->password) == 0) {
					$this->flashMessage('Bylo zadáno špatné heslo', 'error');
					$this->redirect('Chatrooms:');
				}
			}
			$rooms->enterRoom($user_id, $id);
			$in_room = $rooms->getInRoom($user_id, $id);
			$this->template->messages = $messages->getLimitedMessages($id, $user_id);
		} else {
			// vratil se
			// nactu nove zpravy vcetne posledni shlednute
			$this->template->messages = $messages->getNewMessages($id, $user_id, $in_room->last_message);
			if (count($this->template->messages) < 5) {
				$this->template->messages = $messages->getLimitedMessages($id, $user_id);
			}
			// aktualizuji posledni shlednou zpravu v teto mistnosti
			$rooms->updateRoomLastMsg($user_id, $id);
		}
		
		// zjisti kdo je v mistnosti
		$this->template->users = $rooms->getRoomUsers($id);
		$this->template->nick = $in_room->ref('user_id')->nickname;
		$this->template->user_id = $user_id;
		$this->template->room = $room;
	}
	
	public function actionKick($id = '', $room = '') {
		if ($id == '' || $room == '') {
			return;
		}
		$this->context->getService('roomsService')->kick($id, $room);
		$this->redirect('Chatrooms:room', $room);
	}
	
	public function actionSend() {
		// akceptuj pouze ajax
		if (!$this->isAjax()) {
			return;
		}
		$msg = $this->request->getPost('msg');	
		$room = $this->request->getPost('room');
		$user = $this->request->getPost('to');
		
		// pokud je prazdna zprava => konec
		if (empty($msg)) {
			$this->terminate();
		}
		
		// je to septani mezi uzivateli nebo zprava do mistnosti
		if (!empty($user)) {
			// soukroma zprava mezi 2 uzivateli
			
			$recipient = $this->context->getService('usersService')->getIDByNick($user);
			if (empty($recipient)) {
				return;
			}
			$this->context->getService('messagesService')
					->createUserMsg($room, $this->getUser()->getId(), $recipient, $msg);
		} else {
			// verejna zprava pro vsechny v mistnosti
			$user_id = $this->getUser()->getId();
			$this->context->getService('messagesService')
					->createPublicMsg($room, $user_id, $msg);
		}
		$this->terminate();
	}
	
	public function actionCreate() {
		$this['formChatroom']->addSubmit('send', 'Vytvořit');
	}
	
	protected function createComponentFormChatroom() {
		$form = new Form;
		$form->setRenderer(new \Instante\Bootstrap3Renderer\BootstrapRenderer());
		$form->addText('title', 'Název')
				->addRule(Form::FILLED, 'Zadejte název místnosti');
		$form->addText('description', 'Popis')
				->addRule(Form::FILLED, 'Zadejte popis místnosti');
		$form->addCheckbox('locked', 'Zamknout');
		$form->addPassword('password', 'Heslo')
				->addConditionOn($form['locked'], Form::EQUAL, TRUE)
					->setRequired('Napiš heslo pro vstup do místnosti');
		$form->onSuccess[] = callback($this, 'formChatroomSuccess');
		return $form;
	}
	
	public function formChatroomSuccess(Form $form) {
		$values = $form->getValues();
		$user_id = $this->getUser()->getId();
		$values["user_id"] = $user_id;
		$chatrooms = $this->context->getService('roomsService');
		if (isset($values["chatroom_id"])) {
			// update
			$chatrooms->updateRoom($values);
			$id = $values["chatroom_id"];
		} else {
			// insert
			$newRoom = $chatrooms->createRoom($values);
			$chatrooms->enterRoom($user_id, $newRoom->id);
			$id = $newRoom->id;
		}
		$this->redirect('Chatrooms:room', $id);
	}
}
		
