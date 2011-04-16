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

	class ListMakerProperties
	{
		const OPTION_ORDERING = 'ordering';
		const OPTION_DEFAULT_ORDER = 'defaultOrder';
		const OPTION_FILTERABLE = 'filterable';
		const OPTION_FILTERABLE_EQ = 'eq';
		const OPTION_FILTERABLE_IN = 'in';
		const OPTION_FILTERABLE_GT = 'gt';
		const OPTION_FILTERABLE_GTEQ = 'gteq';
		const OPTION_FILTERABLE_LT = 'lt';
		const OPTION_FILTERABLE_LTEQ = 'lteq';
		const OPTION_FILTERABLE_IS_NULL = 'isNull';
		const OPTION_FILTERABLE_IS_NOT_NULL = 'isNotNull';
		const OPTION_FILTERABLE_IS_TRUE = 'isTrue';
		const OPTION_FILTERABLE_IS_NOT_TRUE = 'isNotTrue';
		const OPTION_FILTERABLE_IS_FALSE = 'isFalse';
		const OPTION_FILTERABLE_IS_NOT_FALSE = 'isNotFalse';
		const OPTION_FILTERABLE_ILIKE = 'ilike';
		const OPTION_DESCRIPTION = 'description';
		const OPTION_OBJECT_LINK = 'objectLink';

		const ORDER_ASC = 'asc';
		const ORDER_DESC = 'desc';
	}
?>