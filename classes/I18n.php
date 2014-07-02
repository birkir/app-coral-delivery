<?php
 
class I18n extends Kohana_I18n
{
	public static function get($string, $lang = NULL)
	{
		if ( ! $lang)
		{
			// Use the global target language
			$lang = I18n::$lang;
		}

		// Load the translation table for this language
		$table = I18n::load($lang);

		// Add to dictionary in wanted language
		// ---
		if ( ! isset($table[$string]) AND $string !== '')
		{
			$dict = @file_get_contents(APPPATH.'cache/is.i18n');

			if (strpos($dict, "'".$string."' => '',") === FALSE)
			{
				$fh = fopen(APPPATH.'cache/is.i18n', 'a');
				fwrite($fh, "'".$string."' => '',\n");
				fclose($fh);
			}
		}
		// ---
		// endof

		// Return the translated string if it exists
		return isset($table[$string]) ? $table[$string] : $string;
	}
}