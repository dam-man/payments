<?php
/**
 * [ COPYRIGHT HEADER ]
 */

namespace RDPayments\Providers;

defined('_JEXEC') or die('Restricted access');

use RDPayments\Api\PaymentInterface;
use RDPayments\Payment;
use Stripe\Charge;
use Stripe\Stripe;
use Stripe\Refund;
use Stripe\Balance;

class StripePayment extends Payment implements PaymentInterface
{
	protected $paid = false;
	protected $trix = null;

	/**
	 * Making the payment in Stripe.
	 *
	 * @param array $request
	 *
	 * @return bool
	 * @version [VERSION]
	 */
	public function startPayment($request = [])
	{
		Stripe::setApiKey($this->apiKey);

		try
		{
			$charge = Charge::create(
				[
					"amount"      => $this->amount,
					"currency"    => $this->currency,
					"card"        => $this->token,
					"metadata"    => [
						"order_id" => $this->orderid,
					],
					"description" => $this->description,
				]
			);
		}
		catch (Exception $e)
		{
			return false;
		}

		$this->paid    = true;
		$this->trix    = $charge->id;
		$this->amount  = $charge->amount;
		$this->details = json_encode($charge);

		return true;
	}

	/**
	 *
	 * Refunding transactions within one click
	 *
	 * @return bool
	 * @since   2.0.0
	 * @version [VERSION]
	 */
	public function refundTransaction()
	{
		Stripe::setApiKey($this->apiKey);

		try
		{
			$refund = Refund::create([
				'charge' => $this->token,
				'amount' => $this->amount,
			]);
		}
		catch (Exception $e)
		{
			return false;
		}

		$this->trix = $refund->id;

		return true;
	}

	/**
	 * Getting the balance of the Stripe Account.
	 *
	 * @since   2.0.0
	 * @version [VERSION]
	 */
	public function getBalance()
	{
		Stripe::setApiKey($this->apiKey);

		$balance = Balance::retrieve();

		return [
			'amount'   => ! empty($balance->available->amount) ? $balance->available->amount : null,
			'currency' => ! empty($balance->available->currency) ? $balance->available->currency : null,
		];
	}

	/**
	 * Setting payment state
	 *
	 * @return bool
	 * @version [VERSION]
	 */
	public function isPaid()
	{
		return $this->paid;
	}

	/**
	 * Getitng payment amount.
	 *
	 * @return null
	 * @version [VERSIOn]
	 */
	public function getTransactionAmount()
	{
		return ! empty($this->amount) ? $this->amount : null;
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
		return $this->trix;
	}

	public function getOrderIdFromTransaction()
	{
	}

	public function getPaymentRedirectUrl()
	{
	}

	public function getTransactionDetails($token, $payer_id = null)
	{
	}
}