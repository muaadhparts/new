<?php

namespace App\Http\Controllers\Merchant\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

/**
 * Authorize.net Payment Controller (USA)
 */
class AuthorizeNetPaymentController extends BaseMerchantPaymentController
{
    protected string $paymentKeyword = 'authorize.net';
    protected string $paymentMethod = 'Authorize.net';

    /**
     * POST /merchant/{merchantId}/checkout/payment/authorize
     */
    public function processPayment(Request $request, int $merchantId): JsonResponse
    {
        $validated = $request->validate([
            'cardNumber' => 'required|string',
            'cardCode' => 'required|string',
            'month' => 'required|string',
            'year' => 'required|string',
        ]);

        $validation = $this->validateCheckoutReady($merchantId);
        if (!$validation['valid']) {
            return response()->json($validation, 400);
        }

        $config = $this->getPaymentConfig($merchantId);
        if (!$config) {
            return $this->handlePaymentError($merchantId, __('Authorize.net is not available for this merchant'));
        }

        $checkoutData = $this->getCheckoutData($merchantId);
        $credentials = $config['credentials'];

        try {
            // Set up merchant authentication
            $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
            $merchantAuthentication->setName($credentials['login_id'] ?? '');
            $merchantAuthentication->setTransactionKey($credentials['txn_key'] ?? '');

            // Set reference ID
            $refId = 'ref_M' . $merchantId . '_' . time();

            // Create credit card payment
            $creditCard = new AnetAPI\CreditCardType();
            $creditCard->setCardNumber(str_replace(' ', '', $validated['cardNumber']));
            $creditCard->setExpirationDate($validated['year'] . '-' . $validated['month']);
            $creditCard->setCardCode($validated['cardCode']);

            // Add payment data
            $paymentType = new AnetAPI\PaymentType();
            $paymentType->setCreditCard($creditCard);

            // Create order information
            $itemNumber = 'M' . $merchantId . '_' . time();
            $order = new AnetAPI\OrderType();
            $order->setInvoiceNumber($itemNumber);
            $order->setDescription(__('Purchase from :merchant', ['merchant' => $checkoutData['cart']['merchant']['name'] ?? 'Merchant']));

            // Create transaction request
            $transactionRequestType = new AnetAPI\TransactionRequestType();
            $transactionRequestType->setTransactionType('authCaptureTransaction');
            $transactionRequestType->setAmount($checkoutData['totals']['grand_total']);
            $transactionRequestType->setOrder($order);
            $transactionRequestType->setPayment($paymentType);

            // Assemble complete request
            $apiRequest = new AnetAPI\CreateTransactionRequest();
            $apiRequest->setMerchantAuthentication($merchantAuthentication);
            $apiRequest->setRefId($refId);
            $apiRequest->setTransactionRequest($transactionRequestType);

            // Execute transaction
            $controller = new AnetController\CreateTransactionController($apiRequest);

            $sandboxMode = ($credentials['sandbox_check'] ?? 0) == 1;
            $environment = $sandboxMode
                ? \net\authorize\api\constants\ANetEnvironment::SANDBOX
                : \net\authorize\api\constants\ANetEnvironment::PRODUCTION;

            $response = $controller->executeWithApiResponse($environment);

            if ($response === null) {
                return $this->handlePaymentError($merchantId, __('No response from payment gateway'));
            }

            if ($response->getMessages()->getResultCode() !== 'Ok') {
                $errorMessage = __('Payment failed');
                $errors = $response->getMessages()->getMessage();
                if (!empty($errors)) {
                    $errorMessage = $errors[0]->getText();
                }
                return $this->handlePaymentError($merchantId, $errorMessage);
            }

            $tresponse = $response->getTransactionResponse();

            if ($tresponse === null || $tresponse->getMessages() === null) {
                $errorMessage = __('Payment failed');
                if ($tresponse !== null && $tresponse->getErrors() !== null) {
                    $errors = $tresponse->getErrors();
                    $errorMessage = $errors[0]->getErrorText();
                }
                return $this->handlePaymentError($merchantId, $errorMessage);
            }

            // Payment successful - create purchase
            $result = $this->purchaseCreator->createPurchase($merchantId, [
                'method' => $this->paymentMethod,
                'pay_id' => $config['id'],
                'txnid' => $tresponse->getTransId(),
                'payment_status' => 'Completed',
            ]);

            if (!$result['success']) {
                return $this->handlePaymentError($merchantId, $result['message'] ?? __('Failed to create order'));
            }

            return response()->json([
                'success' => true,
                'message' => __('Payment successful!'),
                'purchase_number' => $result['purchase']->purchase_number,
                'redirect' => $this->getSuccessUrl($merchantId),
            ]);

        } catch (\Exception $e) {
            return $this->handlePaymentError($merchantId, $e->getMessage());
        }
    }

    /**
     * Authorize.net doesn't use callbacks - payment is processed directly
     */
    public function handleCallback(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Not applicable for Authorize.net'], 400);
    }
}
