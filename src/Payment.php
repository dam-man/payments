<?php
/**
 * [ COPYRIGHT HEADER ]
 */

namespace RDPayments;

class Payment
{
	/**
	 * @var
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $apiKey;
	/**
	 * @var
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $username;
	/**
	 * @var bool
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $sandbox = false;
	/**
	 * @var
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $password;
	/**
	 * @var
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $signature;
	/**
	 * @var
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $description;
	/**
	 * @var
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $currency;
	/**
	 * @var
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $amount;
	/**
	 * @var
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $redirectUrl;
	/**
	 * @var
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $setCancelUrl;
	/**
	 * @var
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $webhookUrl;
	/**
	 * @var
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $redirectPaymentUrl;
	/**
	 * @var
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $orderid;
	/**
	 * @var
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $locale;

	/**
	 * Setting a custom API Key for usage in Provider classes.
	 *
	 * @param $apiKey
	 *
	 * @since __DEPLOY_VERSION__
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
	 * @since __DEPLOY_VERSION__
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
	 * @since __DEPLOY_VERSION__
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
	 * @since __DEPLOY_VERSION__
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
	 * @since __DEPLOY_VERSION__
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
	 * @since __DEPLOY_VERSION__
	 *
	 * @return $this
	 */
	public function setPaymentDescription($description)
	{
		$this->description = $description;

		return $this;
	}

	/**
	 * Setting the currency for the Payment Provider.
	 *
	 * @param string $currency
	 *
	 * @since __DEPLOY_VERSION__
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
	 * @since __DEPLOY_VERSION__
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
	 * @since __DEPLOY_VERSION__
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
	 * @since __DEPLOY_VERSION__
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
	 * @since __DEPLOY_VERSION__
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
	 * @since __DEPLOY_VERSION__
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
	 * @since __DEPLOY_VERSION__
	 *
	 * @return $this
	 */
	public function setOrderId($orderid = null)
	{
		$this->orderid = $orderid;

		return $this;
	}
}