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
		$this->template->character = $this->character;
	}



	/**
	 * @return NAppForm
	 */
	protected function createComponentRenameForm()
	{
		$form = new NAppForm;

		$form->addText('name', 'Jméno');

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

		if ($account->online) {
			$form->addError('Přejmenování nejde provést, když je účet online, nejdříve se z účtu odhlašte');
			return;
		}


		if ($this->characterFacade->findOneByName($values->name)) {
			$form->addError('Zvolené jméno je již zbrané, zvolte prosím jiné');
			return;
		}

		if (!$this->characterFacade->takePrice($character->guid, $price['type'], $price['count'])) {
			if ($price['type'] == 'golds') {
				$form->addError('Nemáte dostatek goldů, je potřeba ' . $price['count'] . 'g');
			} else if ($price['type'] == 'item') {
				$parts = explode(':', $price['count']);
				$item = $this->worldFacade->findItemNameById($parts[0]);
				$form->addError('Nemáte dostatek ' . $item . ', je třeba ' . $parts[1] . 'x');
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
	}



	/**
	 * @return NAppForm
	 */
	protected function createComponentTeleportForm()
	{
		$form = new NAppForm;

		$form->addSelect('location', 'Lokace')
			->setPrompt('Vyberte prosím cílovou lokaci');

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

		if ($account->online) {
			$form->addError('Teleportování nejde provést, když je účet online, nejdříve se z účtu odhlašte');
			return;
		}

		if (!$this->characterFacade->takePrice($character->guid, $price['type'], $price['count'])) {
			if ($price['type'] == 'golds') {
				$form->addError('Nemáte dostatek goldů, je potřeba ' . $price['count'] . 'g');
			} else if ($price['type'] == 'item') {
				$parts = explode(':', $price['count']);
				$item = $this->worldFacade->findItemNameById($parts[0]);
				$form->addError('Nemáte dostatek ' . $item . ', je třeba ' . $parts[1] . 'x');
			}
			return;
		} else {
			$this->characterFacade->teleport($character->guid, $values->location);
			$this->flashMessage('Postava byla teleportována', 'success');
			$this->redirect('this');
		}
	}

}