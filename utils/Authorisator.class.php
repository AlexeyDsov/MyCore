<?php
class Authorisator {
	/**
	 * @var SessionWrapper
	 */
	protected $session = null;
	protected $userClassName = null;
	protected $userIdParamName = 'userId';
	
	protected $preloadedUser = false;
	protected $user = null;
	
	/**
	 * @return Authorisator
	 */
	public static function create()
	{
		return new self();
	}
	
	/**
	 * @return SessionWrapper 
	 */
	public function getSession()
	{
		return $this->session;
	}
	
	/**
	 * @return Authorisator 
	 */
	public function setSession(SessionWrapper $session)
	{
		$this->session = $session;
		return $this;
	}
	
	public function getUserClassName()
	{
		return $this->userClassName;
	}
	
	/**
	 * @param string $userClassName
	 * @return Authorisator 
	 */
	public function setUserClassName($userClassName)
	{
		$this->userClassName = $userClassName;
		return $this;
	}
	
	public function getUserIdParamName()
	{
		return $this->userIdParamName;
	}
	
	/**
	 * @param string $userClassName
	 * @return Authorisator 
	 */
	public function setUserIdParamName($userIdParamName)
	{
		$this->userIdParamName = $userIdParamName;
		return $this;
	}
	
	public function getUser()
	{
		Assert::isNotNull($this->session, 'session must be setted');
		Assert::isNotEmpty($this->userClassName, 'userClassName must be setted');
		
		if ($this->preloadedUser === true) {
			return $this->user;
		}
		$this->preloadedUser = true;
		
		
		if (!$this->session->isStarted()) {
			return $this->user = null;
		}
		
		$form = Form::create()->add(
			Primitive::identifier($this->userIdParamName)->
				of($this->userClassName)->
				required()
		);
		
		$form->import($this->session->getAll());
		
		if ($form->getErrors()) {
			return $this->user = null;
		}
		
		return $this->user = $form->getValue($this->userIdParamName);
	}
	
	/**
	 * @return Authorisator 
	 */
	public function setUser(Identifiable $user)
	{
		Assert::isNotNull($this->session, 'session must be setted');
		Assert::isNotEmpty($this->userClassName, 'userClassName must be setted');
		
		if (!$this->session->isStarted()) {
			$this->session->start();
		}
		
		$this->session->assign($this->userIdParamName, $user->getId());
		return $this;
	}
	
	/**
	 * @return Authorisator 
	 */
	public function dropUser()
	{
		Assert::isNotNull($this->session);
		if ($this->session->isStarted()) {
			$this->session->drop($this->userIdParamName);
		}
		return $this;
	}
}
?>