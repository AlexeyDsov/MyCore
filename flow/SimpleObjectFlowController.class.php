<?php
/***************************************************************************
 *   Copyright (C) 2011 by Alexey Denisov                                  *
 *   alexeydsov@gmail.com                                                  *
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU Lesser General Public License as        *
 *   published by the Free Software Foundation; either version 3 of the    *
 *   License, or (at your option) any later version.                       *
 *                                                                         *
 ***************************************************************************/

	/**
	 * Класс для отображения данных об объекте и редактировании их
	 */
	abstract class SimpleObjectFlowController extends ToolkitBaseController {

		/**
		 * Список методов, реализуемых контроллером
		 * @var array
		 */
		protected $methodMap = array(
			'info' => 'infoProcess',
			'edit' => 'editProcess',
			'take' => 'takeProcess',
			'drop' => 'dropProcess',
		);
		protected $defaultAction = 'info';

		/**
		 * Определяет, какое действие должен выполнить контроллер, вызывает его и возвращает результат
		 * @param $request HttpRequest
		 * @return ModelAndView
		**/
		public function handleRequest(HttpRequest $request)
		{
			return $this->resolveAction($request);
		}

		/**
		 * Возвращает модель для отображения информации об объекте
		 * @param $request HttpRequest
		 * @return ModelAndView
		**/
		protected function infoProcess(HttpRequest $request)
		{
			$proto = $this->getObjectProto();

			$form = Form::create();
			$proto->getPropertyByName('id')->fillForm($form);
			$form->get('id')->required();

			$form->import($request->getGet());

			if ($form->getErrors()) {
				return $this->getMav('index', 'NotFound');
			}
			
			if (!$this->serviceLocator->get('linker')->isObjectSupported($form->getValue('id'), $this->getInfoAction())) {
				throw new PermissionException('No permission for info '.$this->getObjectName());
			}

			$infoObject = $form->getValue('id');
			$this->model->
				set('infoObject', $infoObject)->
				set('customInfoFieldsData', $this->getCustomInfoFieldsData($infoObject))->
				set('orderFunction', $this->getFunctionListOrder())->
				set('buttonUrlList', $this->getButtonUrlList($infoObject));
			return $this->getMav('info');
		}

		/**
		 * Возвращает модель с данными для редактирования объекта (форму)
		 * @param $request HttpRequest
		 * @return ModelAndView
		**/
		protected function editProcess(HttpRequest $request)
		{
			$proto = $this->getObjectProto();

			$form = $proto->makeForm();
			$subject = ClassUtils::callStaticMethod("{$this->getObjectName()}::create");

			$command = $this->getCommand();
			/* @var $command EditorCommand */
			$mav = $command->run($subject, $form, $request);
			
			$accessObject = $form->getValue('id') ?: $this->getObjectName();
			if (!$this->serviceLocator->get('linker')->isObjectSupported($accessObject, $this->getEditAction($accessObject))) {
				throw new PermissionException('No permission for edit '.$this->getObjectName());
			}

			return $this->getEditMav($form, $subject, $mav->getModel());
		}

		/**
		 * Валидирует данные для сохранения в объект,
		 * если данные валидны - выполняет операцию сохранения объекта и возвращает редирект на просмотр объекта
		 * если данные не валидны - отмечает не валидные примитивы в форме
		 *  и возвращает форму для продолжения редактирования
		 * @param $request HttpRequest
		 * @return ModelAndView
		**/
		protected function takeProcess(HttpRequest $request)
		{
			$proto = $this->getObjectProto();
			$form = $proto->makeForm();
			$subject = ClassUtils::callStaticMethod("{$this->getObjectName()}::create");
			if ($request->hasGetVar('id')) {
				$form->importOne('id', $request->getGet());
			}
			
			$editObject = $form->getValue('id') ?: $this->getObjectName();
			if (!$this->serviceLocator->get('linker')->isObjectSupported($editObject, $this->getEditAction($editObject))) {
				throw new PermissionException('No permission for edit '.$this->getObjectName());
			}

			$command = $this->getCommand();
			/* @var $command EditorCommand */
			if ($this->isTakeInTransaction()) {
				$command = new CarefulDatabaseRunner($command);
			}

			$mav = $command->run($subject, $form, $request);

			if ($mav->getView() != EditorController::COMMAND_SUCCEEDED) {
				if ($command instanceof CarefulCommand) {
					$command->rollback();
				}
				return $this->getEditMav($form, $subject, $mav->getModel());
			}

			if ($command instanceof CarefulCommand) {
				$command->commit();
			}

			if ($this->serviceLocator->get('isAjax')) {
				$isNew = (bool) $request->hasGetVar('id') ? $request->getGetVar('id') : false;
				$this->model->
					set('isNew', $isNew)->
					set('infoObject', $subject)->
					set('infoUrl', $this->getUrlInfo($subject))->
					set('closeDialog', $this->toCloseDialog($subject))
					;
				return $this->getMav('edit.success');
			}

			return $this->getMavRedirectByUrl($this->getUrlInfo($subject));
		}

		protected function dropProcess(HttpRequest $request)
		{
			$proto = $this->getObjectProto();

			$form = Form::create();
			$proto->getPropertyByName('id')->fillForm($form);
			$form->get('id')->required();
			$form->import($request->getGet());

			if (!($subject = $form->getValue('id'))) {
				return $this->getMav('drop.success');
			}
			
			if (!$this->serviceLocator->get('linker')->isObjectSupported($subject, $this->getDropAction())) {
				throw new PermissionException('No permission for drop '.$className);
			}

			$confirmed = $request->hasGetVar('confirm');
			
			if (!$confirmed) {
				$this->model->
					set('infoObject', $subject)->
					set('dropUrl', $this->getUrlDrop($subject, true))->
					set('infoUrl', $this->getUrlInfo($subject));
				return $this->getMav('drop.confirm');
			}
			
			$command = $this->getDropCommand();
			/* @var $command DropCommand */
			$transaction = null;
			if ($this->isTakeInTransaction()) {
//				$transaction = InnerTransaction::begin($subject->dao());
				$db = DBPool::getByDao($subject->dao())->begin();
			}

			$mav = $command->run($subject, $form, $request);

			if ($mav->getView() != EditorController::COMMAND_SUCCEEDED) {
				if (isset($db)) {
					$db->rollback();
				}
//				if ($transaction) {
//					$transaction->rollback();
//				}
				return $this->getEditMav($form, $subject, $mav->getModel());
			}
			
			if (isset($db)) {
				$db->commit();
			}
//			if ($transaction) {
//				$transaction->commit();
//			}

			if ($this->serviceLocator->get('isAjax')) {
				$this->model->
					set('infoObject', $subject)->
					set('infoUrl', $this->getUrlInfo($subject))->
					set('id', $request->getGetVar('id'));
				return $this->getMav('drop.success');
			}

			return $this->getMavRedirectByUrl($this->getUrlInfo($subject));
		}

		protected function getEditMav(Form $form, IdentifiableObject $subject, Model $commandModel)
		{
			$infoObject = $form->getValue('id') ?: $subject;
			$this->model->
				set('form', $form)->
				set('infoObjectPrototype', $subject)->
				set('infoObject', $infoObject)->
				set('commandModel', $commandModel)->
				set('customEditFieldsData', $this->getCustomEditFieldsData($form, $subject))->
				set('orderFunction', $this->getFunctionListOrder())->
				set('infoUrl', $this->getUrlInfo($infoObject))->
				set('takeUrl', $this->getUrlTake($infoObject))->
				set('closeDialog', $this->toCloseDialog($infoObject))
				;
			$linker = $this->serviceLocator->get('linker');
			if ($linker->isObjectSupported($infoObject, $this->getDropAction())) {
				$this->model->set('dropUrl', $this->getUrlDrop($infoObject));
			}

			return $this->getMav('edit');
		}

		/**
		 * Возвращает массив дополнительных данных для кастомного отображения свойств объекта
		 * @param IdentifiableObject $infoObject
		 * @return array
		 */
		protected function getCustomInfoFieldsData(IdentifiableObject $infoObject)
		{
			return array();
		}

		/**
		 * Возвращает массив дополнительных данных для кастомного отображения редактируемых полей объекта
		 * @param Form $form
		 * @param IdentifiableObject $subject
		 * @return array
		 */
		protected function getCustomEditFieldsData(Form $form, IdentifiableObject $subject)
		{
			return array();
		}

		/**
		 * Возвращает порядок сортировки провертей объекта при его отображении
		 * Все не перечисленные параметры будут оказываться после перечисленных в порядке по умолчнию
		 * @return array
		 */
		protected function getOrderFieldList()
		{
			return array();
		}

		/**
		 * Возвращает имя класса бизнес объекта с которым работает данный контроллер
		 * По умолчанию для удобства это обрезанное название текущего контроллера (убрана часть controller)
		 * @return string
		 */
		protected function getObjectName()
		{
			$className = get_class($this);
			return substr($className, 0, stripos($className, 'controller'));
		}

		/**
		 * Возвращает прото объекта, с которым происходит работа в текущем контроллере
		 * @return AbstractProtoClass
		 */
		protected function getObjectProto()
		{
			return ClassUtils::callStaticMethod("{$this->getObjectName()}::proto");
		}

		/**
		 * Возвращает название комманды, реализующей редактирование объекта
		 * @return string
		 */
		protected function getCommandName()
		{
			return 'TakeEditToolkitCommand';
		}

		/**
		 * Возвращает название комманды, реализующей удаление объекта
		 * @return string
		 */
		protected function getDropCommandName()
		{
			return 'DropCommand';
		}
		
		
		/**
		 * Создает и возвращает комманду для редактирования объекта
		 * @return EditorCommand
		 */
		protected function getCommand()
		{
			$command = $this->serviceLocator->spawn($this->getCommandName());
			
			if ($command instanceof TakeEditToolkitCommand) {
				if ($callbackLog = $this->getCallbackLog()) {
					$command->setLogCallback($callbackLog);
				}
			}
			
			return $command;
		}

		/**
		 * Создает и возвращает комманду для редактирования объекта
		 * @return DropCommand
		 */
		protected function getDropCommand()
		{
			return $this->serviceLocator->spawn($this->getDropCommandName());
		}

		/**
		 * Признак необходимости выполнять комманду в транзакции
		 * @return boolean
		 */
		protected function isTakeInTransaction()
		{
			return false;
		}

		/**
		 * Возвращает дефолтный путь к директории с шаблонами
		 * @return string
		 */
		protected function getViewPath()
		{
			return 'Objects/SimpleObject';
		}

		/**
		 * Возвращает массив ассоциативный названий-действий
		 * - url'ов действий которые можно делать пользователю с объектом
		 * @param type $infoObject
		 */
		protected function getButtonUrlList(IdentifiableObject $infoObject)
		{
			$linker = $this->serviceLocator->get('linker');
			/* @var $linker ToolkitLinkUtils */
			$buttonList = array();
			if ($linker->isObjectSupported($infoObject, $this->getEditAction($infoObject))) {
				$buttonList['Edit'] = array(
					'window' => true,
					'url' => $this->getUrlEdit($infoObject),
				);
			}
			if ($linker->isObjectSupported($infoObject, $this->getDropAction())) {
				$buttonList['Drop'] = array(
					'window' => true,
					'url' => $this->getUrlDrop($infoObject),
				);
			}
			
			if ($logClass = $this->getLogClassName()) {
				if ($linker->isObjectSupported($this->getLogClassName(), $this->getInfoAction())) {
					$buttonList['Logs'] = array(
						'window' => false,
						'url' => $linker->getUrlLog($infoObject),
					);
				}
			}

			return $buttonList;
		}
		
		protected function getLogClassName() {
			return null;
		}

		/**
		 * Возвращает url для просмотра свойств объекта
		 * @param IdentifiableObject $infoObject
		 * @return string
		 */
		protected function getUrlInfo(IdentifiableObject $infoObject)
		{
			return $this->serviceLocator->get('linker')->getUrl($infoObject, array(), $this->getInfoAction());
		}

		/**
		 * Возвращает url для формы-редактирования объекта
		 * @param IdentifiableObject $infoObject
		 * @return string
		 */
		protected function getUrlEdit(IdentifiableObject $infoObject)
		{
			return $this->serviceLocator->get('linker')->getUrl($infoObject, array('action' => 'edit'), $this->getEditAction($infoObject));
		}

		/**
		 * Возвращает url для операции сохранения новых свойств из формы объекта
		 * @param IdentifiableObject $infoObject
		 * @return string
		 */
		protected function getUrlTake(IdentifiableObject $infoObject)
		{
			return $this->serviceLocator->get('linker')->getUrl($infoObject, array('action' => 'take'), $this->getEditAction($infoObject));
		}
		
		protected function getUrlDrop(IdentifiableObject $infoObject, $confirm = false)
		{
			$url = $this->serviceLocator->get('linker')->getUrl($infoObject, array('action' => 'drop'), $this->getDropAction());
			if ($confirm) {
				$url .= '&confirm=1';
			}
			return $url;
		}

		final protected function getEmptyFieldData()
		{
			return array('tpl' => 'Objects/SimpleObject/empty');
		}
		
		protected function toCloseDialog(IdentifiableObject $subject)
		{
			return false;
		}
		
		protected function getCallbackLog() {
			return null;
		}
		
		protected function getInfoAction() {
			return 'info';
		}
		
		protected function getEditAction($object) {
			return is_object($object) ? 'edit' : 'add';
		}
		
		protected function getDropAction() {
			return 'drop';
		}

		protected function prepairData(HttpRequest $request, ModelAndView $mav) {
			$mav = parent::prepairData($request, $mav);
			if ($currentMenu = $this->getCurrentMenu($request, $mav)) {
				$mav->getModel()->set('currentMenu', $currentMenu);
			}
			return $mav;
		}
		
		protected function getCurrentMenu(HttpRequest $request, ModelAndView $mav) {
			return '';
		}

		/**
		 * Возвращает анонимную функцию для сортировки ассоциативной массива в необходимом порядке
		 * @return
		 */
		private function getFunctionListOrder()
		{
			$indexList = $this->getOrderFieldList();

			return function(array $dataList) use ($indexList) {
				$resultList = array();
				foreach ($indexList as $indexName) {
					if (array_key_exists($indexName, $dataList)) {
						$resultList[$indexName] = $dataList[$indexName];
						unset($dataList[$indexName]);
					}
				}
				$resultList += $dataList;
				return $resultList;
			};
		}
	}