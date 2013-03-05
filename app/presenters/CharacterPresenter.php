<?php

/**
 * @author Tomáš Kolinger <tomas@kolinger.name>
 */
class CharacterPresenter extends BasePresenter
{

	/**
	 * @var CharacterFacade
	 */
	private $character;

	/**
	 * @var WorldFacade
	 */
	private $worldFacade;
	
	/**
	 * @var AccountFacade
	 */
	private $accountFacade;

	/**
	 * @var CharacterFacade
	 */
	private $characterFacade;



	/**
	 * @param AccountFacade $accountFacade
	 */
	public function injectAccountFacade(AccountFacade $accountFacade)
	{
		$this->accountFacade = $accountFacade;
	}


	/**
	 * @param WorldFacade $worldFacade
	 */
	public function injectWorldFacade(WorldFacade $worldFacade)
	{
		$this->worldFacade = $worldFacade;
	}



	/**
	 * @param CharacterFacade $characterFacade
	 */
	public function injectCharacterFacade(CharacterFacade $characterFacade)
	{
		$this->characterFacade = $characterFacade;
	}



	/************************ rename ************************/



	/**
	 * @param int $id
	 * @throws NBadRequestException
	 */
	public function actionRename($id)
	{
		$this->character = $this->characterFacade->findOneByIdAndAccount($id, $this->getUser()->getId());
		if (!$this->character) {
			throw new NBadRequestException(NULL, 404);
		}
	}



	public function renderRename()
	{
		$this->template->price = $this->getContext()->parameters['prices']['rename'];
		if ($this->template->price['type'] == 'item') {
			$parts = explode(':', $this->template->price['count']);
			$this->template->price['item'] = $this->worldFacade->findItemNameById($parts[0]);
			$this->template->price['count'] = $parts[1];
		}
		$this->template->character = $this->character;
	}



	/**
	 * @return NAppForm
	 */
	protected function createComponentRenameForm()
	{
		$form = new NAppForm;
		$form->setTranslator($this->translator);
		$form->addProtection('Platnost formuláře vypršela, aktualizujte prosím stránku a akci opakujte');

		$form->addText('name', 'Jméno')
			->setRequired('Vyplňte prosím jméno')
			->addRule(Nform::MIN_LENGTH, 'Jméno musí mít nejméně %d zanky', 3)
			->addRule(nform::PATTERN, 'Jméno může obsahovat pouze znaky anglické abecedy', '[a-zA-Z]+');

		$form->addSubmit('rename', 'Přejmenovat');

		$form->onSuccess[] = callback($this, 'processRenameForm');
		return $form;
	}



	/**
	 * @param NAppForm $form
	 */
	public function processRenameForm(NAppForm $form)
	{
		$values = $form->getValues();
		$account = $this->accountFacade->findOneById($this->getUser()->getId());
		$character = $this->character;
		$price = $this->getContext()->parameters['prices']['rename'];
		$values->name = ucfirst(strtolower($values->name));

		if ($this->checkOnlineStatus($account)) {
			$form->addError('Přejmenování nejde provést, když je účet online, nejdříve se z účtu odhlašte');
			return;
		}


		if ($this->characterFacade->findOneByName($values->name)) {
			$form->addError('Zvolené jméno je již zbrané, zvolte prosím jiné');
			return;
		}

		if (!$this->characterFacade->takePrice($character->guid, $price['type'], $price['count'])) {
			if ($price['type'] == 'golds') {
				$form->addError('Nemáte dostatek goldů');
			} else if ($price['type'] == 'item') {
				$form->addError('Nemáte dostatek itemů');
			}
			return;
		} else {
			$this->characterFacade->rename($character->guid, $values->name);
			$this->flashMessage('Postava byla přejmenována', 'success');
			$this->redirect('this');
		}
	}



	/************************ teleport ************************/



	/**
	 * @param int $id
	 * @throws NBadRequestException
	 */
	public function actionTeleport($id)
	{
		$this->character = $this->characterFacade->findOneByIdAndAccount($id, $this->getUser()->getId());
		if (!$this->character) {
			throw new NBadRequestException(NULL, 404);
		}

		$locations = $this->getContext()->parameters['locations'];
		$items = array();
		foreach ($locations as $name => $value) {
			$items[$name] = $name;
		}
		$this['teleportForm']['location']->setItems($items);
	}



	public function renderTeleport()
	{
		$this->template->character = $this->character;
		$this->template->price = $this->getContext()->parameters['prices']['teleport'];
		if ($this->template->price['type'] == 'item') {
			$parts = explode(':', $this->template->price['count']);
			$this->template->price['item'] = $this->worldFacade->findItemNameById($parts[0]);
			$this->template->price['count'] = $parts[1];
		}
	}



	/**
	 * @return NAppForm
	 */
	protected function createComponentTeleportForm()
	{
		$form = new NAppForm;
		$form->setTranslator($this->translator);
		$form->addProtection('Platnost formuláře vypršela, aktualizujte prosím stránku a akci opakujte');

		$form->addSelect('location', 'Lokace');

		$form->addSubmit('teleport', 'Teleportovat');

		$form->onSuccess[] = callback($this, 'processTeleportForm');
		return $form;
	}



	/**
	 * @param NAppForm $form
	 */
	public function processTeleportForm(NAppForm $form)
	{
		$values = $form->getValues();
		$account = $this->accountFacade->findOneById($this->getUser()->getId());
		$character = $this->character;
		$price = $this->getContext()->parameters['prices']['teleport'];
		$values->location = $this->getContext()->parameters['locations'][$values->location];

		if ($this->checkOnlineStatus($account)) {
			$form->addError('Teleportování nejde provést, když je účet online, nejdříve se z účtu odhlašte');
			return;
		}

		if (!$this->characterFacade->takePrice($character->guid, $price['type'], $price['count'])) {
			if ($price['type'] == 'golds') {
				$form->addError('Nemáte dostatek goldů');
			} else if ($price['type'] == 'item') {
				$form->addError('Nemáte dostatek itemů');
			}
			return;
		} else {
			$this->characterFacade->teleport($character->guid, $values->location);
			$this->flashMessage('Postava byla teleportována', 'success');
			$this->redirect('this');
		}
	}



	/************************ helpers ************************/


	/**
	 * @param DibiRow $account
	 * @return bool
	 */
	public function checkOnlineStatus($account)
	{
		if (isset($account->online) && $account->online) {
			return TRUE;
		}

		$characters = $this->characterFacade->findOnlineByAccount($account->id);
		if (count($characters) > 0) {
			return TRUE;
		}

		return FALSE;
	}

}