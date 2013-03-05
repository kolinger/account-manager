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
	 * @var AccountFacade
	 */
	private $account;

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
}