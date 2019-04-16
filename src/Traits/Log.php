<?php
/**
 * [ COPYRIGHT HEADER ]
 */

namespace RDPayments\Traits;

defined('_JEXEC') or die('Restricted access');

jimport('joomla.filesystem.folder');

trait Log
{
	public static function message($id, $data, $order = false)
	{
		$config = \RDSubscriptions\Config::get();

		if ( ! $config->logging)
		{
			return;
		}

		$path = \JFactory::getConfig()->get('log_path') . '/rdsubs_' . strtolower($id) . '.php';

		$output = [];

		if ( ! is_file($path))
		{
			jimport('joomla.filesystem.folder');

			\JFolder::create(dirname($path));
			$output[] = '<?php die(\'Forbidden.\'); ?>';
			$output[] = '# Log: ' . ucwords(str_replace('_', ' ', $id));
			$output[] = '';
		}

		$date = new \JDate;

		$output[] = '[' . $date->format('Y-m-d H:i:s') . ']';
		$output[] = self::generateOutputFromData($data, $order);

		file_put_contents($path, implode("\n", $output) . "\n", FILE_APPEND);
	}

	private static function generateOutputFromData($data, $order = false, $indent_count = 1)
	{
		$output = [];

		$data = (array) $data;

		if ($order)
		{
			ksort($data);
		}

		foreach ($data as $key => $value)
		{
			$output[] = self::generateOutputFromKeyValue($key, $value, $order, $indent_count);
		}

		return implode("\n", $output);
	}

	private static function generateOutputFromKeyValue($key, $value, $order = false, $indent_count = 1)
	{
		$indent = str_repeat(' ', $indent_count * 4);

		if (is_array($value) || is_object($value))
		{

			return $indent . '[' . $key . '] ' . "\n"
				. self::generateOutputFromData($value, $order, $indent_count + 1);
		}

		return $indent . '[' . $key . '] ' . $value;
	}
}