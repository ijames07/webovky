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
		if ($this->context->getService('roomsService')->leaveRoom($this->getUser()->getId(), $id) == 1) {
			$this->flashMessage('Opustil jsi místnost.', 'success');
		} else {
			$this->flashMessage('Nejsi v této místnosti. Nemůžeš z ní odejít', 'error');
		}
		$this->redirect('Chatrooms:');
	}
	
    public function actionUpdate() {
        if (!$this->isAjax()) {
            $this->terminate();
        }
        $room = $this->request->getPost('room');
        // neprislo id mistnosti
        if (empty($room)) {
            $this->terminate();
        }
        // ziskam activeRow s podrobnostmi, (chatroom_id, user_id, last_message, entered)
        $in_room = $this->context->getService('roomsService')->getInRoom($this->getUser()->getId(), $room);
        if (is_null($in_room)) {
            // nema tu co delat, neni clenem teto mistnosti
            $this->terminate();
        }

        // ziskej nove zpravy od posledni kontroly novych zprav
        $messages = $this->context->getService('messagesService')->getNewMessages($room, $this->getUser()->getId(), $in_room->last_message);
        // aktualizuj posledni precteni zprav
        $this->context->getService('roomsService')->updateRoomLastMsg($this->getUser()->getId(), $room);
        $returnMessages = array();
        // fetchni zpravy do jednoho pole
        foreach ($messages as $msg) {
			$to = null;
			if (!empty($msg->to_user_id)) {
				$to = $msg->ref('to_user_id')->name;
			}
            array_push($returnMessages, array(
                'from'  => $msg->ref('from_user_id')->name,
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
		$this->template->room = $rooms->get($id);
		
		// je zadano id mistnosti, ktera existuje?
		if (empty($this->template->room)) {
			$this->redirect('Chatrooms:');
		}
		
		// Je uzivatel v teto mistnosti?
		$in_room = $rooms->getInRoom($this->getUser()->getId(), $id);
		
		$messages = $this->context->getService('messagesService');
		
		if (empty($in_room)) {
			// PRVNI VSTUP
			// Pokud je zamknuta mistnost a zaroven neni uzivatel v teto mistnosti
			// zkontroluj jeho zadane heslo
			if ($this->template->room->lock == 't') {
				$pw = $this->request->getPost('pw');
				if (!strcmp($pw, $this->template->room->password) == 0) {
					$this->flashMessage('Bylo zadáno špatné heslo', 'error');
					$this->redirect('Chatrooms:');
				}
			}
			$rooms->enterRoom($this->getUser()->getId(), $id);
			$in_room = $rooms->getInRoom($this->getUser()->getId(), $id);
			$this->template->messages = $messages->getLimitedMessages($id, $this->getUser()->getId());
		} else {
			// vratil se
			// nactu nove zpravy vcetne posledni shlednute
			$this->template->messages = $messages->getNewMessages($id, $this->getUser()->getId(), $in_room->last_message);
			if (count($this->template->messages) < 5) {
				$this->template->messages = $messages->getLimitedMessages($id, $this->getUser()->getId());
			}
			// aktualizuji posledni shlednou zpravu v teto mistnosti
			$rooms->updateRoomLastMsg($this->getUser()->getId(), $id);
		}
		
		// zjisti kdo je v mistnosti
		$this->template->users = $rooms->getRoomUsers($id);
		$this->template->nick = $in_room->ref('user_id')->name;
		$this->template->user_id = $in_room->user_id;
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
			
			$recipient = $this->context->getService('usersService')->getIDByName($user);
			if (empty($recipient)) {
				return;
			}
			$this->context->getService('messagesService')->createUserMsg($room, $this->getUser()->getId(), $recipient, $msg);
		} else {
			// verejna zprava pro vsechny v mistnosti
			//$rooms = $this->context->getService('roomsService');
			$user_id = $this->getUser()->getId();
			//$in_room = $rooms->getInRoom($user_id, $room);
			$this->context->getService('messagesService')
					->createPublicMsg($room, $user_id, $msg);
		}
		//$this->payload->message = $msg;
		$this->terminate();
	}
	
	public function actionCreate() {
		
	}
	
	protected function createComponentCreateChatroom() {
		$form = new Form;
		$form->setRenderer(new \Instante\Bootstrap3Renderer\BootstrapRenderer());
		$form->addText('title', 'Název')
				->addRule(Form::FILLED, 'Zadejte název místnosti');
		$form->addText('description', 'Popis')
				->addRule(Form::FILLED, 'Zadejte popis místnosti');
		$form->addCheckbox('locked', 'Uzamknutá?');
		$form->addPassword('password', 'Heslo pro vstup')
				->setOption('description', 'Vyžadováno pouze pokud se jedná o zamknutou místnost')
				->addConditionOn($form['locked'], Form::EQUAL, TRUE)
					->setRequired('Napiš heslo pro vstup do místnosti');
		$form->addSubmit('send', 'Vytvořit');
		$form->onSuccess[] = callback($this, 'createChatroomSuccess');
		return $form;
	}
	
	public function createChatroomSuccess(Form $form) {
		$values = $form->getValues();
		$values["user_id"] = $this->getUser()->getId();
		$chatrooms = $this->context->getService('roomsService');
		$newRoom = $chatrooms->createRoom($values);
		$chatrooms->enterRoom($this->getUser()->getId(), $newRoom->id);
		$this->redirect('Chatrooms:room', $newRoom->id);
	}
}
		
