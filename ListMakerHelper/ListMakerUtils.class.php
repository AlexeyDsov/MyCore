<?php
/***************************************************************************
 *   Copyright (C) 2011 by Alexey Denisov                                  *
 *   alexeydsov@gmail.com                                                  *
 ***************************************************************************/

	class ListMakerUtils
	{
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
	}
?>