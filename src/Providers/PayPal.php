<?php
/**
 * [ COPYRIGHT HEADER ]
 */

namespace RDPayments\Providers;

defined('_JEXEC') or die('Restricted access');

use RDPayments\Api\PaymentInterface;
use RDPayments\Payment;
use RDPayments\Traits\Log;
use RDPayments\Ipn\PayPal as IpnListener;
use Srmklive\PayPal\Services\ExpressCheckout;

jimport('joomla.log.log');

class PayPal extends Payment implements PaymentInterface
{
	use Log;

	/**
	 * @var
	 * @since [VERSION]
	 */
	protected $transactionToken;
	/**
	 * @var
	 * @since [VERSION]
	 */
	protected $paymentRedirectUrl;
	/**
	 * @var
	 * @since [VERSION]
	 */
	protected $return_url;
	/**
	 * @var
	 * @since [VERSION]
	 */
	protected $username;
	/**
	 * @var
	 * @since [VERSION]
	 */
	protected $signature;
	/**
	 * @var
	 * @since [VERSION]
	 */
	protected $password;
	/**
	 * @var
	 * @since [VERSION]
	 */
	protected $currency;
	/**
	 * @var
	 * @since [VERSION]
	 */
	protected $redirectUrl;
	/**
	 * @var
	 * @since [VERSION]
	 */
	private $paid_amount;
	/**
	 * @var
	 * @since [VERSION]
	 */
	private $payment_state = false;
	/**
	 * @var
	 * @since [VERSION]
	 */
	private $payment_message;
	/**
	 * @var
	 * @since [VERSION]
	 */
	private $response = null;
	/**
	 * @var
	 * @since [VERSION]
	 */
	private $redirect = true;
	/**
	 * @var
	 * @since [VERSION]
	 */
	private $billing_type = 'MerchantInitiatedBilling';

	/**
	 * @param array $request
	 *
	 * @return bool
	 * @throws \Exception
	 *
	 * @since [VERSION]
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
	 * @since [VERSION]
	 */
	private function getOrderDetails()
	{
		$landingpage = empty($this->landingpage) ? 'Login' : $this->landingpage;

		return [
			'invoice_id'          => $this->orderid,
			'invoice_description' => $this->description,
			'return_url'          => $this->redirectUrl,
			'cancel_url'          => $this->redirectUrl . '&state=cancelled',
			'total'               => $this->amount,
			'locale'              => empty($this->locale) ? 'en-GB' : $this->locale,
			'currency'            => empty($this->currency) ? 'EUR' : $this->currency,
			'landingpage'         => $landingpage,
			'items'               => $this->cart_items,
		];
	}

	/**
	 * Setting a custom landingpage for the customer.
	 *
	 * @param $page
	 *
	 * @return $this
	 */
	public function setLandingPage($page)
	{
		$this->landingpage = ($page == 'creditcard' ? 'Billing' : 'Login');

		return $this;
	}

	/**
	 * Requesting the payment at PayPal.
	 *
	 * @since [VERSION]
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

		// Setting some things which can be requested later on.
		$this->paid_amount      = isset($response['PAYMENTINFO_0_AMT']) ? $response['PAYMENTINFO_0_AMT'] : 0;
		$this->transactionToken = isset($response['PAYMENTINFO_0_TRANSACTIONID']) ? $response['PAYMENTINFO_0_TRANSACTIONID'] : null;
		$this->payment_message  = ! empty($response['L_LONGMESSAGE0']) ? $response['L_LONGMESSAGE0'] : '';

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
		}

		return true;
	}

	/**
	 * Setting the credentials for PayPal Express
	 *
	 * @return array
	 *
	 * @since [VERSION]
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
			'billing_type'   => $this->billing_type,
			'validate_ssl'   => true,
		];
	}

	/**
	 * Perform an IPN request to check the payment.
	 *
	 * @param $request
	 *
	 * @since [VERSION]
	 */
	public function ipn($response = [], $sandbox = true)
	{
		Log::message('PayPalExpress', 'New IPN request received.');

		$listener = new IpnListener;

		$listener->use_sandbox     = $sandbox;
		$listener->use_curl        = true;
		$listener->follow_location = false;
		$listener->timeout         = 30;
		$listener->verify_ssl      = false;

		if ($verified = $listener->processIpn())
		{
			Log::message('PayPalExpress', 'IPN Verified');
			Log::message('PayPalExpress', $listener->getRawPostData());

			$this->response = $listener->getRawPostData();

			return true;
		}

		Log::message('PayPalExpress', 'IPN Not Verified');

		return false;
	}

	/**
	 * Refund the money to the customer.
	 * To issue partial refund, you must provide the amount as well for refund
	 *
	 * @param $transaction
	 * @param $amount
	 */
	public function refund($transaction, $amount = null)
	{
		if ($amount)
		{
			$amount = number_format($amount, 2, '.', '');
		}

		// Instantiate the PayPal Express Checkout
		$paypalexpress = new ExpressCheckout;

		// Sending the credentals and settings
		$paypalexpress->setApiCredentials($this->getCredentials());
		$paypalexpress->setCurrency($this->currency);

		$response = $paypalexpress->refundTransaction($transaction, $amount);

		if ( ! isset($responseNvp['ACK']) || $responseNvp['ACK'] != 'Success')
		{
			// Seetting error message
			$this->payment_message = $response['L_ERRORCODE0'] . ':' . $response['L_SHORTMESSAGE0'] . ' - ' . $response['L_LONGMESSAGE0'];

			// Paymentstate = false
			$this->payment_state = false;

			return true;
		}

		$this->payment_message = JText::sprintf('PLG_RDMEDIA_PAYPAL_REFUNDED', $data['transaction'], $response['REFUNDTRANSACTIONID']);

		return true;
	}

	/**
	 * Returns the response of the payment.
	 *
	 * @return string
	 * @since [VERSION]
	 */
	public function getPaymentProviderResponse($log = false)
	{
		if ($log)
		{
			Log::message('PayPalExpress', $this->response);
		}

		return $this->response;
	}

	/**
	 * Returns tjo
	 *
	 * @return mixed
	 *
	 * @since [VERSION]
	 */
	public function getPaymentRedirectUrl()
	{
		return $this->paymentRedirectUrl;
	}

	/**
	 * Returning the payemnt state
	 *
	 * @return mixed
	 * @since [VERSION]
	 */
	public function isPaid()
	{
		return $this->payment_state;
	}

	/**
	 * Can be requested when a transaction failed.
	 *
	 * @return mixed
	 * @since [VERSION]
	 */
	public function failedPaymentMessage()
	{
		return $this->payment_message;
	}

	/**
	 * Returning the paid amount.
	 *
	 * @return mixed
	 * @since [VERSION]
	 */
	public function getTransactionAmount()
	{
		return $this->paid_amount;
	}

	/**
	 * @return mixed
	 * @since [VERSION]
	 */
	public function getOrderIdFromTransaction()
	{
		return $this->payment_state;
	}

	/**
	 * This function should return the TRIX from the payment information.
	 *
	 * @since [VERSION]
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
	 * @since [VERSION]
	 */
	public function canRedirect()
	{
		return $this->redirect;
	}
}