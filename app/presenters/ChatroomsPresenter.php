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
		$rooms = $this->context->getService('roomsService');
		
		// obdrzeni vsech mistnosti
		$this->template->rooms = $rooms->getAll()->order('created DESC');
		
		// pro odlisny vypis zamknute mistnosti, ve ktere ale uzivatel uz je
		$this->template->in_room = $rooms->inRoom($this->getUser()->getId());
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
        $in_room = $this->context->getService('roomsService')->inRoom($this->getUser()->getId(), $room);
        if (is_null($in_room)) {
            // nema tu co delat, neni clenem teto mistnosti
            $this->terminate();
        }

        // ziskej nove zpravy od posledni kontrolyn ovych zprav
        $messages = $this->context->getService('messagesService')->getNewMessages($room, $in_room->last_message);
        // aktualizuj posledni precteni zprav
        $this->context->getService('roomsService')->updateRoomLastMsg($this->getUser()->getId(), $room);
        $returnMessages = array();
        // fetchni zpravy do jednoho pole
        foreach ($messages as $msg) {
            array_push($returnMessages, array(
                'from'  => $msg->ref('from_user_id')->name,
                'time'  => date('G:i:s j.n.', strtotime($msg->time)),
                'msg'   => $msg->message
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
		
		// V ktere mistnosti je aktualne uzivatel?
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
			$this->template->messages = $messages->getLimitedMessages($id);
		} else {
			// vratil se
			// nactu nove zpravy vcetne posledni shlednute
			$this->template->messages = $messages->getMessagesInRoom($id, $in_room->last_message);
			if (count($this->template->messages) < 5) {
				$this->template->messages = $messages->getLimitedMessages($id);
			}
			// aktualizuji posledni shlednou zpravu v teto mistnosti
			$rooms->updateRoomLastMsg($this->getUser()->getId(), $id);
		}
		
		// zjisti kdo je v mistnosti
		$this->template->users = $rooms->getRoomUsers($id);
		$this->template->nick = $in_room->ref('user_id')->name;
	}
	
	public function actionSend() {
		// akceptuj pouze ajax
		if (!$this->isAjax()) {
			return;
		}
		$msg = $this->request->getPost('msg');	
		$room = $this->request->getPost('room');
		
		// pokud je prazdna zprava => konec
		if (empty($msg)) {
			$this->terminate();
		}
		
		// je to septani mezi uzivateli nebo zprava do mistnosti
		if (strpos($msg, '/w') === 0) {
			// soukroma zprava mezi 2 uzivateli
			
		} else {
			// verejna zprava pro vsechny v mistnosti
			//$rooms = $this->context->getService('roomsService');
			$user_id = $this->getUser()->getId();
			//$in_room = $rooms->getInRoom($user_id, $room);
			$this->context->getService('messagesService')
					->createPublicMsg($user_id, $room, $msg);
		}
		//$this->payload->message = $msg;
		$this->terminate();
	}
}
		
