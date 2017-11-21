<?php
/**
 * [ COPYRIGHT HEADER ]
 */

namespace RDPayments;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class Log
{
	/**
	 * Writing to log file.
	 *
	 * @param string $log_file
	 * @param null   $text
	 * @param array  $data
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function write($log_file = 'global.log', $text = null, $data = [])
	{
		if (empty($text))
			return;

		$dateFormat = "Y-m-d H:i:s";
		$output     = "%datetime% [%level_name%] %message% %context%" . PHP_EOL;
		$formatter  = new LineFormatter($output, $dateFormat);

		// Create a handler
		$stream = new StreamHandler(JPATH_LIBRARIES . '/epayments/log/' . $log_file, Logger::DEBUG);
		$stream->setFormatter($formatter);

		// bind it to a logger object
		$securityLogger = new Logger('security');
		$securityLogger->pushHandler($stream);
		$securityLogger->info($text, $data);
	}
}