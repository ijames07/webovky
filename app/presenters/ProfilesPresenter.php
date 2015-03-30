<?php

namespace App\Presenters;

use Nette,
	Nette\Application\UI\Form as Form;

class ProfilesPresenter extends \App\Presenters\BasePresenter {
	
	public function startup() {
		parent::startup();
		$this->template->inRooms = $this->context->getService('roomsService')
				->inRooms($this->getUser()->getId());
	}

	public function actionDefault() {
		if (!$this->getUser()->isLoggedIn()) {
			if ($this->user->logoutReason == Nette\Security\IUserStorage::INACTIVITY) {
				$this->flashMessage('Byl jsi odhlášen z důvodu neaktivity. Pro pokračování se musíš přihlásit.');
			}
			$this->redirect('Sign:in', array('backlink' => $this->storeRequest()));
		}
		
		$user = $this->context->getService('usersService')->get($this->getUser()->getId());
		$this["editUserForm"]->setDefaults(array(
			'name'	=>	$user->name,
			'surname'	=>	$user->surname,
			'email'	=>	$user->email,
			'nick'	=> $user->nickname
		));
	}
	
	protected function createComponentEditUserForm() {
		$form = new Form;
		$form->setRenderer(new \Instante\Bootstrap3Renderer\BootstrapRenderer());
		$form->addGroup('Heslo');
		$form->addPassword('current', 'Aktuální heslo')
				->setRequired('Aktuální heslo je povinné');
		$form->addPassword('newpw1', 'Nové heslo', 20)
				->addCondition($form::FILLED)
					->addRule($form::MIN_LENGTH, "Minimální délka nového hesla je %d znaky!", 6);
		$form->addPassword('newpw2', 'Nové heslo znovu', 20)
				->addConditionOn($form['newpw1'], Form::FILLED)
					->setRequired('Zadej nové heslo znovu pro kontrolu')
					->addRule(Form::EQUAL, 'Nové heslo se neshoduje.', $form['newpw1']);
		$form->addGroup('Osobní údaje');
		$form->addText('nick', 'Přezdívka');
		$form->addText('name', 'Jméno');
		$form->addText('surname', 'Příjmení');
		$form->addText('email', 'E-mail', 35)
				->setEmptyValue('@')
				->addRule(Form::FILLED, 'Zadejte svůj email')
				->addCondition(Form::FILLED)
				->addRule(Form::EMAIL, 'Zadaný text nemá správný formát emailu');
		$form->addSubmit('send', 'Upravit');
		$form->onSuccess[] = callback($this, 'editUserFormSuccess');
		return $form;
	}
	
	public function editUserFormSuccess(Form $form) {
		$values = $form->getValues();
		$user_id = $this->getUser()->getId();
		$users = $this->context->getService('usersService');
		$user = $users->get($user_id);
		$values["user_id"] = $user_id;
		if (!Nette\Security\Passwords::verify($values["current"], $user->password)) {
			$this->flashMessage('Špatně zadané současné heslo!', 'error');
			$this->redirect('Profiles:');
		}
		$result = $users->update($values);
		$this->flashMessage('Údaje úspěšně aktualizovány', 'success');
		$this->redirect('Profiles:');
	}
	
	public function actionRegister() {

	}

	protected function createComponentUserForm() {
		$form = new Nette\Application\UI\Form;
		$form->setRenderer(new \Instante\Bootstrap3Renderer\BootstrapRenderer());
		$form->addGroup('Detaily účtu');
		$form->addText('email', 'E-mail', 35)
				->setEmptyValue('@')
				->addRule(Form::FILLED, 'Zadejte svůj email')
				->addCondition(Form::FILLED)
				->addRule(Form::EMAIL, 'Zadaný text nemá správný formát emailu');
		$form->addPassword('password', 'Heslo', 20)
				->addRule(Form::FILLED, 'Vyplňte Vaše heslo')
				->addRule(Form::MIN_LENGTH, 'Heslo musí mít alespoň %d znaků.', 6);
		$form->addPassword('password2', 'Heslo znovu', 20)
				->addConditionOn($form['password'], Form::VALID)
				->addRule(Form::FILLED, 'Stejné heslo ještě jednou pro kontrolu')
				->addRule(Form::EQUAL, 'Hesla se neshodují.', $form['password']);
		$form->addText('nick', 'Přezdívka')
				->addRule(Form::FILLED, 'Zadejte své přihlašovací jméno');
		$form->addGroup('Osobní údaje');
		$form->addText('name', 'Jméno');
		$form->addText('surname', 'Příjmení');
		$form->addRadioList('gender', 'Pohlaví', array(
			1 => 'muž',
			0 => 'žena'
		))
				->addRule(Form::FILLED, "Vyber své pohlaví");
		$form->addSubmit('send', 'Zaregistrovat');
		$form->onSuccess[] = callback($this, 'userFormSuccess');
		return $form;
	}
	
	public function userFormSuccess(Nette\Application\UI\Form $form) {
		$values = $form->getValues();
		$result = $this->context->getService('usersService')->add($values);
		if (isset($result->id)) {
			$this->flashMessage('Registrace se zdařila, nyní se můžeš přihlásit.', 'success');
			$this->redirect('Sign:in');
		} else {
			$this->flashMessage('Chyba přidání účtu', 'error');
			$this->redirect('Profiles:register');
		}
	}
	
}