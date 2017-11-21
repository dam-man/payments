<?php
/**
 * [ COPYRIGHT HEADER ]
 */

namespace RDPayments\Api;

interface PaymentInterface
{
	public function startPayment($request = []);
	public function getPaymentRedirectUrl();
	public function isPaid();
	public function getTransactionAmount();
	public function getOrderIdFromTransaction();
	public function getTransactionDetails($token);
}