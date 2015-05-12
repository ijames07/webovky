<?php

namespace App\Presenters;

use Nette,
	App\Model;


/**
 * Homepage presenter.
 */
class HomepagePresenter extends BasePresenter
{

	public function actionDefault()
	{
		if ($this->getUser()->isLoggedIn()) {
			$this->redirect('Chatrooms:');
		}
	}

}
