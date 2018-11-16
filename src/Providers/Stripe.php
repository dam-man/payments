<?php
/**
 * [ COPYRIGHT HEADER ]
 */

namespace RDPayments\Providers;

defined('_JEXEC') or die('Restricted access');

use RDPayments\Api\PaymentInterface;
use RDPayments\Payment;

class Stripe extends Payment implements PaymentInterface
{
	public function startPayment($request = [])
	{
	}

	public function getPaymentRedirectUrl()
	{
	}

	public function isPaid()
	{
	}

	public function getTransactionAmount()
	{
	}

	public function getOrderIdFromTransaction()
	{
	}

	public function getTransactionDetails($token, $payer_id = null)
	{
	}
}