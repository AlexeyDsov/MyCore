<?php
/***************************************************************************
 *   Copyright (C) 2011 by Alexey Denisov                                  *
 *   alexeydsov@gmail.com                                                  *
 ***************************************************************************/

	class ListMakerHelper
	{
		const OPTION_ORDERING = 'ordering';
		const OPTION_DEFAULT_ORDER = 'defaultOrder';
		const OPTION_FILTERABLE = 'filterable';
		const OPTION_FILTER_ORDER_FIELD = 'filterOrderField';
		const OPTION_FILTER_ORDER_FIELD_TYPE = 'filterOrderFieldType'; //asc or desc
		const OPTION_FILTERABLE_MAX_MIN = 'filterableMaxMin';
		const OPTION_FTS_INDEX = 'ftsIndex';
		const OPTION_DESCRIPTION = 'description';
		const OPTION_OBJECT_LINK = 'objectLink';
		const OPTION_ID_FILTER = 'idFilter';
		const OPTION_PROJECTION_GROUP = 'countProjection';
		const OPTION_FUNCTION_FIELD = 'funcField';
		const OPTION_RELOAD_ON_CHANGE = 'reloadOnChange';
		const OPTION_TO_STRING_METHOD = 'toStringMethod';
		const OPTION_NO_NULL_SELECT = 'noNullSelect';

		const OPERATOR_IS_NULL = -100;

		const ORDER_ASC = 'asc';
		const ORDER_DESC = 'desc';

		/**
		 * @var AbstractProtoClass
		 */
		protected $proto = null;
		protected $propertyList = array();
		/**
		 * @var Form
		 */
		protected $form = null;
		protected $pager = null;
		protected $pagerName = 'offset';
		protected $pagerLimit = 10;
		protected $orderParamName = 'order';
		protected $orderTypeName = 'orderType';
		/**
		 * @var Criteria
		 */
		protected $criteria = null;
		/**
		 * @var QueryResult
		 */
		protected $result = null;
		protected $ftsIndexParam = null;
		protected $selectorNameList = array();
		protected $selectorEnumerationNameList = array();
		protected $selectorList = array();
		protected $baseUrlParams = array();
		protected $routerName = 'default';
		protected $projection = false;

		/**
		 * Ajax
		 */
		protected $orderOnClick		= null;
		protected $submitOnClick	= null;


		public function __construct(AbstractProtoClass $proto, array $propertyList)
		{
			$this->proto = $proto;
			$this->propertyList = $propertyList;
			$this->testPropertyList();
			$this->form = Form::create();
		}

		/**
		 * @return ListMakerHelper
		 */
		public static function create(AbstractProtoClass $proto, array $propertyList)
		{
			return new self($proto, $propertyList);
		}

		public function setOrderClick($js)
		{
			$this->orderOnClick = $js;

			return $this;
		}

		public function setSubmitClick($js)
		{
			$this->submitOnClick = $js;

			return $this;
		}

		/**
		 * @return LightMetaProperty
		 */
		public static function getPropertyByName($objectLink, AbstractProtoClass $proto)
		{
			Assert::isString($objectLink);
			$pathParts = explode('.', $objectLink);
			$length = count($pathParts);
			if ($length == 0) {
				throw new WrongStateException('Object link must have minimum one object name on chain');
			}

			for ($i = 0; $i < $length; $i++) {
				if (!$proto->isPropertyExists($pathParts[$i])) {
					return null;
				}

				$property = $proto->getPropertyByName($pathParts[$i]);

				if ($i+1 < $length) {
					$className = $property->getClassName();
					if ($className === null) {
						return null;
					}
					$proto = ClassUtils::callStaticMethod($className.'::proto');
				}
			}

			return $property;
		}

		/**
		 * @return string
		 */
		public function getPagerName()
		{
			return $this->pagerName;
		}

		/**
		 * @var ListMakerHelper
		 */
		public function setPagerName($pagerName)
		{
			Assert::isString($pagerName);
			$this->pagerName = $pagerName;
			return $this;
		}

		/**
		 * @return string
		 */
		public function getPagerLimit()
		{
			return $this->pagerLimit;
		}

		/**
		 * @var ListMakerHelper
		 */
		public function setPagerLimit($pagerLimit)
		{
			Assert::isPositiveInteger($pagerLimit);
			$this->pagerLimit = $pagerLimit;
			return $this;
		}

		/**
		 * @return string
		 */
		public function getOrderParamName()
		{
			return $this->orderParamName;
		}

		/**
		 * @var ListMakerHelper
		 */
		public function setOrderParamName($orderParamName)
		{
			Assert::isString($orderParamName);
			$this->orderParamName = $orderParamName;
			return $this;
		}

		/**
		 * @return string
		 */
		public function getOrderTypeName()
		{
			return $this->orderTypeName;
		}

		/**
		 * @return ListMakerHelper
		 */
		public function setOrderTypeName($orderTypeName)
		{
			Assert::isString($orderTypeName);
			$this->orderTypeName = $orderTypeName;
			return $this;
		}

		/**
		 * @return array
		 */
		public function getBaseUrlParams()
		{
			return $this->baseUrlParams;
		}

		/**
		 * @return ListMakerHelper
		 */
		public function setBaseUrlParams(array $baseUrlParams)
		{
			$this->baseUrlParams = $baseUrlParams;
			return $this;
		}

		/**
		 * @return array
		 */
		public function getRouterName()
		{
			return $this->routerName;
		}

		/**
		 * @return ListMakerHelper
		 */
		public function SetRouterName($routerName)
		{
			Assert::isString($routerName);
			$this->routerName = $routerName;
			return $this;
		}

		/**
		 * @return Criteria
		 */
		public function getCriteria()
		{
			return $this->criteria;
		}

		/**
		 * @return QueryResult
		 */
		public function getResult()
		{
			return $this->result;
		}

		public function getCount()
		{
			return $this->result !== null ? $this->result->getCount() : null;
		}

		public function getList()
		{
			return $this->result !== null ? $this->result->getList() : null;
		}

		public function getOffset()
		{
			return $this->form->getSafeValue($this->pagerName);
		}

		/**
		 * @return ListMakerHelper
		 */
		public function makeForm(HttpRequest $request)
		{
			$this->form->add(
				Primitive::integer($this->pagerName)->
					setMin(0)->
					setDefault(0)
			);

			$orderList = array();
			$defaultOrderParam = null;
			$defaultOrderType = self::ORDER_ASC;

			foreach ($this->propertyList as $propertyName => $options) {
				if (isset($options[self::OPTION_FUNCTION_FIELD])) {
					Assert::isInstance($options[self::OPTION_FUNCTION_FIELD], 'SQLFunction');
					$objectLink = $options[self::OPTION_FUNCTION_FIELD];
					$property = null;
					$propertyType = 'string';
				} else {
					$objectLink = isset($options[self::OPTION_OBJECT_LINK]) ? $options[self::OPTION_OBJECT_LINK] : $propertyName;
					$property = $this->getPropertyByName($objectLink, $this->proto);
					$propertyType = $property ? $property->getType() : null;
				}

				if (
					(isset($options[self::OPTION_FTS_INDEX]) && $options[self::OPTION_FTS_INDEX])
					|| (isset($options[self::OPTION_FUNCTION_FIELD]))
				) {
					/* */
				} elseif ($property === null) {
					throw new WrongArgumentException("property name {$propertyName} not exist for proto ".get_class($this->proto));
				}

				if (isset($options[self::OPTION_PROJECTION_GROUP])) {
					$this->projection = $propertyName;
				}

				if (isset($options[self::OPTION_ORDERING])) {
					$orderList[$propertyName] = $objectLink;
				}
				if (isset($options[self::OPTION_DEFAULT_ORDER])) {
					$defaultOrderParam = $propertyName;
					if ($options[self::OPTION_DEFAULT_ORDER] == self::ORDER_DESC) {
						$defaultOrderType = self::ORDER_DESC;
					}
				}
				if (isset($options[self::OPTION_FTS_INDEX])) {
					$this->form->add(
						Primitive::string($propertyName)->
							setImportFilter(FilterC4U::textImport())
					);
					$this->ftsIndexParam = $propertyName;
				} else {
					if (isset($options[self::OPTION_FILTERABLE])) {
						if ($propertyType == 'identifier'
							|| $propertyType == 'identifierList'
							|| $propertyType == 'integerIdentifier'
						) {
							$this->form->add(Primitive::integer($propertyName));
							if (!isset($options[self::OPTION_ID_FILTER])) {
								$this->selectorNameList[$propertyName] = $property->getClassName();
							}
						} elseif ($propertyType == 'enumeration') {
							$this->form->add(Primitive::integer($propertyName));
							$this->selectorEnumerationNameList[$propertyName] = $property->getClassName();
						} elseif ($propertyType == 'timestamp') {
							$this->form->add(Primitive::timestamp($propertyName));
						} elseif ($propertyType == 'time') {
							$this->form->add(Primitive::time($propertyName));
						} elseif ($propertyType == 'date') {
							$this->form->add(Primitive::timestamp($propertyName));
						} elseif ($propertyType == 'integer') {
							$this->form->add(Primitive::integer($propertyName));
						} elseif ($propertyType == 'string') {
							$this->form->add(
								Primitive::string($propertyName)->
									setImportFilter(FilterC4U::textImport())
							);
						} elseif ($propertyType == 'boolean') {
							$this->form->add(Primitive::ternary($propertyName));
						} else {
							throw new UnimplementedFeatureException("С данным типом LightMetaProperty не описана работа: {$propertyType}");
						}
					}

					if (isset($options[self::OPTION_FILTERABLE_MAX_MIN])) {
						if ($propertyType == 'timestamp') {
							$this->form->add(Primitive::timestamp($propertyName.'From'));
							$this->form->add(Primitive::timestamp($propertyName.'To'));
						} elseif ($propertyType == 'date') {
							$this->form->add(Primitive::timestamp($propertyName.'From'));
							$this->form->add(Primitive::timestamp($propertyName.'To'));
						} else {
							throw new UnimplementedFeatureException("С данным типом LightMetaProperty не описана работа: {$propertyType}");
						}
					}
				}
			}

			if (count($orderList) > 0) {
				$primitive = Primitive::choice($this->orderParamName)->setList($orderList);
				if ($defaultOrderParam) {
					$primitive->setDefault($defaultOrderParam);
				} else {
					$primitive->setDefault(reset($orderList));
				}
				$this->form->
					add($primitive)->
					add(
						Primitive::choice($this->orderTypeName)->
							setList(
								array(
									self::ORDER_ASC => self::ORDER_ASC,
									self::ORDER_DESC => self::ORDER_DESC,
								)
							)->
							setDefault($defaultOrderType)
					);
			}

			$this->form->
				import($request->getGet())->
				importMore($request->getAttached())->
				importMore($request->getPost());

			$this->form->
				addRule(
					'offsetValues',
					Expression::eq(
						Expression::mod(
							FormField::create($this->pagerName),
							$this->pagerLimit
						),
						0
					)
				)->
				checkRules();

			if ($this->form->getErrors()) {
				FormRussianErrorBuilder::make($this->form);
			}

			return $this;
		}

		public function getFormErrors()
		{
			return $this->form->getErrors();
		}

		public function getFormValue($param)
		{
			return $this->form->getValue($param);
		}

		/**
		 * @return ListMakerHelper
		 */
		public function setCriteria(Criteria $criteria)
		{
			$this->criteria = $criteria;

			return $this;
		}


		/**
		 * @return ListMakerHelper
		 */
		public function fillCriteria()
		{
			$offset = $this->getOffset();
			$this->criteria->
				setOffset($offset)->
				setLimit($this->pagerLimit);

			if (!$this->form->getErrors()) {
				$this->
					makeOrderToCriteria()->
					makeFiltersToCriteria();
			}

			return $this;
		}

		/**
		 * @return ListMakerHelper
		 */
		public function makeResult()
		{
			if (!$this->projection) {
				$this->result = $this->criteria->getResult();
			} else {
				$list = $this->criteria->getCustomList();

				$countCriteria = clone $this->criteria;
				$countCriteria->setLimit(null);
				$countCriteria->setOffset(0);
				$countCriteria->dropOrder();
				$countCriteria->dropProjection();
				$countCriteria->addProjection(Projection::count('id', 'countId'));
				$countList = $countCriteria->getCustom(0);
				$count = $countList['countId'];

				$this->result = QueryResult::create()->
					setList($list)->
					setCount($count);
			}

			return $this;
		}

		/**
		 * @return ListMakerHelper
		 */
		public function makeCriteria(Criteria $criteria)
		{
			return $this->
				setCriteria($criteria)->
				fillCriteria()->
				makeResult();
		}

		public function getPager()
		{
			if ($this->result === null) {
				return null;
			}

			if ($this->pager) {
				return $this->pager;
			}

			$offset = $this->getOffset();

			$urlParams = array();
			foreach ($this->form->getPrimitiveList() as $primitiveName => $primitive) {
				$value = null;
				if ($primitive instanceof PrimitiveDate) {
					$value = $primitive->getValue();
					if ($value !== null) {
						$value = $value->toString();
					}
				} else {
					$value = $primitive->exportValue();
				}
				if ($value !== null) {
					$urlParams[$primitiveName] = $value;
				}
			}

			$this->pager = Pager::create($this->pagerLimit, "PagerDefault")->
				setRouter($this->routerName)->
				setTotalElement($this->result->getCount())->
				setCurrentPage($offset)->
				setUrlParameters(array_merge($urlParams, $this->baseUrlParams))->
				setUrlPrefix($this->pagerName);

			return $this->pager;
		}

		/**
		 * @return ModelAndView
		 */
		public function getMavFilter($additionalParams = array())
		{
			$baseUrl = RouterUrlHelper::url(
				array_merge(
					$this->fixUrlParams($this->baseUrlParams),
					$additionalParams
				),
				$this->routerName,
				true
			);

			$this->makeSelectorList();

			$model = Model::create()->
						set('form', $this->form)->
						set('propertyList', $this->propertyList)->
						set('selectorList', $this->selectorList)->
						set('formActionUrl', $baseUrl)->
						set('proto', $this->proto)->
						set('orderParamName', $this->orderParamName)->
						set('orderTypeName', $this->orderTypeName)->
						set('submitOnClick', $this->submitOnClick)->
						set('selfName', get_class($this));

			return ModelAndView::create()->
				setView($this->getViewDir().'/filter.form')->
				setModel($model);
		}

		public function getOrderParamMav($paramName, $additionalParams = array())
		{
			Assert::isString($paramName);
			if (
				isset($this->propertyList[$paramName])
				&& isset($this->propertyList[$paramName][self::OPTION_ORDERING])
			) {
				$currentOrder = $this->form->getSafeValue($this->orderParamName);
				$orderType = $this->form->getSafeValue($this->orderTypeName);
				$urlParams = array_merge($this->form->export(), $this->baseUrlParams);
				if ($currentOrder != $paramName || $orderType == self::ORDER_DESC) {
					$urlParams[$this->orderTypeName] = self::ORDER_ASC;
				} else {
					$urlParams[$this->orderTypeName] = self::ORDER_DESC;
				}
				$urlParams[$this->orderParamName] = $paramName;
				$switchUrl = RouterUrlHelper::url(
					array_merge($this->fixUrlParams($urlParams), $additionalParams),
					$this->routerName,
					true
				);
			} else {
				$currentOrder = null;
				$orderType = null;
				$switchUrl = null;
			}
			$description = (isset($this->propertyList[$paramName]) && isset($this->propertyList[$paramName][self::OPTION_DESCRIPTION]))
				? $this->propertyList[$paramName][self::OPTION_DESCRIPTION]
				: $paramName;

			return ModelAndView::create()->
				setView('ListMakerHelper/orderLink')->
				setModel(
					Model::create()->
						set('currentOrder', $currentOrder)->
						set('paramName', $paramName)->
						set('orderType', $orderType)->
						set('description', $description)->
						set('switchUrl', $switchUrl)->
						set('orderOnClick', $this->orderOnClick)
				);
		}

		/**
		 * @return ListMakerHelper
		 */
		protected function makeOrderToCriteria()
		{
			if ($this->projection) {
				$objectLink = $this->projection;
				$orderType = $this->form->getSafeValue($this->orderTypeName);
				$order = OrderBy::create($objectLink);
				if ($orderType == self::ORDER_ASC) {
					$order->asc();
				} else {
					$order->desc();
				}
				$this->criteria->addOrder($order);
			} elseif ($this->ftsIndexParam && ($words = $this->form->getValue($this->ftsIndexParam))) {
				$words = preg_split(
					'/ |\t|\,/isU',
					$words,
					null,
					PREG_SPLIT_NO_EMPTY
				);

				$this->criteria->add(
					Expression::fullTextAnd($this->propertyList[$this->ftsIndexParam][self::OPTION_FTS_INDEX], $words)
				);

				$this->criteria->addOrder(
					OrderBy::create(Expression::fullTextRankAnd($this->propertyList[$this->ftsIndexParam][self::OPTION_FTS_INDEX], $words))->
					desc()
				);
			} elseif ($this->form->primitiveExists($this->orderParamName)) {
				$orderParam = $this->form->getActualChoiceValue($this->orderParamName);
				$orderType = $this->form->getSafeValue($this->orderTypeName);

				$order = OrderBy::create($orderParam);
				if ($orderType == self::ORDER_ASC) {
					$order->asc();
				} else {
					$order->desc();
				}

				$this->criteria->addOrder($order);
			}

			return $this;
		}

		/**
		 * @return ListMakerHelper
		 */
		protected function makeFiltersToCriteria()
		{
			foreach ($this->propertyList as $propertyName => $options) {
				$this->makeFilterToCriteria($propertyName, $options);
			}

			return $this;
		}

		protected function makeFilterToCriteria($propertyName, $options)
		{
			if (isset($options[self::OPTION_FUNCTION_FIELD])) {
				$objectLink = $options[self::OPTION_FUNCTION_FIELD];
				$property = null;
				$propertyType = 'string';
			} else {
				$objectLink = isset($options[self::OPTION_OBJECT_LINK]) ? $options[self::OPTION_OBJECT_LINK] : $propertyName;
				$property = $this->getPropertyByName($objectLink, $this->proto);
				$propertyType = $property ? $property->getType() : null;
			}

			if (
				(isset($options[self::OPTION_FTS_INDEX]) && $options[self::OPTION_FTS_INDEX])
				|| (isset($options[self::OPTION_FUNCTION_FIELD]))
			) {
				/* */
			} elseif ($property === null) {
				throw new WrongArgumentException("property name {$propertyName} not exist for proto ".get_class($this->proto));
			}

			if (isset($options[self::OPTION_PROJECTION_GROUP])) {
				$this->criteria->
					addProjection(Projection::count('id', 'countId'))->
					addProjection(Projection::group($objectLink))->
					addProjection(Projection::property($objectLink, $propertyName));
			}

			if (
				isset($options[self::OPTION_FILTERABLE])
				&& (($primitiveValue = $this->form->getSafeValue($propertyName)) !== null)
				&& !isset($options[self::OPTION_FTS_INDEX])
			) {
				if (
					$propertyType == 'identifier'
					|| $propertyType == 'enumeration'
					|| $propertyType == 'integerIdentifier'
				) {
					if ($primitiveValue == self::OPERATOR_IS_NULL) {
						$this->criteria->add(Expression::isNull($objectLink));
					} else {
						$this->criteria->add(Expression::eq($objectLink, $primitiveValue));
					}
				} elseif ($propertyType == 'identifierList') {
					if ($primitiveValue == self::OPERATOR_IS_NULL) {
						$this->criteria->add(Expression::isNull($objectLink));
					} else {
						$this->criteria->add(Expression::eq($objectLink.'.id', $primitiveValue));
					}
				} elseif (
					$propertyType == 'integer'
				) {
					$this->criteria->add(Expression::eq($objectLink, $primitiveValue));
				} elseif (
					$propertyType == 'timestamp'
					|| $propertyType == 'date'
				) {
					$this->criteria->add(
						Expression::eq(
							SQLFunction::create('',$objectLink)->castTo('date'),
							SQLFunction::create('',$primitiveValue->toString())->castTo('date')
						)
					);
				} elseif ($propertyType == 'string') {
					$this->criteria->add(Expression::ilike($objectLink, '%'.$primitiveValue.'%'));
				} elseif ($propertyType == 'boolean') {
					if ($primitiveValue == true) {
						$this->criteria->add(Expression::isTrue($objectLink));
					} else {
						$this->criteria->add(Expression::isFalse($objectLink));
					}
				} else {
					throw new UnimplementedFeatureException("С данным типом LightMetaProperty не описана работа: {$propertyType}");
				}
			}

			if (
				isset($options[self::OPTION_FILTERABLE])
				&& isset($options[self::OPTION_FILTERABLE_MAX_MIN])
				&& (($primitiveValue = $this->form->getSafeValue($propertyName.'From')) !== null)
				&& !isset($options[self::OPTION_FTS_INDEX])
			) {
				if ($propertyType == 'timestamp' || $propertyType == 'date') {
					$this->criteria->add(
						Expression::gtEq(
							SQLFunction::create('',$objectLink)->castTo('date'),
							SQLFunction::create('',$primitiveValue->toString())->castTo('date')
						)
					);
				}
			}

			if (
				isset($options[self::OPTION_FILTERABLE])
				&& isset($options[self::OPTION_FILTERABLE_MAX_MIN])
				&& (($primitiveValue = $this->form->getSafeValue($propertyName.'To')) !== null)
				&& !isset($options[self::OPTION_FTS_INDEX])
			) {
				if ($propertyType == 'timestamp' || $propertyType == 'date') {
					$this->criteria->add(
						Expression::ltEq(
							SQLFunction::create('',$objectLink)->castTo('date'),
							SQLFunction::create('',$primitiveValue->toString())->castTo('date')
						)
					);
				}
			}

			return $this;
		}

		protected function addSelectorListIdentifier($paramName, $className)
		{
			$criteria = Criteria::create(ClassUtils::callStaticMethod($className.'::dao'));

			$propertyList = $this->propertyList[$paramName];
			$order = null;
			if (isset($propertyList[self::OPTION_FILTER_ORDER_FIELD])) {
				$order = OrderBy::create($propertyList[self::OPTION_FILTER_ORDER_FIELD]);
			} else {
				$order = OrderBy::create('id');
			}

			if ($order) {
				if (
					isset($propertyList[self::OPTION_FILTER_ORDER_FIELD_TYPE])
					&& $propertyList[self::OPTION_FILTER_ORDER_FIELD_TYPE] == 'desc'
				) {
					$order->desc();
				} else {
					$order->asc();
				}
				$criteria->addOrder($order);
			}

			$this->selectorList[$paramName] = $criteria->
				getList();

			return $this;
		}

		protected function addSelectorListEnumeration($paramName, $className)
		{
			$enumeration = new $className(ClassUtils::callStaticMethod($className.'::getAnyId'));
			$this->selectorList[$paramName] = $enumeration->getObjectList();

			return $this;
		}

		protected function makeSelectorList()
		{
			foreach ($this->selectorNameList as $paramName => $className) {
				$this->addSelectorListIdentifier($paramName, $className);
			}

			foreach ($this->selectorEnumerationNameList as $paramName => $className) {
				$this->addSelectorListEnumeration($paramName, $className);
			}

			return $this;
		}

		protected function fixUrlParams($params)
		{
			foreach ($params as $key => $val) {
				if (is_array($val)) {
					$params[$key] = implode('-', $val);
				}
			}
			return $params;
		}

		/**
		 * @return string
		 */
		protected function getViewDir()
		{
			return get_class($this);
		}

		/**
		 * PART OF TESTING OPTION PARAMS
		 */
		protected function testPropertyList()
		{
			$projectionFields = array();

			if (count($this->propertyList) == 0) {
				$this->testFailThrowing('global test', "You must set at least one property in array");
			}
			foreach ($this->propertyList as $propertyName => $options) {
				$this->testFilterableAndFilterableId($propertyName, $options);
				$this->testFtsCompability($propertyName, $options);
				$this->testFunctionFieldCompability($propertyName, $options);

				if (isset($options[self::OPTION_PROJECTION_GROUP])) {
					$projectionFields[] = $propertyName;
				}
			}

			if (count($projectionFields) >= 2) {
				$this->testFailThrowing('global test', "Now allowing create only one projection per perpert list. But in this list it is in next properties: ".implode(', ', $projectionFields));
			}
		}

		protected function testFilterableAndFilterableId(&$propertyName, array &$options)
		{
			if (
				!isset($options[self::OPTION_FILTERABLE])
				&& isset($options[self::OPTION_ID_FILTER])
			) {
				$this->testFailThrowing($propertyName, "OPTION_ID_FILTER not work without OPTION_FILTERABLE");
			}

			if (
				!isset($options[self::OPTION_FILTERABLE])
				&& isset($options[self::OPTION_FILTERABLE_MAX_MIN])
			) {
				$this->testFailThrowing($propertyName, "OPTION_FILTERABLE_MAX_MIN not work without OPTION_FILTERABLE");
			}

			return $this;
		}

		protected function testFtsCompability(&$propertyName, array &$options)
		{
			if (isset($options[self::OPTION_FTS_INDEX])) {
				if (isset($options[self::OPTION_ID_FILTER])) {
					$this->testFailThrowing($propertyName, "OPTION_FTS_INDEX not work with OPTION_ID_FILTER");
				}
				if (isset($options[self::OPTION_OBJECT_LINK])) {
					$this->testFailThrowing($propertyName, "OPTION_FTS_INDEX not work with OPTION_OBJECT_LINK");
				}
				if (isset($options[self::OPTION_FUNCTION_FIELD])) {
					$this->testFailThrowing($propertyName, "OPTION_FTS_INDEX not work with OPTION_FUNCTION_FIELD");
				}
				if (isset($options[self::OPTION_PROJECTION_GROUP])) {
					$this->testFailThrowing($propertyName, "OPTION_FTS_INDEX not work with OPTION_PROJECTION_GROUP");
				}
			}
			return $this;
		}

		protected function testFunctionFieldCompability(&$propertyName, array &$options)
		{
			if (isset($options[self::OPTION_FUNCTION_FIELD])) {
				if (isset($options[self::OPTION_ID_FILTER])) {
					$this->testFailThrowing($propertyName, "OPTION_FUNCTION_FIELD not work with OPTION_ID_FILTER");
				}
				if (isset($options[self::OPTION_OBJECT_LINK])) {
					$this->testFailThrowing($propertyName, "OPTION_FUNCTION_FIELD not work with OPTION_OBJECT_LINK");
				}
				if (isset($options[self::OPTION_FTS_INDEX])) {
					$this->testFailThrowing($propertyName, "OPTION_FUNCTION_FIELD not work with OPTION_FTS_INDEX");
				}
				if (isset($options[self::OPTION_PROJECTION_GROUP])) {
					$this->testFailThrowing($propertyName, "OPTION_FUNCTION_FIELD not work with OPTION_PROJECTION_GROUP");
				}
			}
			return $this;
		}

		protected function testFailThrowing($propertyName, $msg)
		{
			throw new WrongStateException($msg." (property: {$propertyName})");
		}
	}
?>