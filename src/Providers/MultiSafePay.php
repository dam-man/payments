<?php
/**
 * [ COPYRIGHT HEADER ]
 */

namespace RDPayments\Providers;

use RDPayments\Payment;
use RDPayments\Api\PaymentInterface;

defined('_JEXEC') or die('Restricted access');

class MultiSafePay extends Payment implements PaymentInterface
{
	/**
	 * @var
	 * @since [VERSION]
	 */
	protected $url;
	/**
	 * @var
	 * @since [VERSION]
	 */
	private $transactionDetails;
	/**
	 * @var
	 * @since [VERSION]
	 */
	protected $paymentUrl;

	/**
	 * @param $url
	 *
	 * @since [VERSION]
	 */
	public function setUrl($url)
	{
		$this->url = $url;

		return $this;
	}

	/**
	 * Getting the headers for the request.
	 * It is required for all requests to MultiSafePay
	 *
	 * @since [VERSION]
	 */
	public function getHeaders($key = null)
	{
		if ($key)
		{
			return ['api_key' => $this->apiKey];
		}

		return ['api_key' => $this->apiKey];
	}

	/**
	 * Requesting a redirect URL for the payment
	 *
	 * @param array $post
	 *
	 * @return string
	 *
	 * @since [VERSION]
	 */
	public function startPayment($request = [])
	{
		// Getting pyment details
		$data = $this->preparePaymentData();

		// Getting the url for redirects
		$url = $this->getUrlForTransaction();

		try
		{
			$content = \JHttpFactory::getHttp()->post($url . 'orders', $data, $this->getHeaders())->body;
		}
		catch (\RuntimeException $e)
		{
			return false;
		}

		$payment = json_decode($content);

		$this->paymentUrl = isset($payment->data->payment_url) ? $payment->data->payment_url : null;
	}

	/**
	 * Getting the URL and sadbox settings.
	 *
	 * @since [VERSION]
	 */
	public function getUrlForTransaction()
	{
		// Setting specific sandbox things.
		$prefix = $this->sandbox ? 'testapi' : 'api';

		// Getting the domain for this call.
		return 'https://' . $prefix . '.multisafepay.com/v1/json/';
	}

	/**
	 * Generating the post data.
	 *
	 * @param array $post
	 * @param array $customer
	 *
	 * @return array
	 *
	 * @since [VERSION]
	 */
	private function preparePaymentData($post = [], $customer = [])
	{
		$data = [
			"type"            => "redirect",
			"order_id"        => $this->orderid,
			"currency"        => $this->currency,
			"amount"          => $this->amount,
			"description"     => $this->description,
			"payment_options" => [
				"notification_url" => $this->webhookUrl,
				"redirect_url"     => $this->redirectUrl,
				"cancel_url"       => $this->setCancelUrl,
			],
		];

		return $data;
	}

	/**
	 * Giving back the payment url for the redirect in the plugin.
	 *
	 * @return mixed
	 */
	public function getPaymentRedirectUrl()
	{
		return $this->paymentUrl;
	}

	/**
	 * Returns the result of the transaction. (true/false)
	 *
	 * @return int
	 * @since [VERSION]
	 */
	public function isPaid()
	{
		if (isset($this->transactionDetails['data']['status']) && $this->transactionDetails['data']['status'] == 'completed')
		{
			return true;
		}

		return false;
	}

	/**
	 * Returning the paid amount.
	 *
	 * @return float|int|null
	 * @since [VERSION]
	 */
	public function getTransactionAmount()
	{
		return isset($this->transactionDetails['data']['amount']) ? $this->transactionDetails['data']['amount'] / 100 : 0;
	}

	/**
	 * Returning the transaction ID from the payment provider for refunds.
	 *
	 * @return null
	 * @since [VERSION]
	 */
	public function getOrderIdFromTransaction()
	{
		return isset($this->transactionDetails['data']['transaction_id']) ? $this->transactionDetails['data']['transaction_id'] : null;
	}

	/**
	 * Returning the transaction ID from the payment provider for refunds.
	 *
	 * @return null
	 * @since [VERSION]
	 */
	public function getTrix()
	{
		return isset($this->transactionDetails['data']['transaction_id']) ? $this->transactionDetails['data']['transaction_id'] : null;
	}

	/**
	 * Returning the transaction ID from the payment provider for refunds.
	 *
	 * @return null
	 * @since [VERSION]
	 */
	public function getPaymentDetailsAsJsonObject()
	{
		return isset($this->transactionDetails['data']) ? json_encode($this->transactionDetails['data']) : null;
	}

	/**
	 * Returning the transaction ID from the payment provider for refunds.
	 *
	 * @return null
	 * @since [VERSION]
	 */
	public function getPaymentDetailsAsArray()
	{
		return isset($this->transactionDetails['data']) ? $this->transactionDetails['data'] : [];
	}

	/**
	 * Getting orderdetails from a specific order from MultisafePay
	 *
	 * @param      $token
	 * @param null $payer_id
	 *
	 * @return bool
	 * @since [VERSION]
	 */
	public function getTransactionDetails($token, $payer_id = null)
	{
		try
		{
			$content = \JHttpFactory::getHttp()->get($this->getUrlForTransaction() . 'orders/' . $token, $this->getHeaders())->body;
		}
		catch (\RuntimeException $e)
		{
			return false;
		}

		// Getting the reults from MultisafePay
		$this->transactionDetails = json_decode($content, true);

		return true;
	}
}