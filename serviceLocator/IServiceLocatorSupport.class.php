<?php
interface IServiceLocatorSupport {
	public function setServiceLocator(IServiceLocator $serviceLocator);

	public function getServiceLocator();
}
?>