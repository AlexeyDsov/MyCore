<?php
/***************************************************************************
 *   Copyright (C) 2011 by Alexey Denisov                                  *
 *   alexeydsov@gmail.com                                                  *
 ***************************************************************************/

	class ListMakerFormBuilder
	{
		/**
		 * @var AbstractProtoClass
		 */
		protected $proto = null;
		protected $propertyList = array();

		protected $offsetName = 'offset';

		public function __construct(AbstractProtoClass $proto, array $propertyList)
		{
			$this->proto = $proto;
			$this->propertyList = $propertyList;
		}

		/**
		 * @return ListMakerFormBuilder
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
		 * @var ListMakerFormBuilder
		 */
		public function setOffsetName($offsetName)
		{
			Assert::isString($offsetName);
			$this->offsetName = $offsetName;
			return $this;
		}

		/**
		 * @return ListMakerFormBuilder
		 */
		public function buildForm(Form $form = null)
		{
			if ($form === null) {
				$form = Form::create();
			}
			$this
				->initForm($form)
				->fillForm($form);

			return $form;
		}

		/**
		 * @param Form $form
		 * @return ListMakerFormBuilder
		 */
		protected function initForm(Form $form)
		{
			if ($form->getErrors() || $form->export()) {
				throw new WrongStateException('Form Already Imported');
			}

			return $this;
		}

		/**
		 * @param Form $form
		 * @return ListMakerFormBuilder
		 */
		protected function fillForm(Form $form)
		{
			$form->add(
				Primitive::integer($this->offsetName)->
					setMin(0)->
					setDefault(0)
			);

			foreach ($this->propertyList as $propertyName => $options) {
				if ($propertyForm = $this->makePropertyForm($propertyName)) {
					$propertyPrimitive = new PrimitiveFormCustom($propertyName);
					$propertyPrimitive->setForm($propertyForm);
					$form->add($propertyPrimitive);
				}
			}

			return $this;
		}

		/**
		 * @param string $propertyName
		 * @return Form
		 */
		protected function makePropertyForm($propertyName) {
			$options = $this->propertyList[$propertyName];
			$objectLink = isset($options[ListMakerProperties::OPTION_OBJECT_LINK])
				? $options[ListMakerProperties::OPTION_OBJECT_LINK]
				: $propertyName;
			$property = ListMakerUtils::getPropertyByName($objectLink, $this->proto);
			$propertyType = $property ? $property->getType() : null;

			$prmitiveList = array();
			if (isset($options[ListMakerProperties::OPTION_FILTERABLE])) {
				$filters = $options[ListMakerProperties::OPTION_FILTERABLE];
				Assert::isArray($filters, "value for OPTION_FILTERABLE must be array");

				foreach ($filters as $filterName) {
					switch ($filterName) {
						case ListMakerProperties::OPTION_FILTERABLE_EQ:
						case ListMakerProperties::OPTION_FILTERABLE_GT:
						case ListMakerProperties::OPTION_FILTERABLE_GTEQ:
						case ListMakerProperties::OPTION_FILTERABLE_LT:
						case ListMakerProperties::OPTION_FILTERABLE_LTEQ:
						case ListMakerProperties::OPTION_FILTERABLE_ILIKE:
							$prmitiveList[] = $this->makePrimitiveComparison($filterName, $propertyType);
							break;
						case ListMakerProperties::OPTION_FILTERABLE_IS_NULL:
						case ListMakerProperties::OPTION_FILTERABLE_IS_NOT_NULL:
						case ListMakerProperties::OPTION_FILTERABLE_IS_TRUE:
						case ListMakerProperties::OPTION_FILTERABLE_IS_NOT_TRUE:
						case ListMakerProperties::OPTION_FILTERABLE_IS_FALSE:
						case ListMakerProperties::OPTION_FILTERABLE_IS_NOT_FALSE:
							$prmitiveList[] = $this->makePrimitiveTernaryLogic($filterName);
							break;
						default:
							throw new UnimplementedFeatureException('Unkown filter name: '.$filterName);
					}
				}
			}

			if (isset($options[ListMakerProperties::OPTION_ORDERING])) {
				$prmitiveList[] = Primitive::integer('order')->setMin(1);
				$prmitiveList[] = Primitive::plainChoice('sort')->
					setList(array(ListMakerProperties::ORDER_ASC, ListMakerProperties::ORDER_DESC))->
					setDefault(ListMakerProperties::ORDER_ASC);
			}

			if (empty($prmitiveList)) {
				return null;
			}

			$form = Form::create();
			foreach ($prmitiveList as $primitive) {
				$form->add($primitive);
			}

			return $form;
		}

		protected function makePrimitiveComparison($filterName, $propertyType) {
			switch ($propertyType) {
				case 'identifier':
				case 'identifierList':
				case 'integerIdentifier':
				case 'enumeration':
				case 'integer':
					return Primitive::integer($filterName);
				case 'timestamp':
					return Primitive::timestamp($filterName);
				case 'date':
					return Primitive::date($filterName);
				case 'string':
				case 'scalarIdentifier':
					return Primitive::string($filterName);
				case 'boolean':
					$errorMsg = "Для propertyType 'boolean' операции сравнения невозможны";
					throw new UnimplementedFeatureException($errorMsg);
				default:
					$errorMsg = "С данным типом LightMetaProperty не описана работа: {$propertyType}";
					throw new UnimplementedFeatureException($errorMsg);
			}
			Assert::isUnreachable();
		}

		protected function makePrimitiveTernaryLogic($filterName) {
			return Primitive::boolean($filterName);
		}
	}
?>