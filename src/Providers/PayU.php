<?php
/**
 * [ COPYRIGHT HEADER ]
 */

namespace RDPayments\Providers;

defined('_JEXEC') or die('Restricted access');

use OpenPayU_Configuration;
use OpenPayU_Exception;
use OpenPayU_Order;
use OpenPayU_Util;
use RDPayments\Api\PaymentInterface;
use RDPayments\Payment;

jimport('joomla.log.log');

class PayU extends Payment implements PaymentInterface
{
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
	private $redirect = false;
	/**
	 * @var
	 * @since [VERSION]
	 */
	private $trixId;
	/**
	 * @var
	 * @since [VERSION]
	 */
	private $paymentResponse;

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
		$environment = $this->sandbox ? 'sandbox' : 'secure';

		// Setting up the configuration.
		OpenPayU_Configuration::setEnvironment($environment);
		OpenPayU_Configuration::setMerchantPosId($request['pos_id']);
		OpenPayU_Configuration::setSignatureKey($request['pos_signature']);
		OpenPayU_Configuration::setOauthClientId($request['client_id']);
		OpenPayU_Configuration::setOauthClientSecret($request['client_secret']);

		try
		{
			$response = OpenPayU_Order::create($this->getPaymentRequest($request));
			$status   = OpenPayU_Util::statusDesc($response->getStatus());

			$this->paymentRedirectUrl = $response->getResponse()->redirectUri;

			if ($response->getStatus() == 'SUCCESS')
			{
				$this->redirect = true;
				$this->trixId   = $response->getResponse()->orderId;
			}
			else
			{
				$this->redirect = false;

				// Customer doesn't need to be redirected.
				$this->payment_message = $status;
			}
		}
		catch (OpenPayU_Exception $e)
		{
			$this->redirect = false;

			// Customer doesn't need to be redirected.
			$this->payment_message = (string) $e;
		}

		return true;
	}

	/**
	 * Preparing the payment request for PayU
	 *
	 * @return array
	 *
	 * @since [VERSION]
	 *
	 * @return array
	 */
	private function getPaymentRequest($request = [])
	{
		// Test orders can only being done with PLN currency
		$currency = $this->sandbox ? 'PLN' : $this->currency;

		$order[0] = [
			'name'      => $this->description . ' ' . $this->orderid,
			'unitPrice' => $this->amount,
			'quantity'  => 1,
		];

		return [
			'merchantPosId' => OpenPayU_Configuration::getOauthClientId() ? OpenPayU_Configuration::getOauthClientId() : OpenPayU_Configuration::getMerchantPosId(),
			'notifyUrl'     => $this->webhookUrl,
			'continueUrl'   => $this->redirectUrl,
			'customerIp'    => '127.0.0.1',
			'totalAmount'   => intval($this->amount),
			'extOrderId'    => $this->orderid,
			'description'   => $this->description,
			'products'      => $order,
			'currencyCode'  => $currency,
			'buyer'         => [
				'email'     => $request['email'],
				'firstName' => $request['firstname'],
				'lastName'  => $request['lastname'],
				'language'  => 'en',
			],
		];
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
	public function getTransactionDetails($token, $request = [])
	{
		$environment = $this->sandbox ? 'sandbox' : 'secure';

		OpenPayU_Configuration::setEnvironment($environment);
		OpenPayU_Configuration::setMerchantPosId($request['pos_id']);
		OpenPayU_Configuration::setSignatureKey($request['pos_signature']);
		OpenPayU_Configuration::setOauthClientId($request['client_id']);
		OpenPayU_Configuration::setOauthClientSecret($request['client_secret']);

		try
		{
			$response    = OpenPayU_Order::retrieve(stripslashes($token));
			$status_desc = OpenPayU_Util::statusDesc($response->getStatus());

			if ($response->getStatus() == 'SUCCESS')
			{
				// Getting order details
				$this->paymentResponse = $response->getResponse()->orders[0];
				$this->payment_state   = ($this->paymentResponse->status === 'COMPLETED') ? true : false;
				$this->paid_amount     = $this->paymentResponse->totalAmount / 100;
				$this->trixId          = $this->paymentResponse->extOrderId;
				$this->ordercode       = $this->paymentResponse->extOrderId;
			}
			else if ($response->getStatus() == 'PENDING')
			{
				// Capture payment
				$this->paid_amount     = $this->paymentResponse->totalAmount / 100;
				$this->trixId          = $this->paymentResponse->extOrderId;
				$this->ordercode       = $this->paymentResponse->extOrderId;
			}
			else
			{
				$this->payment_state   = false;
				$this->payment_message = $response->getStatus()  . ' : ' . $status_desc;
			}
		}
		catch (OpenPayU_Exception $e)
		{
			$this->payment_state   = false;
			$this->payment_message = $e->getCode() . ' : ' . $e->getMessage();
		}

		return true;
	}

	/**
	 * Perform an IPN request to check the payment.
	 *
	 * @param $request
	 *
	 * @since [VERSION]
	 */
	public function ipn($request = [])
	{
	}

	/**
	 * Check if the order is valid and if the customer can be redirected.
	 *
	 * @return mixed
	 * @since [VERSION]
	 */
	public function canRedirect()
	{
		return $this->redirect;
	}

	/**
	 * @return array
	 *
	 * @since [VERSION]
	 */
	public function getPaymentProviderResponse()
	{
		return json_encode($this->paymentResponse);
	}

	/**
	 * Adding items to the log file with JLog
	 *
	 * @param $message
	 *
	 * @since [VERSION]
	 */
	private function log($message)
	{
		\RDMedia\Log::write($message, 'PayU');
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
		return $this->trixId;
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
		return $this->trixId;
	}
}