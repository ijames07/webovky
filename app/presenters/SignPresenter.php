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
		$form->addText('email', "Email")
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
			$this->getUser()->login($values->email, $values->password);
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
		$this->flashMessage('Odhlásil ses.', 'success');
		$this->redirect('Homepage:');
	}
}
