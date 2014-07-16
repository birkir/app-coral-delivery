<?php defined('SYSPATH') or die('No direct script access.');

class ORM extends Kohana_ORM {

	/**
	 * Overload to parse float and integers
	 *
	 * @param array $values
	 * @return ORM
	 */
	protected function _load_values(array $values)
	{
		parent::_load_values($values);

		foreach ($values as $column => $value)
		{
			if (isset($this->_table_columns[$column]))
			{
				switch (Arr::get($this->_table_columns[$column], 'type'))
				{
					case 'int': $this->_object[$column] = intval($value); break;
					case 'float': $this->_object[$column] = floatval($value); break;
				}
			}
		}

		return $this;
	}
}