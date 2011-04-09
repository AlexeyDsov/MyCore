<?php
interface IServiceLocatorSupport {
	public function setServiceLocator(ServiceLocator $serviceLocator);
	
	public function getServiceLocator();
}
?>