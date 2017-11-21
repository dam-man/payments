<?php
/**
 * [ COPYRIGHT HEADER ]
 */

namespace RDPayments\Api;

interface PaymentInterface
{
	/**
	 * Creating or charging a payment object
	 *
	 * @param array $request
	 *
	 * @since __DEPLOY_VERSION__
	 *
	 * @return mixed
	 */
	public function startPayment($request = []);

	/**
	 * Returning the redirect URL to the payment provider.
	 *
	 * @since __DEPLOY_VERSION__
	 *
	 * @return mixed
	 */
	public function getPaymentRedirectUrl();
}