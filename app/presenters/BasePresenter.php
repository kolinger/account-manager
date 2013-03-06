<?php

/**
 * @author Tomáš Kolinger <tomas@kolinger.name>
 */
abstract class BasePresenter extends NPresenter
{

	/**
	 * @var string
	 * @persistent
	 */
	public $lang;

	/**
	 * @var Gettext
	 */
	protected $translator;



	/**
	 * @param Gettext
	 */
	public function injectTranslator(Gettext $translator)
	{
		$this->translator = $translator;
	}



	/**
	 * @param string $class
	 * @return ITemplate
	 */
	public function createTemplate($class = NULL)
	{
		$template = parent::createTemplate($class);

		if (!isset($this->lang)) {
			$this->lang = $this->context->parameters["lang"];
		}

		$this->translator->setLang($this->lang);
		$template->setTranslator($this->translator);

		return $template;
	}




	public function beforeRender()
	{
		$this->template->lang = $this->lang;
	}

}
