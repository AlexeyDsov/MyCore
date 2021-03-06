<?php
	class SimplePhpViewParametrizedToolkit extends SimplePhpViewParametrized
	{
		protected function trans($phraseName)
		{
			return $this->escape(
				$this->has('translator')
					? $this->get('translator')->trans($phraseName)
					: $phraseName
			);
		}
		
		protected function objectLink($object)
		{
			$this->view('Objects/SimpleObject/objectLink', array('object' => $object));
		}
		
		protected function getEnumerationNameList(AbstractProtoClass $proto, $options, $propertyName)
		{
			$objectLink = isset($options[ListMakerProperties::OPTION_OBJECT_LINK])
				? $options[ListMakerProperties::OPTION_OBJECT_LINK]
				: $propertyName;
			$property = ListMakerUtils::getPropertyByName($objectLink, $proto);
			
			$class = $property->getClassName();
			Assert::isInstance($class, 'Enumeration', 'Must be enumeration');
			$anyId = ClassUtils::callStaticMethod("{$class}::getAnyId");
			$exemplar = new $class($anyId);
			/* @var $exemplar Enumeration */
			
			return $exemplar->getNameList();
		}
		
		protected function isPrimitiveEnumeration(AbstractProtoClass $proto, $options, $propertyName)
		{
			$objectLink = isset($options[ListMakerProperties::OPTION_OBJECT_LINK])
				? $options[ListMakerProperties::OPTION_OBJECT_LINK]
				: $propertyName;
			$property = ListMakerUtils::getPropertyByName($objectLink, $proto);
			$propertyType = isset($options[ListMakerProperties::OPTION_PROPERTY_TYPE])
				? $options[ListMakerProperties::OPTION_PROPERTY_TYPE]
				: ($property ? $property->getType() : null);
			return $propertyType == 'enumeration';
		}

		protected function isTimePrimitive(Form $form, $propertyName, $filterName)
		{
			$timePrimitiveList = array('PrimitiveTimestamp');

			return in_array(get_class($form->getValue($propertyName)->get($filterName)), $timePrimitiveList);
		}

		protected function isDatePrimitive(Form $form, $propertyName, $filterName)
		{
			$datePrimitiveList = array('PrimitiveDate');

			return in_array(get_class($form->getValue($propertyName)->get($filterName)), $datePrimitiveList);
		}

		protected function getFilteredValue(Form $form, $propertyName, $filterName, $propertyData)
		{
			if (!isset($propertyData[$filterName])) {
				return '';
			}

			if ($this->isDatePrimitive($form, $propertyName, $filterName)) {
				$value = $propertyData[$filterName];
				return $value['year'] . '-' . $value['month'] . '-' . $value['day'];
			}

			return $this->escape($propertyData[$filterName]);
		}
	}
?>