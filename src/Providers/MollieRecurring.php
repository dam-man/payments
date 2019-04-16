<?php
/**
 * [ COPYRIGHT HEADER ]
 */

namespace RDPayments\Providers;

use RDPayments\Traits\Log;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Payment;
use Mollie_API_Object_Payment;

class MollieRecurring extends Mollie
{
	use Log;

	/**
	 * Setting logging to false.
	 *
	 * @var bool
	 */
	protected $logger = false;
	/**
	 * Setting the provider for the logger.
	 *
	 * @var string
	 */
	protected $provider = 'MollieRecurring';
	/**
	 * Setting recurring option to false
	 *
	 * @var bool
	 * @since   1.0.0
	 * @version [VERSION]
	 */
	protected $recurring = false;
	/**
	 * The email address of this client
	 *
	 * @var string
	 * @since   1.0.0
	 * @version [VERSION]
	 */
	protected $email;
	/**
	 * The name of this client
	 *
	 * @var string
	 * @since   1.0.0
	 * @version [VERSION]
	 */
	protected $name;
	/**
	 * This will be filled with the Mollie Customer Id.
	 *
	 * @var string
	 * @since   1.0.0
	 * @version [VERSION]
	 */
	protected $customerId;
	/**
	 * This will be filled with the Mollie Customer Id.
	 *
	 * @var string
	 * @since   1.0.0
	 * @version [VERSION]
	 */
	protected $payment_id = null;
	/**
	 * This will be filled with the Mollie Subscription ID.
	 *
	 * @var string
	 * @since   1.0.0
	 * @version [VERSION]
	 */
	protected $subscriptionId = null;
	/**
	 * This will be filled with the Mollie Subscription Status.
	 *
	 * @var string
	 * @since   1.0.0
	 * @version [VERSION]
	 */
	protected $subscriptionStatus = null;
	/**
	 * The interval will be set for this subscription.
	 *
	 * @var string
	 * @since   1.0.0
	 * @version [VERSION]
	 */
	protected $interval;
	/**
	 * The amount of times that needs to be charged for this subscription.
	 *
	 * @var int
	 * @since   1.0.0
	 * @version [VERSION]
	 */
	protected $times = 0;

	/**
	 * MollieRecurring constructor.
	 * @since   1.0.0
	 * @version [VERSIOn]
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Setting recurring to true.
	 *
	 * @param $state
	 *
	 * @return $this
	 */
	public function setRecurring($state)
	{
		$this->recurring = $state;

		return $this;
	}

	/**
	 * Setting the customer name.
	 *
	 * @param $name
	 *
	 * @return $this
	 * @since   1.0.0
	 * @version [VERSION]
	 */
	public function setCustomerName($name)
	{
		$this->name = $name;

		return $this;
	}

	/**
	 * Setting the customer email.
	 *
	 * @param $email
	 *
	 * @return $this
	 * @since   1.0.0
	 * @version [VERSION]
	 */
	public function setCustomerEmail($email)
	{
		$this->email = $email;

		return $this;
	}

	/**
	 * Setting the customer ID which is from the recurring database or newly created before..
	 *
	 * @param $email
	 *
	 * @return $this
	 * @since   1.0.0
	 * @version [VERSION]
	 */
	public function setCustomerId($customerId)
	{
		$this->customerId = $customerId;

		return $this;
	}

	/**
	 * Setting the mandate ID which is being used by this customer.
	 *
	 * @param $email
	 *
	 * @return $this
	 * @since   1.0.0
	 * @version [VERSION]
	 */
	public function setMandateId($mandateId)
	{
		$this->mandateId = $mandateId;

		return $this;
	}

	/**
	 * Setting the interval for this subscription.
	 *
	 * @param $email
	 *
	 * @return $this
	 * @since   1.0.0
	 * @version [VERSION]
	 */
	public function setInterval($interval)
	{
		$this->interval = $interval;

		return $this;
	}

	/**
	 * Setting the amount of recurring times
	 *
	 * @param null $times
	 *
	 * @return $this
	 * @since   1.0.0
	 * @version [VERSION]
	 */
	public function setRecurringTimes($times = null)
	{
		$this->times = $times;

		return $this;
	}

	/**
	 * Making the ordercode available for the Trait Logger
	 *
	 * @return int
	 */
	public function getOrderId()
	{
		return ! empty($this->orderid) ? $this->orderid : null;
	}

	/**
	 * Creating a Mollie Customer for a mandate.
	 *
	 * @param $ordercode
	 *
	 * @return string
	 * @since   1.0.0
	 * @version [VERSION]
	 */
	public function createCustomer()
	{
		$customer = [
			"name"  => $this->name,
			"email" => $this->email,
		];

		// Setting the API Key.
		$this->mollie->setApiKey($this->apiKey);

		try
		{
			$customer = $this->mollie->customers->create($customer);

			return $customer->id;
		}
		catch (\Mollie\Api\Exceptions\ApiException $e)
		{
			echo "API call failed: " . htmlspecialchars($e->getMessage());
		}
	}

	/**
	 * Getting the mandate for a customer so we can save it to our database.
	 * We may use it later on with new subscriptions.
	 *
	 * @since   1.0.0
	 * @version [VERSION]
	 */
	public function getMandateForCustomerId()
	{
		$valid_mandate = [];

		try
		{
			$this->mollie->setApiKey($this->apiKey);

			$mandates = $this->mollie->mandates->listForId($this->customerId);

			foreach ($mandates as $mandate)
			{
				if ($mandate->status == 'valid')
				{
					$valid_mandate = [
						'id'   => $mandate->id,
						'type' => $mandate->method,
					];
				}
			}
		}
		catch (\Mollie\Api\Exceptions\ApiException $e)
		{
			echo "API call failed: " . htmlspecialchars($e->getMessage());
		}

		return $valid_mandate;
	}

	/**
	 * Preparing a payment for recurring, as soon as the first payment has been done the customer has a mandate also.
	 * This mandate will be stored in the database then as mandate for later payments.
	 *
	 * @since   1.0.0
	 * @version [VERSION]
	 */
	public function createFirstPayment()
	{
		try
		{
			// Setting the API Key.
			$this->mollie->setApiKey($this->apiKey);

			$payment = $this->mollie->payments->create($this->getPaymentObject(true));

			// Setting the payment ID
			$this->payment_id = $payment->id;

			// Setting the Payemnt URL
			$this->paymentRedirectUrl = $payment->getCheckoutUrl();
		}
		catch (\Mollie\Api\Exceptions\ApiException $e)
		{
			echo "API call failed: " . htmlspecialchars($e->getMessage());
		}

		return $this;
	}

	/**
	 * The customer has a valid mandate, so we can activate a subscription.
	 *
	 * @since   1.0.0
	 * @version [VERSION]
	 */
	public function createSubscription()
	{
		try
		{
			$payment = [
				'amount'      => [
					'value'    => $this->amount,
					'currency' => $this->currency,
				],
				'description' => $this->description,
				'interval'    => $this->interval,
				'webhookUrl'  => $this->webhookUrl,
			];

			if ( ! empty($this->times))
			{
				$payment['times'] = $this->times;
			}

			// Setting the API Key.
			$this->mollie->setApiKey($this->apiKey);

			// Getting the customer from the API
			$customer     = $this->mollie->customers->get($this->customerId);
			$subscription = $customer->createSubscription($this->getPaymentObject(false));
		}
		catch (\Mollie\Api\Exceptions\ApiException $e)
		{
			echo "API call failed: " . htmlspecialchars($e->getMessage());
		}

		$this->subscriptionId     = isset($subscription->id) ? $subscription->id : null;
		$this->subscriptionStatus = isset($subscription->status) ? $subscription->status : null;

		return $this;
	}

	/**
	 * Generating a payment object.
	 *
	 * @param bool $firstpayment
	 *
	 * @return array
	 */
	public function getPaymentObject($firstpayment = true)
	{
		$payment_object = [
			"amount"       => [
				"value"    => $this->amount,
				"currency" => $this->currency,
			],
			"description"  => $this->description,
			"redirectUrl"  => $this->redirectUrl,
			"webhookUrl"   => $this->webhookUrl,
			"customerId"   => $this->customerId,
			"sequenceType" => 'first',
		];

		if ( ! $firstpayment)
		{
			// Unsetting shizzle which is not needed for a subscription.
			unset($payment_object['customerId'], $payment_object['sequenceType'], $payment_object['redirectUrl']);

			// Setting the interval
			$payment['interval'] = $this->interval;
		}

		// Sometimes you want to set an amount of recurring times
		// For one time payments for example we do want to set it to once.
		if ( ! empty($this->times) && ! $firstpayment)
		{
			$payment['times'] = $this->times;
		}

		return $payment_object;
	}

	/**
	 * Returning the subscription ID
	 *
	 * @return null|string
	 * @since   1.0.0
	 * @version [VERSION]
	 */
	public function getSubscriptionId()
	{
		return ! empty($this->subscriptionId) ? $this->subscriptionId : null;
	}

	/**
	 * Returning the subscription ID
	 *
	 * @return null|string
	 * @since   1.0.0
	 * @version [VERSION]
	 */
	public function getSubscriptionStatus()
	{
		return ! empty($this->subscriptionStatus) ? $this->subscriptionStatus : null;
	}

	/**
	 * Returning the generated Payment ID.
	 *
	 * @return null|string
	 * @since   1.0.0
	 * @version [VERSION]
	 */
	public function getPaymentId()
	{
		return ! empty($this->payment_id) ? $this->payment_id : null;
	}

}