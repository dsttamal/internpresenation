<?php

namespace App\Controllers;

use App\Core\Response;
use App\Models\Submission;
use App\Models\Form;

/**
 * @OA\Tag(
 *     name="Payments",
 *     description="Payment processing and management"
 * )
 */
class PaymentController
{
    /**
     * @OA\Post(
     *     path="/api/payment/stripe/create-intent",
     *     tags={"Payments"},
     *     summary="Create Stripe payment intent",
     *     description="Create a Stripe payment intent for form submission",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"submissionId", "amount", "currency"},
     *             @OA\Property(property="submissionId", type="string", example="sub_abc123def456"),
     *             @OA\Property(property="amount", type="integer", example=9999, description="Amount in cents"),
     *             @OA\Property(property="currency", type="string", example="usd"),
     *             @OA\Property(property="description", type="string", example="Event Registration Payment"),
     *             @OA\Property(
     *                 property="metadata",
     *                 type="object",
     *                 example={"submissionId": "sub_abc123def456", "formId": "42"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment intent created successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid request data"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Submission not found"
     *     )
     * )
     */
    public function createStripePaymentIntent($request)
    {
        try {
            $data = $request->getParsedBody();

            // Validate required fields
            $required = ['submissionId', 'amount', 'currency'];
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    return Response::error("Field '{$field}' is required", 400);
                }
            }

            // Find submission
            $submission = Submission::where('unique_id', $data['submissionId'])->first();
            if (!$submission) {
                return Response::error('Submission not found', 404);
            }

            // Check if Stripe is configured
            if (!isset($_ENV['STRIPE_SECRET_KEY'])) {
                return Response::error('Stripe is not configured', 500);
            }

            // Initialize Stripe
            \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

            // Create payment intent
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'description' => $data['description'] ?? 'Form submission payment',
                'metadata' => array_merge([
                    'submission_id' => $data['submissionId'],
                    'form_id' => $submission->form_id
                ], $data['metadata'] ?? []),
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            // Update submission with payment intent ID
            $submission->payment_intent_id = $paymentIntent->id;
            $submission->payment_status = 'pending';
            $submission->save();

            return Response::success([
                'clientSecret' => $paymentIntent->client_secret,
                'paymentIntentId' => $paymentIntent->id,
                'amount' => $paymentIntent->amount,
                'currency' => $paymentIntent->currency,
                'status' => $paymentIntent->status
            ]);

        } catch (\Stripe\Exception\ApiErrorException $e) {
            return Response::error('Stripe error: ' . $e->getMessage(), 400);
        } catch (\Exception $e) {
            return Response::error('Failed to create payment intent: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/payment/stripe/confirm",
     *     tags={"Payments"},
     *     summary="Confirm Stripe payment",
     *     description="Confirm a Stripe payment and update submission status",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"paymentIntentId"},
     *             @OA\Property(property="paymentIntentId", type="string", example="pi_abc123def456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment confirmed successfully"
     *     )
     * )
     */
    public function confirmStripePayment($request)
    {
        try {
            $data = $request->getParsedBody();

            if (!isset($data['paymentIntentId'])) {
                return Response::error('Payment intent ID is required', 400);
            }

            // Initialize Stripe
            \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

            // Retrieve payment intent
            $paymentIntent = \Stripe\PaymentIntent::retrieve($data['paymentIntentId']);

            // Find submission
            $submission = Submission::where('payment_intent_id', $paymentIntent->id)->first();
            if (!$submission) {
                return Response::error('Submission not found for this payment', 404);
            }

            // Update submission based on payment status
            if ($paymentIntent->status === 'succeeded') {
                $submission->payment_status = 'completed';
                $submission->status = 'completed';
                $submission->payment_completed_at = date('Y-m-d H:i:s');
            } else {
                $submission->payment_status = 'failed';
            }

            $submission->save();

            return Response::success([
                'paymentStatus' => $paymentIntent->status,
                'submissionStatus' => $submission->status,
                'amount' => $paymentIntent->amount,
                'currency' => $paymentIntent->currency
            ], 'Payment confirmed successfully');

        } catch (\Stripe\Exception\ApiErrorException $e) {
            return Response::error('Stripe error: ' . $e->getMessage(), 400);
        } catch (\Exception $e) {
            return Response::error('Failed to confirm payment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/payment/stripe/webhook",
     *     tags={"Payments"},
     *     summary="Stripe webhook handler",
     *     description="Handle Stripe webhook events",
     *     @OA\Response(
     *         response=200,
     *         description="Webhook processed successfully"
     *     )
     * )
     */
    public function stripeWebhook($request)
    {
        try {
            $payload = $request->getBody();
            $sig_header = $request->getHeader('stripe-signature');
            $endpoint_secret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '';

            if (!$endpoint_secret) {
                return Response::error('Webhook secret not configured', 500);
            }

            // Verify webhook signature
            try {
                $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
            } catch (\UnexpectedValueException $e) {
                return Response::error('Invalid payload', 400);
            } catch (\Stripe\Exception\SignatureVerificationException $e) {
                return Response::error('Invalid signature', 400);
            }

            // Handle the event
            switch ($event['type']) {
                case 'payment_intent.succeeded':
                    $paymentIntent = $event['data']['object'];
                    $this->handlePaymentSucceeded($paymentIntent);
                    break;

                case 'payment_intent.payment_failed':
                    $paymentIntent = $event['data']['object'];
                    $this->handlePaymentFailed($paymentIntent);
                    break;

                default:
                    // Unhandled event type
                    break;
            }

            return Response::success([], 'Webhook processed successfully');

        } catch (\Exception $e) {
            return Response::error('Webhook processing failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/payment/bank-transfer",
     *     tags={"Payments"},
     *     summary="Process bank transfer payment",
     *     description="Record a bank transfer payment with receipt",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"submissionId", "amount", "currency"},
     *             @OA\Property(property="submissionId", type="string", example="sub_abc123def456"),
     *             @OA\Property(property="amount", type="number", example=99.99),
     *             @OA\Property(property="currency", type="string", example="USD"),
     *             @OA\Property(
     *                 property="bankDetails",
     *                 type="object",
     *                 @OA\Property(property="accountHolder", type="string"),
     *                 @OA\Property(property="bankName", type="string"),
     *                 @OA\Property(property="accountNumber", type="string"),
     *                 @OA\Property(property="routingNumber", type="string"),
     *                 @OA\Property(property="transactionId", type="string")
     *             ),
     *             @OA\Property(property="receiptFile", type="string", example="payment_receipt.pdf"),
     *             @OA\Property(property="notes", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Bank transfer payment recorded successfully"
     *     )
     * )
     */
    public function bankTransfer($request)
    {
        try {
            $data = $request->getParsedBody();

            // Validate required fields
            $required = ['submissionId', 'amount', 'currency'];
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    return Response::error("Field '{$field}' is required", 400);
                }
            }

            // Find submission
            $submission = Submission::where('unique_id', $data['submissionId'])->first();
            if (!$submission) {
                return Response::error('Submission not found', 404);
            }

            // Generate payment ID
            $paymentId = 'pay_bank_' . uniqid();

            // Update submission
            $submission->payment_method = 'bank_transfer';
            $submission->payment_amount = $data['amount'];
            $submission->payment_currency = $data['currency'];
            $submission->payment_status = 'pending_approval';
            $submission->payment_reference = $paymentId;

            if (isset($data['bankDetails'])) {
                $submission->payment_details = json_encode($data['bankDetails']);
            }

            if (isset($data['receiptFile'])) {
                $submission->payment_receipt = $data['receiptFile'];
            }

            if (isset($data['notes'])) {
                $submission->payment_notes = $data['notes'];
            }

            $submission->save();

            // Generate receipt URL if file provided
            $receiptUrl = null;
            if (isset($data['receiptFile'])) {
                $baseUrl = $request->getUri()->getScheme() . '://' . $request->getUri()->getHost();
                $receiptUrl = $baseUrl . '/uploads/receipts/' . $data['receiptFile'];
            }

            return Response::success([
                'paymentId' => $paymentId,
                'status' => 'pending_approval',
                'submissionId' => $data['submissionId'],
                'amount' => $data['amount'],
                'receiptUrl' => $receiptUrl
            ], 'Bank transfer payment recorded. Pending admin approval.', 201);

        } catch (\Exception $e) {
            return Response::error('Failed to process bank transfer: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/payment/bank-transfer/{id}/approve",
     *     tags={"Payments"},
     *     summary="Approve bank transfer payment",
     *     description="Approve a bank transfer payment (admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Payment ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="adminNotes", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment approved successfully"
     *     )
     * )
     */
    public function approveBankTransfer($request)
    {
        try {
            // Check admin authorization
            if (!$this->isAdmin($request)) {
                return Response::error('Admin access required', 403);
            }

            $paymentId = $request->getParam('id');
            $data = $request->getParsedBody();

            $submission = Submission::where('payment_reference', $paymentId)->first();
            if (!$submission) {
                return Response::error('Payment not found', 404);
            }

            $submission->payment_status = 'completed';
            $submission->status = 'completed';
            $submission->payment_completed_at = date('Y-m-d H:i:s');

            if (isset($data['adminNotes'])) {
                $submission->admin_notes = $data['adminNotes'];
            }

            $submission->save();

            return Response::success([
                'paymentId' => $paymentId,
                'status' => 'completed'
            ], 'Payment approved successfully');

        } catch (\Exception $e) {
            return Response::error('Failed to approve payment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/payment/bank-transfer/{id}/reject",
     *     tags={"Payments"},
     *     summary="Reject bank transfer payment",
     *     description="Reject a bank transfer payment (admin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Payment ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="reason", type="string"),
     *             @OA\Property(property="adminNotes", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment rejected successfully"
     *     )
     * )
     */
    public function rejectBankTransfer($request)
    {
        try {
            // Check admin authorization
            if (!$this->isAdmin($request)) {
                return Response::error('Admin access required', 403);
            }

            $paymentId = $request->getParam('id');
            $data = $request->getParsedBody();

            $submission = Submission::where('payment_reference', $paymentId)->first();
            if (!$submission) {
                return Response::error('Payment not found', 404);
            }

            $submission->payment_status = 'rejected';
            $submission->status = 'payment_failed';

            if (isset($data['reason'])) {
                $submission->payment_rejection_reason = $data['reason'];
            }

            if (isset($data['adminNotes'])) {
                $submission->admin_notes = $data['adminNotes'];
            }

            $submission->save();

            return Response::success([
                'paymentId' => $paymentId,
                'status' => 'rejected'
            ], 'Payment rejected');

        } catch (\Exception $e) {
            return Response::error('Failed to reject payment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/bkash/create",
     *     tags={"Payments"},
     *     summary="Create bKash payment",
     *     description="Create a bKash payment session",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"submissionId", "amount", "currency"},
     *             @OA\Property(property="submissionId", type="string"),
     *             @OA\Property(property="amount", type="string", example="99.99"),
     *             @OA\Property(property="currency", type="string", example="BDT"),
     *             @OA\Property(property="intent", type="string", example="sale"),
     *             @OA\Property(property="merchantInvoiceNumber", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="bKash payment created successfully"
     *     )
     * )
     */
    public function createBkashPayment($request)
    {
        try {
            $data = $request->getParsedBody();

            // Validate required fields
            $required = ['submissionId', 'amount', 'currency'];
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    return Response::error("Field '{$field}' is required", 400);
                }
            }

            // Find submission
            $submission = Submission::where('unique_id', $data['submissionId'])->first();
            if (!$submission) {
                return Response::error('Submission not found', 404);
            }

            // Mock bKash API response (replace with actual bKash integration)
            $paymentID = 'TR' . str_pad(rand(1, 999999999999999999), 18, '0', STR_PAD_LEFT);
            
            $bkashResponse = [
                'paymentID' => $paymentID,
                'createTime' => date('c'),
                'orgLogo' => 'https://www.bkash.com/logo.png',
                'orgName' => 'Form Builder App',
                'transactionStatus' => 'Initiated',
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'bkashURL' => 'https://checkout.pay.bka.sh/v1.2.0-beta/checkout/payment/' . $paymentID
            ];

            // Update submission
            $submission->payment_method = 'bkash';
            $submission->payment_amount = floatval($data['amount']);
            $submission->payment_currency = $data['currency'];
            $submission->payment_status = 'pending';
            $submission->payment_reference = $paymentID;
            $submission->save();

            return Response::success($bkashResponse);

        } catch (\Exception $e) {
            return Response::error('Failed to create bKash payment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Execute bKash payment
     */
    public function executeBkashPayment($request)
    {
        try {
            $data = $request->getParsedBody();

            if (!isset($data['paymentID'])) {
                return Response::error('Payment ID is required', 400);
            }

            // Find submission by payment reference
            $submission = Submission::where('payment_reference', $data['paymentID'])->first();
            if (!$submission) {
                return Response::error('Payment not found', 404);
            }

            // Mock execution (replace with actual bKash API call)
            $executionResponse = [
                'paymentID' => $data['paymentID'],
                'paymentExecuteTime' => date('c'),
                'transactionStatus' => 'Completed',
                'trxID' => 'TXN' . uniqid()
            ];

            // Update submission
            $submission->payment_status = 'completed';
            $submission->status = 'completed';
            $submission->payment_completed_at = date('Y-m-d H:i:s');
            $submission->payment_transaction_id = $executionResponse['trxID'];
            $submission->save();

            return Response::success($executionResponse);

        } catch (\Exception $e) {
            return Response::error('Failed to execute bKash payment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Query bKash payment
     */
    public function queryBkashPayment($request)
    {
        try {
            $data = $request->getParsedBody();

            if (!isset($data['paymentID'])) {
                return Response::error('Payment ID is required', 400);
            }

            // Find submission
            $submission = Submission::where('payment_reference', $data['paymentID'])->first();
            if (!$submission) {
                return Response::error('Payment not found', 404);
            }

            // Mock query response
            $queryResponse = [
                'paymentID' => $data['paymentID'],
                'transactionStatus' => $submission->payment_status === 'completed' ? 'Completed' : 'Initiated',
                'amount' => $submission->payment_amount,
                'currency' => $submission->payment_currency,
                'intent' => 'sale'
            ];

            return Response::success($queryResponse);

        } catch (\Exception $e) {
            return Response::error('Failed to query bKash payment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Refund bKash payment
     */
    public function refundBkashPayment($request)
    {
        try {
            $data = $request->getParsedBody();

            if (!isset($data['paymentID'])) {
                return Response::error('Payment ID is required', 400);
            }

            // Check admin authorization
            if (!$this->isAdmin($request)) {
                return Response::error('Admin access required', 403);
            }

            // Find submission
            $submission = Submission::where('payment_reference', $data['paymentID'])->first();
            if (!$submission) {
                return Response::error('Payment not found', 404);
            }

            // Mock refund response
            $refundResponse = [
                'originalTrxID' => $submission->payment_transaction_id,
                'refundTrxID' => 'REF' . uniqid(),
                'transactionStatus' => 'Completed',
                'amount' => $data['amount'] ?? $submission->payment_amount,
                'currency' => $submission->payment_currency,
                'charge' => '0'
            ];

            // Update submission
            $submission->payment_status = 'refunded';
            $submission->refund_amount = $refundResponse['amount'];
            $submission->refund_transaction_id = $refundResponse['refundTrxID'];
            $submission->refunded_at = date('Y-m-d H:i:s');
            $submission->save();

            return Response::success($refundResponse);

        } catch (\Exception $e) {
            return Response::error('Failed to refund bKash payment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/upload/payment-receipt",
     *     tags={"Payments"},
     *     summary="Upload payment receipt",
     *     description="Upload payment receipt file",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="receipt",
     *                     type="string",
     *                     format="binary",
     *                     description="Payment receipt file"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File uploaded successfully"
     *     )
     * )
     */
    public function uploadPaymentReceipt($request)
    {
        try {
            $files = $request->getUploadedFiles();

            if (!isset($files['receipt'])) {
                return Response::error('No file uploaded', 400);
            }

            $uploadedFile = $files['receipt'];

            // Validate file
            if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
                return Response::error('File upload error', 400);
            }

            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
            $fileType = $uploadedFile->getClientMediaType();
            
            if (!in_array($fileType, $allowedTypes)) {
                return Response::error('Invalid file type. Only JPEG, PNG, and PDF files are allowed.', 400);
            }

            // Validate file size (10MB max)
            $maxSize = 10 * 1024 * 1024; // 10MB
            if ($uploadedFile->getSize() > $maxSize) {
                return Response::error('File too large. Maximum size is 10MB.', 400);
            }

            // Generate unique filename
            $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
            $filename = 'payment_receipt-' . time() . '-' . rand(100000000, 999999999) . '.' . $extension;

            // Create uploads directory if it doesn't exist
            $uploadDir = __DIR__ . '/../../uploads/receipts/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Move uploaded file
            $uploadedFile->moveTo($uploadDir . $filename);

            // Generate file URL
            $baseUrl = $request->getUri()->getScheme() . '://' . $request->getUri()->getHost();
            $fileUrl = $baseUrl . '/uploads/receipts/' . $filename;

            return Response::success([
                'filename' => $filename,
                'url' => $fileUrl,
                'size' => $uploadedFile->getSize(),
                'type' => $fileType
            ], 'File uploaded successfully');

        } catch (\Exception $e) {
            return Response::error('Failed to upload file: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get uploaded file
     */
    public function getUploadedFile($request)
    {
        try {
            $filename = $request->getParam('filename');
            $filepath = __DIR__ . '/../../uploads/receipts/' . $filename;

            if (!file_exists($filepath)) {
                return Response::error('File not found', 404);
            }

            // Security check
            $realPath = realpath($filepath);
            $uploadsPath = realpath(__DIR__ . '/../../uploads/receipts/');

            if (strpos($realPath, $uploadsPath) !== 0) {
                return Response::error('Access denied', 403);
            }

            // Serve file
            $mimeType = mime_content_type($filepath);
            
            return new Response(200, [
                'Content-Type' => $mimeType,
                'Content-Length' => filesize($filepath),
                'Cache-Control' => 'public, max-age=31536000'
            ], file_get_contents($filepath));

        } catch (\Exception $e) {
            return Response::error('Failed to retrieve file: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/settings/payment-methods",
     *     tags={"Payments"},
     *     summary="Get available payment methods",
     *     description="Get list of available payment methods and their configuration",
     *     @OA\Response(
     *         response=200,
     *         description="Payment methods retrieved successfully"
     *     )
     * )
     */
    public function getPaymentMethods($request)
    {
        try {
            $methods = [
                'stripe' => [
                    'enabled' => !empty($_ENV['STRIPE_PUBLIC_KEY']),
                    'name' => 'Credit/Debit Card',
                    'description' => 'Pay securely with your credit or debit card',
                    'fees' => '2.9% + $0.30 per transaction'
                ],
                'bkash' => [
                    'enabled' => !empty($_ENV['BKASH_APP_KEY']),
                    'name' => 'bKash',
                    'description' => 'Pay with bKash mobile wallet',
                    'fees' => '1.5% per transaction'
                ],
                'bank_transfer' => [
                    'enabled' => true,
                    'name' => 'Bank Transfer',
                    'description' => 'Direct bank transfer with receipt upload',
                    'fees' => 'No processing fees'
                ]
            ];

            return Response::success($methods);

        } catch (\Exception $e) {
            return Response::error('Failed to retrieve payment methods: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Handle successful payment
     */
    private function handlePaymentSucceeded($paymentIntent)
    {
        $submission = Submission::where('payment_intent_id', $paymentIntent['id'])->first();
        
        if ($submission) {
            $submission->payment_status = 'completed';
            $submission->status = 'completed';
            $submission->payment_completed_at = date('Y-m-d H:i:s');
            $submission->save();

            // TODO: Send confirmation email
        }
    }

    /**
     * Handle failed payment
     */
    private function handlePaymentFailed($paymentIntent)
    {
        $submission = Submission::where('payment_intent_id', $paymentIntent['id'])->first();
        
        if ($submission) {
            $submission->payment_status = 'failed';
            $submission->save();

            // TODO: Send failure notification
        }
    }

    /**
     * Check if user is admin
     */
    private function isAdmin($request)
    {
        // Mock implementation - replace with actual JWT middleware
        return true; // For now, allow all operations
    }
}
