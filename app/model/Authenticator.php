<?php

/**
 * @author Tomáš Kolinger <tomas@kolinger.name>
 */
class Authenticator extends NObject implements IAuthenticator
{

	/**
	 * @var AccountFacade
	 */
	private $accountFacade;

	/**
	 * @var NHttpRequest
	 */
	private $httpRequest;



	/**
	 * @param AccountFacade $accountFacade
	 * @param NHttpRequest $httpRequest
	 */
	public function __construct(AccountFacade $accountFacade, NHttpRequest $httpRequest)
	{
		$this->accountFacade = $accountFacade;
		$this->httpRequest = $httpRequest;
	}



	/**
	 * @param array $credentials
	 * @return NIdentity
	 * @throws NAuthenticationException
	 */
	public function authenticate(array $credentials)
	{
		list($username, $password) = $credentials;

		$account = $this->accountFacade->findOneByUsername($username);
		if (!$account) {
			throw new NAuthenticationException('Špatné jméno', self::IDENTITY_NOT_FOUND);
		}

		if (strcasecmp($account->sha_pass_hash, self::calculateHash($password, $username)) != 0) {
			throw new NAuthenticationException('Špatné heslo', self::INVALID_CREDENTIAL);
		}

		if ($account->locked && $account->last_ip != $this->httpRequest->getRemoteAddress()) {
			throw new NAuthenticationException('Účet je uzamčen', self::FAILURE);
		}

		return new NIdentity($account->id, 'guest', array('username' => $account->username));
	}



	/**
	 * @param string $password
	 * @param string $username
	 * @return string
	 */
	public static function calculateHash($password, $username)
	{
		return sha1(strtoupper($username) . ':' . strtoupper($password));
	}

}
