<?php
/**
 * [ COPYRIGHT HEADER ]
 */

namespace RDPayments\Providers;

defined('_JEXEC') or die('Restricted access');

use Mollie_API_Client;
use RDPayments\Api\PaymentInterface;
use RDPayments\Payment;

class Mollie extends Payment implements PaymentInterface
{
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
	private $mollie;
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
	private $transactionDetails;

	/**
	 * Mollie constructor.
	 *
	 * @since [VERSION]
	 */
	public function __construct()
	{
		$this->mollie = new Mollie_API_Client;
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
	 * @since [VERSION]
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

		foreach ($this->mollie->methods->all() as $method)
		{
			$methods_list[$method->id] = ['description' => $method->description, 'image' => $method->image->normal];
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
		$this->paymentRedirectUrl = $payment->getPaymentUrl();

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

		if (!empty($this->method))
		{
			$payment_object['method'] = $this->method;
		}

		return $payment_object;
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
		if(empty($token))
		{
			return null;
		}

		$this->mollie->setApiKey($this->apiKey);
		$this->transactionDetails = $this->mollie->payments->get($token);

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
		return $this->transactionDetails->amount;
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
}
 
