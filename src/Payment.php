<?php
/**
 * [ COPYRIGHT HEADER ]
 */

namespace RDPayments;

defined('_JEXEC') or die('Restricted access');

class Payment
{
	/**
	 * @var
	 *
	 * @since [VERSION]
	 */
	protected $apiKey;
	/**
	 * @var
	 *
	 * @since [VERSION]
	 */
	protected $username;
	/**
	 * @var bool
	 *
	 * @since [VERSION]
	 */
	protected $sandbox = false;
	/**
	 * @var
	 *
	 * @since [VERSION]
	 */
	protected $password;
	/**
	 * @var
	 *
	 * @since [VERSION]
	 */
	protected $signature;
	/**
	 * @var
	 *
	 * @since [VERSION]
	 */
	protected $description;
	/**
	 * @var
	 *
	 * @since [VERSION]
	 */
	protected $currency;
	/**
	 * @var
	 *
	 * @since [VERSION]
	 */
	protected $amount;
	/**
	 * @var
	 *
	 * @since [VERSION]
	 */
	protected $redirectUrl;
	/**
	 * @var
	 *
	 * @since [VERSION]
	 */
	protected $setCancelUrl;
	/**
	 * @var
	 *
	 * @since [VERSION]
	 */
	protected $webhookUrl;
	/**
	 * @var
	 *
	 * @since [VERSION]
	 */
	protected $redirectPaymentUrl;
	/**
	 * @var
	 *
	 * @since [VERSION]
	 */
	protected $orderid;
	/**
	 * @var
	 *
	 * @since [VERSION]
	 */
	protected $locale;
	/**
	 * @var array
	 * @since 1.4.1
	 */
	protected $cart_items = [];

	/**
	 * Setting a custom API Key for usage in Provider classes.
	 *
	 * @param $apiKey
	 *
	 * @since [VERSION]
	 *
	 * @return $this
	 */
	public function setApiKey($apiKey)
	{
		$this->apiKey = $apiKey;

		return $this;
	}

	/**
	 * Setting a payment provider in sandbox mode.
	 *
	 * @param bool $sandbox
	 *
	 * @since [VERSION]
	 *
	 * @return $this
	 */
	public function setSandbox($sandbox = false)
	{
		$this->sandbox = $sandbox;

		return $this;
	}

	/**
	 * Setting a username for the API in Provider classes.
	 *
	 * @param $username
	 *
	 * @since [VERSION]
	 *
	 * @return $this
	 */
	public function setApiUser($username)
	{
		$this->username = $username;

		return $this;
	}

	/**
	 * Setting a password for the API in Provider classes.
	 *
	 * @param $password
	 *
	 * @since [VERSION]
	 *
	 * @return $this
	 */
	public function setApiPassword($password)
	{
		$this->password = $password;

		return $this;
	}

	/**
	 * Setting a signature for the API in Provider classes.
	 *
	 * @param $signature
	 *
	 * @since [VERSION]
	 *
	 * @return $this
	 */
	public function setApiSignature($signature)
	{
		$this->signature = $signature;

		return $this;
	}

	/**
	 * Setting a payment description
	 *
	 * @param $description
	 *
	 * @since [VERSION]
	 *
	 * @return $this
	 */
	public function setPaymentDescription($description)
	{
		$this->description = $description;

		return $this;
	}

	/**
	 * Setting cart items for checkout.
	 *
	 * @param array $items
	 *
	 * @return $this
	 */
	public function setCartItems($items = [])
	{
		$this->cart_items = ! empty($items) ? $items : [];

		return $this;
	}

	/**
	 * Setting the currency for the Payment Provider.
	 *
	 * @param string $currency
	 *
	 * @since [VERSION]
	 *
	 * @return $this
	 */
	public function setCurrency($currency = 'USD')
	{
		$this->currency = $currency;

		return $this;
	}

	/**
	 * Setting the amount for the payment providers.
	 *
	 * @param      $amount
	 * @param bool $cents
	 *
	 * @since [VERSION]
	 *
	 * @return $this
	 */
	public function setAmount($amount, $cents = false)
	{
		$this->amount = ($cents) ? $amount * 100 : $amount;

		return $this;
	}

	/**
	 * Setting the return URL
	 *
	 * @since [VERSION]
	 *
	 * @param $returnUrl
	 *
	 * @return $this
	 */
	public function redirectUrl($redirectUrl)
	{
		$this->redirectUrl = $redirectUrl;

		return $this;
	}

	/**
	 * Setting the webhook URL
	 *
	 * @param $webhookUrl
	 *
	 * @since [VERSION]
	 *
	 * @return $this
	 */
	public function setWebHookUrl($webhookUrl)
	{
		$this->webhookUrl = $webhookUrl;

		return $this;
	}

	/**
	 * Setting a cancel Url
	 *
	 * @param $cancelUrl
	 *
	 * @since [VERSION]
	 *
	 * @return $this
	 */
	public function setCancelUrl($cancelUrl)
	{
		$this->setCancelUrl = $cancelUrl;

		return $this;
	}

	/**
	 * Setting the checksout locale
	 *
	 * @since [VERSION]
	 *
	 * @param $locale
	 *
	 * @return $this
	 */
	public function setLocale($locale)
	{
		$this->locale = $locale;

		return $this;
	}

	/**
	 * @param null $orderid
	 *
	 * @since [VERSION]
	 *
	 * @return $this
	 */
	public function setOrderId($orderid = null)
	{
		$this->orderid = $orderid;

		return $this;
	}

	/**
	 * Setting a payment token for tokenized payments.
	 *
	 * @param $token
	 *
	 * @since [VERSION]
	 *
	 * @return $this
	 */
	public function setPaymentToken($token)
	{
		$this->token = $token;

		return $this;
	}

	/**
	 * Dumping results in a readable string.
	 *
	 * @param array $array
	 *
	 * @since [VERSION]
	 */
	public function dump($array = [])
	{
		echo '<pre>';
		var_dump($array);
		echo '</pre>';
		die;
	}
}