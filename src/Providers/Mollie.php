<?php
/**
 * [ COPYRIGHT HEADER ]
 */

namespace RDPayments\Providers;

defined('_JEXEC') or die('Restricted access');

use Mollie\Api\MollieApiClient;
use RDPayments\Api\PaymentInterface;
use RDPayments\Payment;
use RDPayments\Traits\Log;

class Mollie extends Payment implements PaymentInterface
{
	use Log;

	/**
	 * @var null
	 *
	 * @since [VERSION]
	 */
	private $method = null;
	/**
	 * @var array
	 *
	 * @since [VERSION]
	 */
	private $methods = [];
	/**
	 * @var
	 *
	 * @since [VERSION]
	 */
	private $issuer = null;

	/**
	 * @var Mollie_API_Client
	 *
	 * @since [VERSION]
	 */
	protected $mollie;
	/**
	 * @var
	 *
	 * @since [VERSION]
	 */
	protected $paymentRedirectUrl;
	/**
	 * @var
	 *
	 * @since [VERSION]
	 */
	protected $transactionDetails;
	/**
	 * @var
	 *
	 * @since [VERSION]
	 */
	private $issuers;

	/**
	 * Mollie constructor.
	 *
	 * @since [VERSION]
	 */
	public function __construct()
	{
		$this->mollie = new MollieApiClient;
	}

	/**
	 * Getting all issuers when using IDeal.
	 *
	 * @since [VERSION]
	 *
	 * @return array
	 */
	public function getIssuers()
	{
		if ( ! empty($this->issuers))
		{
			return $this->issuers;
		}

		$issuer_list = [];

		$this->mollie->setApiKey($this->apiKey);

		foreach ($this->mollie->issuers->all() as $issuer)
		{
			$issuer_list[$issuer->id] = $issuer->name;
		}

		return $issuer_list;
	}

	/**
	 * Setting issuers for the payment method/.
	 *
	 * @since [VERSION]
	 *
	 * @return array
	 */
	private function setIssuers($method = null, $issuers = [])
	{
		foreach ($issuers as $issuer)
		{
			$this->issuers[$method][$issuer->id] = $issuer->name;
		}

		return true;
	}

	/**
	 * @param null $issuer
	 *
	 * @since [VERSION]
	 *
	 * @return $this
	 */
	public function setIssuer($issuer = null)
	{
		$this->issuer[] = ! empty($issuer) ? $issuer : $this->issuer;

		return $this;
	}

	/**
	 * @param null $method
	 *
	 * @since [VERSION]
	 *
	 * @return $this
	 */
	public function setMethod($method = null)
	{
		$this->method = ! empty($method) ? $method : $this->method;

		return $this;
	}

	/**
	 * Getting available payment methods.
	 *
	 * @since [VERSION]
	 */
	public function getAvailablemethods()
	{
		$methods_list = [];

		// Setting the Api Key for Mollie
		$this->mollie->setApiKey($this->apiKey);

		// Getting methods from the API:
		$methods = $this->mollie->methods->all(['include' => 'issuers']);

		foreach ($methods as $method)
		{
			$methods_list[$method->id] = ['description' => $method->description, 'size1x' => $method->image->size2x, 'size2x' => $method->image->size2x];

			if (isset($method->issuers) && ! empty($method->issuers))
			{
				$this->setIssuers($method->id, $method->issuers);
			}
		}

		return $methods_list;
	}

	/**
	 * Creating or starting the payment request.
	 *
	 * @since [VERSION]
	 *
	 * @param array $request
	 *
	 * @return $this
	 */
	public function startPayment($request = [])
	{
		$this->mollie->setApiKey($this->apiKey);

		// Creating a payment object for Mollie.
		$payment = $this->mollie->payments->create($this->getPaymentObject());

		// Getting the payment URL.
		$this->paymentRedirectUrl = $payment->getCheckoutUrl();

		// Setting the payment ID
		$this->payment_id = $payment->id;

		return $this;
	}

	/**
	 * Preparing a payment object for Mollie
	 *
	 * @since [VERSION]
	 *
	 * @return array
	 */
	public function getPaymentObject()
	{
		$payment_object = [
			"amount"      => [
				"currency" => 'EUR',
				"value"    => $this->amount,
			],
			'description' => $this->description,
			'redirectUrl' => $this->redirectUrl,
			'webhookUrl'  => $this->webhookUrl,
			'locale'      => ! empty($this->locale) ? $this->locale : 'en',
			'metadata'    => [
				'order_id' => $this->orderid,
			],
		];

		if ($this->method == 'ideal' && ! empty($this->issuer))
		{
			$payment_object['method'] = $this->method;
			$payment_object['issuer'] = $this->issuer;
		}

		if ( ! empty($this->method))
		{
			$payment_object['method'] = $this->method;
		}

		// Log the transaction to the log system
		Log::message('Mollie', $payment_object, $this->orderid);

		return $payment_object;
	}

	/**
	 * @param $transaction_id
	 *
	 * @throws \Mollie\Api\Exceptions\ApiException
	 *
	 * @since   1.5.0
	 * @version [VERSION]
	 */
	public function refund($transaction_id, $amount = null)
	{
		$this->mollie->setApiKey($this->apiKey);

		$transaction = $this->getTransactionDetails($transaction_id);

		if (empty($transaction))
		{
			return false;
		}

		// Is this a partial refund or a full refund.
		$amount = ! empty($amount) ? $amount : $this->getTransactionAmount();

		// Properly formatting the amount for the Mollie API.
		$amount = number_format($amount, 2, '.', '');

		if ( ! $this->isRefundPossible($amount))
		{
			return false;
		}

		try
		{
			$refund = $transaction->refund([
				'amount' => [
					'currency' => $this->getUsedCurrency(),
					'value'    => $amount,
				],
			]);

			return true;
		}
		catch (Mollie_API_Exception $e)
		{
			Log::message('Mollie', $e->getMessage());
			JFactory::getApplication()->enqueueMessage($e->getMessage(), 'error');

			return false;
		}
	}

	/**
	 * Getting the maximum amount to refund from the transaction.
	 * Refund maximum amount to refund = transaction amount + 25.
	 * EG: Order total: 25.00 (maximum refund amount = 25 +25 = 50)
	 *
	 * @return mixed
	 * @since   1.5.0
	 * @version [VERSION]
	 */
	private function isRefundPossible($amount)
	{
		$amount  = $amount * 100;
		$maximum = $this->transactionDetails->amountRemaining->value * 100;

		return ($amount <= $maximum) ? true : false;
	}

	/**
	 * Getting the used currecy for refunds.
	 *
	 * @return mixed
	 * @since   1.5.0
	 * @version [VERSION]
	 */
	public function getUsedCurrency()
	{
		return $this->transactionDetails->amount->currency;
	}

	/**
	 * Returning the payment ID
	 *
	 * @since [VERSION]
	 *
	 * @return mixed
	 */
	public function getPaymentId()
	{
		return isset($this->payment_id) ? $this->payment_id : 0;
	}

	/**
	 * Returning a payment url if needed for the payment provider.
	 *
	 * @since [VERSION]
	 *
	 * @return mixed
	 */
	public function getPaymentRedirectUrl()
	{
		return $this->paymentRedirectUrl;
	}

	/**
	 * Gettin gthe transaction details based on the transaction id from Mollie.
	 *
	 * @param $token
	 *
	 * @since [VERSION]
	 *
	 * @return \Mollie_API_Object_Payment|null
	 */
	public function getTransactionDetails($token, $transid = null)
	{
		if (empty($token))
		{
			return null;
		}

		try
		{
			$this->mollie->setApiKey($this->apiKey);
			$this->transactionDetails = $this->mollie->payments->get($token);
		}
		catch (Mollie_API_Exception $e)
		{
			JFactory::getApplication()->enqueueMessage($e->getMessage(), 'error');
		}

		return ! empty($this->transactionDetails) ? $this->transactionDetails : null;
	}

	/**
	 * Returning payment status -> paid yes/no
	 *
	 * @since [VERSION]
	 *
	 * @return int
	 */
	public function isPaid()
	{
		return ($this->transactionDetails->status == 'paid') ? 1 : 0;
	}

	/**
	 * Getting the real payment state.
	 *
	 * @since [VERSION]
	 *
	 * @return mixed
	 */
	public function getTransactionState()
	{
		return $this->transactionDetails->status;
	}

	/**
	 * Getting the paid amount so it can be checked.
	 *
	 * @since [VERSION]
	 *
	 * @return mixed
	 */
	public function getTransactionAmount()
	{
		return $this->transactionDetails->amount->value;
	}

	/**
	 * Getting the order id to process from the transaction
	 *
	 * @since [VERSION]
	 *
	 * @return mixed
	 */
	public function getOrderIdFromTransaction()
	{
		return $this->transactionDetails->metadata->order_id;
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
		return $this->transactionDetails->id;
	}

	/**
	 * This function should return theuse rpayment method which has been chosen by the client.
	 *
	 * @return mixed
	 * @since [VERSION]
	 */
	public function getPaymentMethod()
	{
		return $this->transactionDetails->method;
	}
}
 
