<?php
/**
 * [ COPYRIGHT HEADER ]
 */

namespace RDPayments\Providers;

defined('_JEXEC') or die('Restricted access');

use RDPayments\Api\PaymentInterface;
use RDPayments\Payment;
use Srmklive\PayPal\Services\ExpressCheckout;

jimport('joomla.log.log');

class PayPal extends Payment implements PaymentInterface
{
	/**
	 * @var
	 * @since __DEPLOY_VERSION__
	 */
	protected $transactionToken;
	/**
	 * @var
	 * @since __DEPLOY_VERSION__
	 */
	protected $paymentRedirectUrl;
	/**
	 * @var
	 * @since __DEPLOY_VERSION__
	 */
	protected $return_url;
	/**
	 * @var
	 * @since __DEPLOY_VERSION__
	 */
	protected $username;
	/**
	 * @var
	 * @since __DEPLOY_VERSION__
	 */
	protected $signature;
	/**
	 * @var
	 * @since __DEPLOY_VERSION__
	 */
	protected $password;
	/**
	 * @var
	 * @since __DEPLOY_VERSION__
	 */
	protected $currency;
	/**
	 * @var
	 * @since __DEPLOY_VERSION__
	 */
	protected $redirectUrl;
	/**
	 * @var
	 * @since __DEPLOY_VERSION__
	 */
	private $paid_amount;
	/**
	 * @var
	 * @since __DEPLOY_VERSION__
	 */
	private $payment_state = false;
	/**
	 * @var
	 * @since __DEPLOY_VERSION__
	 */
	private $payment_message;
	/**
	 * @var
	 * @since __DEPLOY_VERSION__
	 */
	private $response;
	/**
	 * @var
	 * @since __DEPLOY_VERSION__
	 */
	private $redirect = true;

	/**
	 * @param array $request
	 *
	 * @return bool
	 * @throws \Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function startPayment($request = [])
	{
		// Instantiate the PayPal Express Checkout
		$paypalexpress = new ExpressCheckout;

		// Sending the credentals and settings
		$paypalexpress->setApiCredentials($this->getCredentials());

		// Retreive the payment details
		$response = $paypalexpress->setExpressCheckout($this->getOrderDetails());

		// setting some needed things for the transaction
		$this->paymentRedirectUrl = $response['paypal_link'];
		$this->transactionToken   = $response['TOKEN'];

		if (empty($response['TOKEN']))
		{
			$this->redirect = false;
		}

		return true;
	}

	/**
	 * @return array
	 *
	 * @since __DEPLOY_VERSION__
	 */
	private function getOrderDetails()
	{
		return [
			'invoice_id'          => $this->orderid,
			'invoice_description' => $this->description,
			'return_url'          => $this->redirectUrl,
			'cancel_url'          => $this->redirectUrl . '&state=cancelled',
			'total'               => $this->amount,
			'locale'              => empty($this->locale) ? 'en-GB' : $this->locale,
			'items'               => [],
		];
	}

	/**
	 * Requesting the payment at PayPal.
	 *
	 * @since __DEPLOY_VERSION__
	 *
	 * @param      $token
	 * @param null $payerid
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function getTransactionDetails($token, $payerid = null)
	{
		// Instantiate the PayPal Express Checkout
		$paypalexpress = new ExpressCheckout;

		// Sending the credentals and settings
		$paypalexpress->setApiCredentials($this->getCredentials());
		$response = $paypalexpress->getExpressCheckoutDetails($token);

		$this->paid_amount      = $response['AMT'];
		$this->transactionToken = $response['TOKEN'];

		if (strtolower($response['ACK']) === strtolower('Success'))
		{
			// Preparing the transaction details.
			$transaction    = array_merge($this->getOrderDetails(), $this->getCredentials());
			$this->response = $paypalexpress->doExpressCheckoutPayment($transaction, $token, $payerid);

			// Checking payment state and comparing the amounts
			if (strtolower($this->response['ACK']) === 'success' || strtolower($this->response['PAYMENTINFO_0_ACK']) === 'success' && $this->paid_amount == $transaction['total'])
			{
				$this->payment_state = true;
			}

			// Message for failed payments
			if ( ! $this->payment_state)
			{
				$this->payment_message = ! empty($response['L_LONGMESSAGE0']) ? $response['L_LONGMESSAGE0'] : 'Undefined Message';
			}
		}

		return true;
	}

	/**
	 * Setting the credentials for PayPal Express
	 *
	 * @return array
	 *
	 * @since __DEPLOY_VERSION__
	 */
	private function getCredentials()
	{
		$mode = $this->sandbox ? 'sandbox' : 'live';

		return [
			'mode'           => $mode,        // Can only be 'sandbox' Or 'live'. If empty or invalid, 'live' will be used.
			'sandbox'        => [
				'username'    => trim($this->username),
				'password'    => trim($this->password),
				'secret'      => trim($this->signature),
				'certificate' => '',
			],
			'live'           => [
				'username'    => trim($this->username),
				'password'    => trim($this->password),
				'secret'      => trim($this->signature),
				'certificate' => '',
			],
			'payment_action' => 'Sale',
			'currency'       => $this->currency,
			'notify_url'     => $this->redirectUrl,
			'validate_ssl'   => true,
		];
	}

	/**
	 * Perform an IPN request to check the payment.
	 *
	 * @param $request
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function ipn($request = [])
	{
		$this->log(json_encode($request));

		// Instantiate the Express Checkout.
		$provider = new ExpressCheckout;

		// Prepare the request for this IPN message.
		$request = array_merge($request, ['cmd' => '_notify-validate']);

		// Requesting the IPN result from PayPal.
		$response = (string) $provider->verifyIPN($request);

		$request_logger = http_build_query($request);
		$this->log($request_logger);

		$request_logger = http_build_query($response);
		$this->log($request_logger);

		if ($response === 'VERIFIED')
		{
			$this->log(json_encode($response));
		}
	}

	/**
	 * Adding items to the log file with JLog
	 *
	 * @param $message
	 *
	 * @since __DEPLOY_VERSION__
	 */
	private function log($message)
	{
		\RDMedia\Log::write($message, 'PP-IPN');
	}

	/**
	 * Returns the response of the payment.
	 *
	 * @return string
	 * @since __DEPLOY_VERSION__
	 */
	public function getPaymentProviderResponse()
	{
		return $this->response;
	}

	/**
	 * Returns tjo
	 *
	 * @return mixed
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getPaymentRedirectUrl()
	{
		return $this->paymentRedirectUrl;
	}

	/**
	 * Returning the payemnt state
	 *
	 * @return mixed
	 * @since __DEPLOY_VERSION__
	 */
	public function isPaid()
	{
		return $this->payment_state;
	}

	/**
	 * Can be requested when a transaction failed.
	 *
	 * @return mixed
	 * @since __DEPLOY_VERSION__
	 */
	public function failedPaymentMessage()
	{
		return $this->payment_message;
	}

	/**
	 * Returning the paid amount.
	 *
	 * @return mixed
	 * @since __DEPLOY_VERSION__
	 */
	public function getTransactionAmount()
	{
		return $this->paid_amount;
	}

	/**
	 * @return mixed
	 * @since __DEPLOY_VERSION__
	 */
	public function getOrderIdFromTransaction()
	{
		return $this->payment_state;
	}

	/**
	 * This function should return the TRIX from the payment information.
	 *
	 * @since __DEPLOY_VERSION__
	 *
	 * @return string
	 */
	public function getTrix()
	{
		return $this->transactionToken;
	}

	/**
	 * Let the customer redirect or not if the transaction went wrong.
	 *
	 * @return bool
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function canRedirect()
	{
		return $this->redirect;
	}
}