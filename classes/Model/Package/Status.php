<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Package Status Model
 *
 * @package    Coral
 * @category   Model
 * @author     Birkir Gudjonsson (birkir.gudjonsson@gmail.com)
 * @copyright  (c) 2014 Birkir Gudjonsson
 * @license    http://kohanaframework.org/licence
 */
class Model_Package_Status extends ORM {

	/**
	 * @var array Belongs-to relationships
	 */
	protected $_belongs_to = array(
		'package' => array()
	);

	/**
	 * Check if hook trigger validates item status
	 *
	 * @param  array Hook trigger array
	 * @return bool
	 */
	public function validate_trigger($item)
	{
		// Array of valid flags
		$valids = [];

		foreach ($item->children as $item)
		{
			if ($item->type === 'group')
			{
				// Append valid flags recursive
				$valids[] = $this->validate_trigger($item);

				continue;
			}

			// Get value, operator and test
			$value = $this->{$item->field};
			$operator = $item->operator;
			$test = $item->expression;

			if ($item->field === 'timestamp')
			{
				// Parse value and test as unix timestamp
				$value = strtotime($value);
				$test = strtotime($test);
			}

			if ($operator === '~=')
			{
				// Check if value regex matches test
				$valids[] = preg_match('#'.$test.'#s', $value);
			}			     	
			else if ($operator === '=')
			{
				$valids[] = (UTF8::strtolower($test) === UTF8::strtolower($value));
			}
			else if ($operator === '<')
			{
				$valids[] = ($value < $test);
			}
			else if ($operator === '<=')
			{
				$valids[] = ($value <= $test);
			}
			else if ($operator === '>')
			{
				$valids[] = ($value > $test);
			}
			else if ($operator === '>=')
			{
				$valids[] = ($value >= $test);
			}
		}

		foreach ($valids as $valid)
		{
			if ( ! $valid AND ($item->operator === 'AND'))
				return FALSE;

			if ($valid AND ($item->operator === 'OR'))
				return TRUE;
		}

		return TRUE;
	}

} // End Package Status Model