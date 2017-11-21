<?php
/**
 * [ COPYRIGHT HEADER ]
 */

namespace RDPayments\Providers;

use Mollie_API_Client;
use RDPayments\Api\PaymentInterface;
use RDPayments\Log;
use RDPayments\Payment;

class Mollie extends Payment implements PaymentInterface
{
	/**
	 * @var null
	 *
	 * @since __DEPLOY_VERSION__
	 */
	private $method = null;
	/**
	 * @var array
	 *
	 * @since __DEPLOY_VERSION__
	 */
	private $methods = [];
	/**
	 * @var
	 *
	 * @since __DEPLOY_VERSION__
	 */
	private $issuer = null;

	/**
	 * @var Mollie_API_Client
	 *
	 * @since __DEPLOY_VERSION__
	 */
	private $mollie;
	/**
	 * @var
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $paymentRedirectUrl;
	/**
	 * @var Log
	 *
	 * @since __DEPLOY_VERSION__
	 */
	private $logger;
	/**
	 * @var string
	 *
	 * @since __DEPLOY_VERSION__
	 */
	private $logfile = 'mollie.log';
	/**
	 * @var
	 *
	 * @since __DEPLOY_VERSION__
	 */
	private $transactionDetails;

	/**
	 * Mollie constructor.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function __construct()
	{
		$this->mollie = new Mollie_API_Client;
		$this->logger = new Log;
	}

	/**
	 * Getting all issuers when using IDeal.
	 *
	 * @since __DEPLOY_VERSION__
	 *
	 * @return array
	 */
	public function getIssuers()
	{
		$issuer_list = [];

		$this->mollie->setApiKey($this->apiKey);

		foreach ($this->mollie->issuers->all() as $issuer)
		{
			$issuer_list[$issuer->id] = $issuer->name;
		}

		return $issuer_list;
	}

	/**
	 * @param null $issuer
	 *
	 * @since __DEPLOY_VERSION__
	 *
	 * @return $this
	 */
	public function setIssuer($issuer = null)
	{
		$this->issuer = ! empty($issuer) ? $issuer : $this->issuer;

		return $this;
	}

	/**
	 * @param null $method
	 *
	 * @since __DEPLOY_VERSION__
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
	 * @since __DEPLOY_VERSION__
	 */
	public function getAvailablemethods()
	{
		$methods_list = [];

		// Setting the Api Key for Mollie
		$this->mollie->setApiKey($this->apiKey);

		foreach ($this->mollie->methods->all() as $method)
		{
			$methods_list[$method->id] = $method->description;
		}

		return $methods_list;
	}

	/**
	 * Creating or starting the payment request.
	 *
	 * @since __DEPLOY_VERSION__
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
		$this->paymentRedirectUrl = $payment->getPaymentUrl();

		$this->logger->write($this->logfile, 'Redirecting', ['url' => $this->paymentRedirectUrl]);

		return $this;
	}

	/**
	 * Preparing a payment object for Mollie
	 *
	 * @since __DEPLOY_VERSION__
	 *
	 * @return array
	 */
	public function getPaymentObject()
	{
		$payment_object = [
			'amount'      => $this->amount,
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

		if ($this->method == 'ideal')
		{
			$payment_object['method'] = $this->method;
		}

		$this->logger->write($this->logfile, 'Created Payment Object', $payment_object);

		return $payment_object;
	}

	/**
	 * Returning a payment url if needed for the payment provider.
	 *
	 * @since __DEPLOY_VERSION__
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
	 * @since __DEPLOY_VERSION__
	 *
	 * @return \Mollie_API_Object_Payment|null
	 */
	public function getTransactionDetails($token)
	{
		if(empty($token))
		{
			return null;
		}

		$this->mollie->setApiKey($this->apiKey);
		$this->transactionDetails = $this->mollie->payments->get($token);

		// Write transaction data to log file
		$this->logger->write($this->logfile, 'IPN Processed', (array) $this->transactionDetails);

		return ! empty($this->transactionDetails) ? $this->transactionDetails : null;
	}

	/**
	 * Returning payment status -> paid yes/no
	 *
	 * @since __DEPLOY_VERSION__
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
	 * @since __DEPLOY_VERSION__
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
	 * @since __DEPLOY_VERSION__
	 *
	 * @return mixed
	 */
	public function getTransactionAmount()
	{
		return $this->transactionDetails->amount;
	}

	/**
	 * Getting the order id to process from the transaction
	 *
	 * @since __DEPLOY_VERSION__
	 *
	 * @return mixed
	 */
	public function getOrderIdFromTransaction()
	{
		return $this->transactionDetails->metadata->order_id;
	}
}
 
