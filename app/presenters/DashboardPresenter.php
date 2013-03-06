<?php

/**
 * @author Tomáš Kolinger <tomas@kolinger.name>
 */
class DashboardPresenter extends BasePresenter
{

	/**
	 * @var DibiRow
	 */
	private $account;

	/**
	 * @var array
	 */
	private $characters;

	/**
	 * @var AccountFacade
	 */
	private $accountFacade;

	/**
	 * @var CharacterFacade
	 */
	private $characterFacade;

	/**
	 * @var NHttpRequest
	 */
	private $httpRequest;



	/**
	 * @param NHttpRequest $httpRequest
	 */
	public function injectHttpRequest(NHttpRequest $httpRequest)
	{
		$this->httpRequest = $httpRequest;
	}



	/**
	 * @param AccountFacade $accountFacade
	 */
	public function injectAccountFacade(AccountFacade $accountFacade)
	{
		$this->accountFacade = $accountFacade;
	}



	/**
	 * @param CharacterFacade $characterFacade
	 */
	public function injectCharacterFacade(CharacterFacade $characterFacade)
	{
		$this->characterFacade = $characterFacade;
	}



	/**
	 * @param object $element
	 */
	public function checkRequirements($element)
	{
		if (!$this->getUser()->isLoggedIn()) {
			$this->flashMessage('Pro zobrazení stránky se musíte přihlásit', 'error');
			$this->redirect('User:login');
		}
	}



	/************************ default ************************/



	public function actionDefault()
	{
		$this->account = $this->accountFacade->findOneById($this->getUser()->getId());
		if (!$this->account) {
			throw new NBadRequestException(NULL, 404);
		}

		$characters = $this->characterFacade->findByAccount($this->account->id);
		$played = 0;
		$items = array();
		foreach ($characters as $character) {
			$played += $character->totaltime;
			$items[$character->guid] = $character->name;
		}
		$this['charactersForm']['character']->setItems($items);
		$this->account->played = $played;
		$this->characters = $characters;
	}



	public function renderDefault()
	{
		$this->template->characters = $this->characters;
		$this->template->account = $this->account;
		$this->template->banned = $this->accountFacade->getBannedState($this->account->id, $this->httpRequest->getRemoteAddress());
	}



	/************************ characters ************************/



	/**
	 * @return NAppForm
	 */
	protected function createComponentCharactersForm()
	{
		$form = new NAppForm;
		$form->setTranslator($this->translator);
		$form->addProtection('Platnost formuláře vypršela, aktualizujte prosím stránku a akci opakujte');

		$form->addRadioList('character');

		$form->addSubmit('rename', 'Přejmenovat postavu');
		$form->addSubmit('teleport', 'Teleportovat postavu');

		$form->onSuccess[] = callback($this, 'processCharactersForm');

		return $form;
	}



	/**
	 * @param NAppForm $form
	 */
	public function processCharactersForm(NAppForm $form)
	{
		$values = $form->getValues();

		if ($values->character == NULL) {
			$form->addError('Vyberte prosím postavu');
			return;
		}

		if ($form['teleport']->isSubmittedBy()) {
			$this->redirect('Character:teleport', $values->character);
			return;
		}

		if ($form['rename']->isSubmittedBy()) {
			$this->redirect('Character:rename', $values->character);
			return;
		}
	}

}
