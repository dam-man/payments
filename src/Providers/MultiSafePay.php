<?php
/**
 * [ COPYRIGHT HEADER ]
 */

namespace RDPayments\Providers;

defined('_JEXEC') or die('Restricted access');

class MultiSafePay
{
	/**
	 * @var
	 * @since [ VERSION ]
	 */
	private $key;

	/**
	 * @var
	 * @since [ VERSION ]
	 */
	private $url;
	/**
	 * @var
	 * @since [ VERSION ]
	 */
	private $headers;

	/**
	 * @param $key
	 *
	 * @since [ VERSION ]
	 */
	public function setApiKey($key)
	{
		$this->key     = $key;
		$this->headers = ['api_key' => $key];
	}

	/**
	 * @param $url
	 *
	 * @since [ VERSION ]
	 */
	public function setUrl($url)
	{
		$this->url = $url;
	}

	/**
	 * Retreiving all Gateways
	 *
	 * @return bool
	 *
	 * @since [ VERSION ]
	 */
	public function getPaymentGateways()
	{
		try
		{
			$content = \JHttpFactory::getHttp()->get($this->url . 'gateways?country=NL', $this->headers)->body;
		}
		catch (\RuntimeException $e)
		{
			return false;
		}

		$gateways = json_decode($content);

		return $gateways->data;
	}

	/**
	 * @param null  $method
	 * @param array $gateways
	 *
	 * @return bool
	 *
	 * @since [ VERSION ]
	 */
	public function isActiveGateWay($method = null, $gateways = [])
	{
		foreach ($gateways as $gateway)
		{
			if ($method === $gateway->id)
			{
				return true;
			}
		}
	}

	/**
	 * Getting orderdetails from a specific order from MultisafePay
	 *
	 * @param string $method
	 *
	 * @return bool|mixed
	 *
	 * @since [ VERSION ]
	 */
	public function getOrderDetails($order_id)
	{
		try
		{
			$content = \JHttpFactory::getHttp()->get($this->url . 'orders/' . $order_id, $this->headers)->body;
		}
		catch (\RuntimeException $e)
		{
			return false;
		}

		$issuers = json_decode($content);

		return $issuers->data;
	}

	/**
	 * Getting iDeal Issuers from MultisafePay
	 *
	 * @param string $method
	 *
	 * @return bool|mixed
	 *
	 * @since [ VERSION ]
	 */
	public function getIdealIssuers($method = 'IDEAL')
	{
		try
		{
			$content = \JHttpFactory::getHttp()->get($this->url . 'issuers/' . $method, $this->headers)->body;
		}
		catch (\RuntimeException $e)
		{
			return false;
		}

		$issuers = json_decode($content);

		return $issuers->data;
	}

	/**
	 * Requesting a redirect URL for the payment
	 *
	 * @param array $post
	 *
	 * @return string
	 *
	 * @since [ VERSION ]
	 */
	public function getRedirectUrlForPayment($post = [], $customer = [])
	{
		$data = $this->preparePaymentData($post, $customer);

		try
		{
			$content = \JHttpFactory::getHttp()->post($this->url . 'orders', $data, $this->headers)->body;
		}
		catch (\RuntimeException $e)
		{
			return false;
		}

		$payment = json_decode($content);

		return $payment->data->payment_url;
	}

	/**
	 * Generating the post data.
	 *
	 * @param array $post
	 * @param array $customer
	 *
	 * @return array
	 *
	 * @since [ VERSION ]
	 */
	private function preparePaymentData($post = [], $customer = [])
	{
		$data = [
			"type"            => "redirect",
			"order_id"        => $post['ordercode'],
			"currency"        => "EUR",
			"amount"          => $post['amount'],
			"description"     => $post['description'],
			"var_1"           => $post['var1'],
			"payment_options" => [
				"notification_url" => $post['notify_url'],
				"redirect_url"     => $post['return_url'],
				"cancel_url"       => $post['cancel_url'],
			],
		];

		return $data;
	}
}