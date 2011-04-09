<?php
class WebAppSessionHandler implements InterceptingChainHandler
{
	protected $sessionName		= null;
	protected $cookiePath		= '/';
	protected $cookieDomain		= null;
	protected $cookieTime		= 1800;

	/**
	 * @return WebAppSessionHandler
	 */
	public static function create()
	{
		return new self();
	}

	/**
	 * @return WebAppSessionHandler
	 */
	public function run(InterceptingChain $chain)
	{
		Assert::isNotEmpty($this->sessionName, 'sessionName must not be empty');
		Assert::isNotEmpty($this->cookieDomain, 'cookie domain must not be empty');

		$sessionName = session_name($this->sessionName);
		session_set_cookie_params($this->cookieTime, $this->cookiePath, '.'.$this->cookieDomain);

		if (
			array_key_exists($sessionName, $_REQUEST)
			&& !preg_match('/^[0-9a-z\-]+$/i', $_REQUEST[$sessionName])
		) {
			unset($_REQUEST[$sessionName]);
		}

		if (
			array_key_exists($sessionName, $_COOKIE)
			&& !preg_match('/^[0-9a-z\-]+$/i', $_COOKIE[$sessionName])
		) {
			unset($_COOKIE[$sessionName]);
		}

		$session = SessionWrapper::me();
		if (!empty($_COOKIE[session_name()])) {
			$session->start();
		} else {
			/**
			 * Not start session if user disable cookies or if it's a bot
			**/
		}
		
		$serviceLocator = $chain->getServiceLocator();
		$serviceLocator->set('session', $session);

		$chain->next();

		return $this;
	}

	/**
	 * @return WebAppSessionHandler
	 */
	public function setSessionName($sessionName)
	{
		$this->sessionName = $sessionName;

		return $this;
	}

	/**
	 * @return WebAppSessionHandler
	 */
	public function setCookiePath($cookiePath)
	{
		$this->cookiePath = $cookiePath;

		return $this;
	}

	/**
	 * @return WebAppSessionHandler
	 */
	public function setCookieDomain($cookieDomain)
	{
		$this->cookieDomain = $cookieDomain;

		return $this;
	}
	
	/**
	 * @param int $cookieTime
	 * @return WebAppSessionHandler 
	 */
	public function setCookieTime($cookieTime)
	{
		$this->cookieTime = $cookieTime;
		return $this;
	}
}
?>