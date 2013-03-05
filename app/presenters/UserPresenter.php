<?php

/**
 * @author Tomáš Kolinger <tomas@kolinger.name>
 */
class UserPresenter extends BasePresenter
{

	/**
	 * @var AccountFacade
	 */
	private $accountFacade;

	/**
	 * @var TokenFacade
	 */
	private $tokenFacade;

	/**
	 * @var NHttpRequest
	 */
	private $httpRequest;
	


	/**
	 * @param AccountFacade $accountFacade
	 */
	public function injectAccountFacade(AccountFacade $accountFacade)
	{
		$this->accountFacade = $accountFacade;
	}



	/**
	 * @param TokenFacade $tokenFacade
	 */
	public function injectTokenFacade(TokenFacade $tokenFacade)
	{
		$this->tokenFacade = $tokenFacade;
	}



	/**
	 * @param NHttpRequest $httpRequest
	 */
	public function injectHttpRequest(NHttpRequest $httpRequest)
	{
		$this->httpRequest = $httpRequest;
	}


	
	/**
	 * @param object $element
	 */
	public function checkRequirements($element)
	{
		$outsideSections = array('login', 'lostPassword', 'registration');
		$totallyFree = array('completeChangeEmail', 'completeRegistration', 'completeLostPassword');
		if ($this->getUser()->isLoggedIn() && in_array($this->getAction(), $outsideSections) && !in_array($this->getAction(), $totallyFree)) {
			$this->redirect('Dashboard:');
		} else if (!$this->getUser()->isLoggedIn() && !in_array($this->getAction(), $outsideSections) && !in_array($this->getAction(), $totallyFree)) {
			$this->flashMessage('Pro zobrazení stránky se musíte přihlásit', 'error');
			$this->redirect('login');
		}
	}



	/************************ change password ************************/


	/**
	 * @return NAppForm
	 */
	protected function createComponentChangePasswordForm()
	{
		$form = new NAppForm;
		$form->addProtection('Platnost formuláře vypršela, aktualizujte prosím stránku a akci opakujte');

		$form->addPassword('old', 'Aktuální heslo')
			->setRequired('Vyplňte prosím aktuální heslo');

		$form->addPassword('new', 'Nové heslo')
			->setRequired('Vyplňte prosím nové heslo')
			->addRule(NForm::MIN_LENGTH, 'Heslo musí mít alespoň %d znaky', 4);

		$form->addPassword('verify', 'Ověření hesla')
			->setRequired('Vyplňte prosím ověření nového hesla')
			->addRule(NForm::EQUAL, 'Ověření nového hesla nesouhlasí', $form['new']);

		$form->addSubmit('submit', 'Změnit');

		$form->onSuccess[] = callback($this, 'processChangePasswordForm');
		return $form;
	}



	/**
	 * @param NAppForm $form
	 */
	public function processChangePasswordForm(NAppForm $form)
	{
		$values = $form->getValues();

		$account = $this->accountFacade->findOneById($this->getUser()->getId());
		if (strcasecmp($account->sha_pass_hash, Authenticator::calculateHash($values->old, $account->username)) != 0) {
			$form->addError('Aktuální heslo nesouhlasí');
			return;
		}

		$this->accountFacade->changePassword($account->id, $account->username, $values->new);
		$this->flashMessage('Heslo bylo změněno', 'success');
		$this->redirect('this');
	}



	/************************ change e-mail ************************/



	/**
	 * @return NAppForm
	 */
	protected function createComponentChangeEmailForm()
	{
		$form = new NAppForm;

		$form->addText('email', 'E-mail')
			->setRequired('Vyplňte prosím e-mail')
			->addRule(NForm::EMAIL, 'Byl zadán neplatný formát e-mailu');

		$form->addSubmit('submit', 'Změnit');

		$form->onSuccess[] = callback($this, 'processChangeEmailForm');
		return $form;
	}



	/**
	 * @param NAppForm $form
	 */
	public function processChangeEmailForm(NAppForm $form)
	{
		$values = $form->getValues();
		$account = $this->accountFacade->findOneById($this->getUser()->getId());
		$token = $this->tokenFacade->create($account->id, $values->email);

		$template = $this->createTemplate();
		$template->setFile(dirname(__FILE__) . '/../templates/emails/changeEmail.latte');
		$template->link = $this->link('//User:completeChangeEmail', $token);
		$template->account = $account->username;
		$template->email = $values->email;
		$template->ip = $this->httpRequest->getRemoteAddress();

		$message = new NMail();
		$message->setFrom($this->getContext()->parameters['email']);
		$message->setSubject('Změna e-mailu');
		$message->addTo($account->email);
		$message->setBody($template);
		$message->send();

		$this->flashMessage('Na aktuální e-mail by odeslán potvrzovací e-mail', 'success');
		$this->redirect('this');
	}



	/**
	 * @param string $id
	 */
	public function actionCompleteChangeEmail($id)
	{
		$token = $this->tokenFacade->findOneById($id);
		if (!$token) {
			$this->flashMessage('Neplatný kód', 'error');
			if ($this->getUser()->isLoggedIn()) {
				$this->redirect('changeEmail');
			} else {
				$this->redirect('login');
			}
			return;
		}

		$this->accountFacade->changeEmail($token->user, $token->data);
		$this->tokenFacade->delete($id);
		$this->flashMessage('E-mail byl změněn', 'success');

		if ($this->getUser()->isLoggedIn()) {
			$this->redirect('changeEmail');
		} else {
			$this->redirect('login');
		}
	}



	/************************ change type ************************/



	public function actionChangeType()
	{
		$account = $this->accountFacade->findOneById($this->getUser()->getId());
		$this['changeTypeForm']['type']->setItems($this->getExpansions());
		$this['changeTypeForm']->setDefaults(array('type' => $account->expansion));
	}



	/**
	 * @return NAppForm
	 */
	protected function createComponentChangeTypeForm()
	{
		$form = new NAppForm;

		$form->addSelect('type', 'Typ');

		$form->addSubmit('submit', 'Změnit');

		$form->onSuccess[] = callback($this, 'processChangeTypeForm');
		return $form;
	}



	/**
	 * @param NAppForm $form
	 */
	public function processChangeTypeForm(NAppForm $form)
	{
		$values = $form->getValues();
		$this->accountFacade->changeType($this->getUser()->getId(), $values->type);
		$this->flashMessage('Typ byl změněn', 'success');
		$this->redirect('this');
	}



	/************************ login ************************/



	/**
	 * @return NAppForm
	 */
	protected function createComponentLoginForm()
	{
		$form = new NAppForm;
		$form->addProtection('Platnost formuláře vypršela, aktualizujte prosím stránku a akci opakujte');

		$form->addText('username', 'Jméno')
			->setRequired('Vyplňte prosím jméno');

		$form->addPassword('password', 'Heslo')
			->setRequired('Vyplňte prosím heslo');

		$form->addSubmit('login', 'Login');

		$form->onSuccess[] = callback($this, 'processLoginForm');
		return $form;
	}



	/**
	 * @param NAppForm $form
	 */
	public function processLoginForm(NAppForm $form)
	{
		$values = $form->getValues();
		try {
			$this->getUser()->login($values->username, $values->password);
			$this->flashMessage('Přihlášení proběhlo úspěšně', 'success');
			$this->redirect('Dashboard:');
		} catch (NAuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}



	/************************ registration ************************/



	public function actionRegistration()
	{
		$this['registrationForm']['type']->setItems($this->getExpansions());
		$this['registrationForm']->setDefaults(array('type' => $this->getCurrentExpansion()));
	}



	/**
	 * @return NAppForm
	 */
	protected function createComponentRegistrationForm()
	{
		$form = new NAppForm;
		$form->addProtection('Platnost formuláře vypršela, aktualizujte prosím stránku a akci opakujte');

		$form->addText('username', 'Jméno')
			->setRequired('Vyplňte prosím jméno');

		$form->addText('email', 'E-mail')
			->setRequired('Vyplňte prosím e-mail')
			->addRule(NForm::EMAIL, 'Byl zadán neplatný formát e-mailu');

		$form->addPassword('password', 'Heslo')
			->setRequired('Vyplňte prosím heslo')
			->addRule(NForm::MIN_LENGTH, 'Heslo musí mít alespoň %d znaky', 4);

		$form->addPassword('password2', 'Heslo (ověření)')
			->setRequired('Vyplňte prosím ověření hesla')
			->addRule(NForm::EQUAL, 'Ověření hesla nesouhlasí', $form['password']);

		$form->addSelect('type', 'Typ');

		$form->addSubmit('save', 'Registrovat se');

		$form->onSuccess[] = callback($this, 'processRegistrationForm');
		return $form;
	}



	/**
	 * @param NAppForm $form
	 */
	public function processRegistrationForm(NAppForm $form)
	{
		$values = $form->getValues();

		if ($this->accountFacade->findOneByUsername($values->username)) {
			$form->addError('Zadané jméno je již obsazené, zvolte prosím jiné');
			return;
		}

		if ($this->accountFacade->findOneByEmail($values->email)) {
			$form->addError('Zadaný e-mail je již obsazené, zvolte prosím jiný');
			return;
		}

		$id = $this->accountFacade->create($values->username, $values->password, $values->email, $values->type);
		$token = $this->tokenFacade->create($id, 0);

		$template = $this->createTemplate();
		$template->setFile(dirname(__FILE__) . '/../templates/emails/registration.latte');
		$template->link = $this->link('//User:completeRegistration', $token);
		$template->account = $values->username;

		$message = new NMail();
		$message->setFrom($this->getContext()->parameters['email']);
		$message->setSubject('Dokončení registrace');
		$message->addTo($values->email);
		$message->setBody($template);
		$message->send();

		$this->flashMessage('Účet byl vytvořen, na zadaný e-mail by odeslán aktivační kód', 'success');
		$this->redirect('this');
	}



	/**
	 * @param string $id
	 */
	public function actionCompleteRegistration($id)
	{
		$token = $this->tokenFacade->findOneById($id);
		if (!$token) {
			$this->flashMessage('Neplatný kód', 'error');
			$this->redirect('login');
			return;
		}

		$this->accountFacade->activate($token->user);
		$this->tokenFacade->delete($id);
		$this->flashMessage('Účet byl aktivován, můžete se přihlásit', 'success');

		$this->redirect('login');
	}



	/************************ lost password ************************/



	/**
	 * @return NAppForm
	 */
	protected function createComponentLostPasswordForm()
	{
		$form = new NAppForm;
		$form->addProtection('Platnost formuláře vypršela, aktualizujte prosím stránku a akci opakujte');

		$form->addText('value', 'E-mail / jméno')
			->setRequired('Zadejte prosím e-mail nebo jméno');

		$form->addSubmit('submit', 'Odeslat');

		$form->onSuccess[] = callback($this, 'processLostPasswordForm');
		return $form;
	}



	/**
	 * @param NAppForm $form
	 */
	public function processLostPasswordForm(NAppForm $form)
	{
		$values = $form->getValues();

		$account = $this->accountFacade->findOneByUsername($values->value);
		if (!$account) {
			$account = $this->accountFacade->findOneByEmail($values->value);
		}

		if (!$account) {
			$form->addError('Účet nebyl nalezen');
			return;
		}

		$token = $this->tokenFacade->create($account->id, 0);

		$template = $this->createTemplate();
		$template->setFile(dirname(__FILE__) . '/../templates/emails/lostPassword.latte');
		$template->link = $this->link('//User:completeLostPassword', $token);
		$template->account = $account->username;
		$template->ip = $this->httpRequest->getRemoteAddress();

		$message = new NMail();
		$message->setFrom($this->getContext()->parameters['email']);
		$message->setSubject('Zapomenuté heslo');
		$message->addTo($account->email);
		$message->setBody($template);
		$message->send();

		$this->flashMessage('Na registrační e-mail byly odeslány další instrukce', 'success');
		$this->redirect('this');
	}



	/**
	 * @param string $id
	 */
	public function actionCompleteLostPassword($id)
	{
		$token = $this->tokenFacade->findOneById($id);
		if (!$token) {
			$this->flashMessage('Neplatný kód', 'error');
			$this->redirect('lostPassword');
			return;
		}
	}



	/**
	 * @return NAppForm
	 */
	protected function createComponentCompleteLostPasswordForm()
	{
		$form = new NAppForm;
		$form->addProtection('Platnost formuláře vypršela, aktualizujte prosím stránku a akci opakujte');

		$form->addPassword('password', 'Heslo')
			->setRequired('Vyplňte prosím heslo')
			->addRule(NForm::MIN_LENGTH, 'Heslo musí mít alespoň %d znaky', 4);

		$form->addPassword('password2', 'Heslo (ověření)')
			->setRequired('Vyplňte prosím ověření hesla')
			->addRule(NForm::EQUAL, 'Ověření hesla nesouhlasí', $form['password']);

		$form->addSubmit('submit', 'Změnit');

		$form->onSuccess[] = callback($this, 'processCompleteLostPasswordForm');
		return $form;
	}




	/**
	 * @param NAppForm $form
	 */
	public function processCompleteLostPasswordForm(NAppForm $form)
	{
		$values = $form->getValues();

		$token = $this->tokenFacade->findOneById($this->getParameter('id'));
		if (!$token) {
			$form->addError('Neplatný kód');
			return;
		}

		$account = $this->accountFacade->findOneById($token->user);
		$this->tokenFacade->delete($this->getParameter('id'));
		$this->accountFacade->changePassword($account->id, $account->username, $values->password);

		$this->flashMessage('Heslo bylo změněno', 'success');
		$this->redirect('login');
	}



	/************************ logout ************************/



	public function actionLogout()
	{
		$this->getUser()->logout();
		$this->flashMessage('Odhlášení bylo úspěšné', 'success');
		$this->redirect('User:login');
	}



	/************************ helpers ************************/


	/**
	 * @return array
	 */
	public function getExpansions()
	{
		$version = $this->getContext()->parameters['version'];
		$items = array(
			0 => 'Classic',
		);
		if (in_array($version, array('tbc', 'wotlk', 'cata'))) {
			$items[1] = 'Burning Crusade';
		}
		if (in_array($version, array('wotlk', 'cata'))) {
			$items[2] = 'Wrath of the Lich King';
		}
		if (in_array($version, array('cata'))) {
			$items[3] = 'Cataclysm';
		}
		return $items;
	}



	/**
	 * @return int
	 */
	public function getCurrentExpansion()
	{
		return array_search($this->getContext()->parameters['version'], $this->getExpansions());
	}

}
