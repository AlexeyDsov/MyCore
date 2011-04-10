<?php
/***************************************************************************
 *   Copyright (C) 2011 by Alexey Denisov                                  *
 *   alexeydsov@gmail.com                                                  *
 ***************************************************************************/

	class ListMakerConstructor
	{
		protected $binaryExpressionMapping = array(
			ListMakerProperties::OPTION_FILTERABLE_EQ => BinaryExpression::EQUALS,
			ListMakerProperties::OPTION_FILTERABLE_GT => BinaryExpression::GREATER_THAN,
			ListMakerProperties::OPTION_FILTERABLE_GTEQ => BinaryExpression::GREATER_OR_EQUALS,
			ListMakerProperties::OPTION_FILTERABLE_LT => BinaryExpression::LOWER_THAN,
			ListMakerProperties::OPTION_FILTERABLE_LTEQ => BinaryExpression::LOWER_OR_EQUALS,

			ListMakerProperties::OPTION_FILTERABLE_ILIKE => BinaryExpression::ILIKE,
		);

		protected $postfixExpressionMapping = array(
			ListMakerProperties::OPTION_FILTERABLE_IS_NULL => PostfixUnaryExpression::IS_NULL,
			ListMakerProperties::OPTION_FILTERABLE_IS_NOT_NULL => PostfixUnaryExpression::IS_NOT_NULL,
			ListMakerProperties::OPTION_FILTERABLE_IS_TRUE => PostfixUnaryExpression::IS_TRUE,
			ListMakerProperties::OPTION_FILTERABLE_IS_NOT_TRUE => 'IS NOT TRUE',
			ListMakerProperties::OPTION_FILTERABLE_IS_FALSE => PostfixUnaryExpression::IS_FALSE,
			ListMakerProperties::OPTION_FILTERABLE_IS_NOT_FALSE => 'IS NOT FALSE',
		);

		/**
		 * @var AbstractProtoClass
		 */
		protected $proto = null;
		protected $propertyList = array();

		protected $offsetName = 'offset';
		protected $limit = 10;

		public function __construct(AbstractProtoClass $proto, array $propertyList)
		{
			$this->proto = $proto;
			$this->propertyList = $propertyList;
		}

		/**
		 * @return ListMakerConstructor
		 */
		public static function create(AbstractProtoClass $proto, array $propertyList)
		{
			return new self($proto, $propertyList);
		}

		/**
		 * @return string
		 */
		public function getOffsetName()
		{
			return $this->offsetName;
		}

		/**
		 * @var ListMakerConstructor
		 */
		public function setOffsetName($offsetName)
		{
			Assert::isString($offsetName);
			$this->offsetName = $offsetName;
			return $this;
		}

		/**
		 * @return string
		 */
		public function getLimit()
		{
			return $this->limit;
		}

		/**
		 * @var ListMakerConstructor
		 */
		public function setLimit($limit)
		{
			Assert::isPositiveInteger($limit);
			$this->limit = $limit;
			return $this;
		}

		/**
		 * @return QueryResult
		 */
		public function getResult(Form $form, Criteria $criteria = null)
		{
			Assert::isEmpty($form->getErrors(), 'Form must not has errors');
			if (!$criteria) {
				$criteria = $this->makeCriteria();
			} else {
				Assert::isTrue(
					$criteria->getProjection()->isEmpty(),
					'Criteria must not contain projections'
				);
			}

			$this->fillCriteria($criteria, $form);

			return $criteria->getResult();
		}

		/**
		 * @return ListMakerConstructor
		 */
		protected function fillCriteria(Criteria $criteria, Form $form)
		{
			$criteria->
				setOffset($form->getSafeValue($this->offsetName))->
				setLimit($this->limit);

			$formData = $form->export();

			$this->
				makeOrdersToCriteria($criteria, $formData)->
				makeFiltersToCriteria($criteria, $formData);

			return $this;
		}

		/**
		 * @return Criteria
		 */
		protected function makeCriteria()
		{
			$className = mb_substr(get_class($this->proto), 5);
			$dao = ClassUtils::callStaticMethod("$className::dao");

			return Criteria::create($dao);
		}

		/**
		 * @return ListMakerConstructor
		 */
		protected function makeOrdersToCriteria(Criteria $criteria, array $formData)
		{
			$orderList = array();

			$hasIdSort = false;
			foreach ($this->propertyList as $propertyName => $options) {
				$objectLink = isset($options[ListMakerProperties::OPTION_OBJECT_LINK])
					? $options[ListMakerProperties::OPTION_OBJECT_LINK]
					: $propertyName;
				$property = ListMakerUtils::getPropertyByName($objectLink, $this->proto);

				if (isset($formData[$propertyName]['order'])) {
					if ($objectLink == 'id') {
						$hasIdSort = true;
					}
					$order = OrderBy::create($objectLink);
					if (
						isset($formData[$propertyName]['sort'])
						&& $formData[$propertyName]['sort'] == ListMakerProperties::ORDER_DESC
					) {
						$order->desc();
					}

					$orderList[$formData[$propertyName]['order']] = $order;
				}
			}
			ksort($orderList);
			if (count($orderList) > 2) {
				$orderList = array_splice($orderList, 2);
			}

			foreach ($orderList as $order) {
				$criteria->addOrder($order);
			}
			if (!$hasIdSort) {
				$criteria->addOrder(OrderBy::create('id'));
			}

			return $this;
		}

		/**
		 * @return ListMakerConstructor
		 */
		protected function makeFiltersToCriteria(Criteria $criteria, array $formData)
		{
			foreach ($this->propertyList as $propertyName => $options) {
				if (isset($formData[$propertyName]) && is_array($formData[$propertyName])) {
					$this->makeFilterToCriteria($criteria, $propertyName, $formData[$propertyName]);
				}
			}

			return $this;
		}

		protected function makeFilterToCriteria(Criteria $criteria, $propertyName, $propertyData)
		{
			$options = $this->propertyList[$propertyName];
			$objectLink = isset($options[ListMakerProperties::OPTION_OBJECT_LINK])
				? $options[ListMakerProperties::OPTION_OBJECT_LINK]
				: $propertyName;
			$property = ListMakerUtils::getPropertyByName($objectLink, $this->proto);
			$propertyType = $property ? $property->getType() : null;

			if ($property === null) {
				$errorMsg = "property {$propertyName} not exist for proto ".get_class($this->proto);
				throw new WrongArgumentException($errorMsg);
			}

			if (isset($options[ListMakerProperties::OPTION_FILTERABLE])) {
				$filterList = $options[ListMakerProperties::OPTION_FILTERABLE];
				Assert::isArray($filterList, 'OPTION_FILTERABLE must be array');
				foreach ($filterList as $filterName) {
					if (isset($propertyData[$filterName])) {
						$value = $propertyData[$filterName];
						if (isset($this->binaryExpressionMapping[$filterName])) {
							$criteria->add($this->makeExpressionBinary($objectLink, $filterName, $value));
						} elseif (isset($this->postfixExpressionMapping[$filterName])) {
							$criteria->add($this->makeExpressionTernary($objectLink, $filterName));
						} else {
							throw new UnimplementedFeatureException('Unkown filterName: '.$filterName);
						}
					}
				}
			}

			return $this;
		}

		/**
		 * @param string $objectLink
		 * @param string $filterName
		 * @param string $value
		 * @return BinaryExpression
		 */
		protected function makeExpressionBinary($objectLink, $filterName, $value) {
			if (!isset($this->binaryExpressionMapping[$filterName])) {
				throw new UnimplementedFeatureException('Unkown binary filter: '.$filterName);
			}
			$logic = $this->binaryExpressionMapping[$filterName];
			return new BinaryExpression($objectLink, DBValue::create($value), $logic);
		}

		/**
		 * @param string $objectLink
		 * @param string $filterName
		 * @return PostfixUnaryExpression
		 */
		protected function makeExpressionTernary($objectLink, $filterName) {
			if (!isset($this->postfixExpressionMapping[$filterName])) {
				throw new UnimplementedFeatureException('Unkown ternary filter: '.$filterName);
			}
			$logic = $this->postfixExpressionMapping[$filterName];
			return new PostfixUnaryExpression($objectLink, $logic);
		}
	}
?>