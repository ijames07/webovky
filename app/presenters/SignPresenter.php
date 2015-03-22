<?php

namespace App\Presenters;

use Nette,
	Nette\Application\UI\Form as Form;


/**
 * Sign in/out presenters.
 */
class SignPresenter extends BasePresenter {
	/*
	public function startup() {
		parent::startup();
		if ($this->getUser()->isLoggedIn()) {
			$this->redirect('Chatrooms:default');
		}
	}*/
	
	/**
	 * Sign-in form factory.
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentSignInForm() {
		$form = new Form;
		$form->setRenderer(new \Instante\Bootstrap3Renderer\BootstrapRenderer());
		$form->addText('login', "Přihlašovací jméno")
			->addCondition(Form::FILLED);
		$form->addPassword('password', "Heslo")
			->addCondition(Form::FILLED);
		$form->addCheckbox('remember', 'Zapamatovat přihlášení');
		$form->addSubmit('send', 'Přihlásit');
		$form->onSuccess[] = callback($this, 'signInFormSucceeded');
		return $form;
	}
	
	public function signInFormSucceeded(Form $form) {
		$values = $form->getValues();
		if ($values->remember) {
			$this->user->setExpiration('14 days', FALSE);
		} else {
			$this->user->setExpiration('20 minutes', TRUE);
		}
		
		try {
			$this->getUser()->login($values->login, $values->password);
			$this->flashMessage("Přihlášení proběhlo úspěšně", 'success');
			//$this->restoreRequest($this->backlink);
			$this->redirect('Chatrooms:');
		} catch (Nette\Security\AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}
	
	public function actionDefault() {
		$this->redirect('Sign:in');
	}

	public function actionOut()
	{
		$this->getUser()->logout();
		$this->flashMessage('You have been signed out.');
		$this->redirect('in');
	}
	
	public function actionRegister() {
		
	}

	protected function createComponentRegisterForm() {
		$form = new Nette\Application\UI\Form;
		$form->setRenderer(new \Instante\Bootstrap3Renderer\BootstrapRenderer());
		$form->addGroup('Detaily účtu');
		$form->addText('login', 'Přihlašovací jméno')
				->addRule(Form::FILLED, 'Zadejte své přihlašovací jméno');
		$form->addPassword('password', 'Heslo', 20)
				->setOption('description', 'Alespoň 6 znaků')
				->addRule(Form::FILLED, 'Vyplňte Vaše heslo')
				->addRule(Form::MIN_LENGTH, 'Heslo musí mít alespoň %d znaků.', 6);
		$form->addPassword('password2', 'Heslo znovu', 20)
				->addConditionOn($form['password'], Form::VALID)
				->addRule(Form::FILLED, 'Stejné heslo ještě jednou pro kontrolu')
				->addRule(Form::EQUAL, 'Hesla se neshodují.', $form['password']);
		$form->addGroup('Osobní údaje');
		$form->addText('name', 'Jméno');
		$form->addText('surname', 'Příjmení');
		$form->addText('email', 'E-mail', 35)
				->setEmptyValue('@')
				->addRule(Form::FILLED, 'Zadejte svůj email')
				->addCondition(Form::FILLED)
				->addRule(Form::EMAIL, 'Zadaný text nemá správný formát emailu');
		$sex = array(
			1 => 'muž',
			0 => 'žena'
		);
		$form->addRadioList('gender', 'Pohlaví', $sex)
				->addRule(Form::FILLED, "Vyber své pohlaví");	
		$form->addSubmit('send', 'Zaregistrovat');
		$form->onSuccess[] = callback($this, 'registerFormSuccess');
		return $form;
	}
	
	public function registerFormSuccess(Nette\Application\UI\Form $form) {
		$values = $form->getValues();
		$result = $this->context->getService('usersService')->add($values);
		if (isset($result->id)) {
			$this->redirect('Homepage:');
		} else {
			$this->flashMessage('Chyba přidání účtu', 'error');
		}
	}
}
