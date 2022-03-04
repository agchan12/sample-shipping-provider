<?php

namespace BCSample\Shipping\Domain\ShippingRates;

use GuzzleHttp\Client;
use GuzzleHttp\TransferStats;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ShippingRateAPIController
{
    /** @var StubbedShippingRateAPIService */
    private $rateAPIService;

    /**
     * ShippingRateAPIController constructor.
     * @param StubbedShippingRateAPIService $rateAPIService
     */
    public function __construct(StubbedShippingRateAPIService $rateAPIService)
    {
        $this->rateAPIService = $rateAPIService;
    }

    public function getRates(Request $request)
    {
        error_log('is this updated');

        $requestPayload = json_decode($request->getContent(), true);

        if (!$this->validatePayload($requestPayload)) {
            return new JsonResponse($this->buildErrorResponseBody('Badly formatted request'));
        }

        $storeHash = $requestPayload['base_options']['store_id'];
        $cartId = $requestPayload['base_options']['request_context']['reference_values'][0]['value'];

        error_log(':::cURL request to Checkout API:::');
        error_log("::cart_id::$cartId");
        $startTime = microtime(true);

        $client = $this->setupHttpClient(
            [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-Auth-Token' => $requestPayload['connection_options']['x_auth_token'],
                'X-Auth-Client' => $requestPayload['connection_options']['x_auth_client'],
            ],
            null,
            false
        );

        $checkoutResponse = $client->request(
            'GET',
            "https://api.service.bcdev/stores/{$storeHash}/v3/checkouts/{$cartId}"
        );

//        $ch = curl_init();
//        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
//        curl_setopt($ch, CURLOPT_URL, "https://store-{$storeHash}.store.bcdev/internalapi/v2/checkouts/{$cartId}?XDEBUG_SESSION_START=1");
//        curl_setopt($ch, CURLOPT_HTTPHEADER, [
//            'Accept: application/json',
//            'Content-Type: application/json',
//        ]);
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//        $output = curl_exec($ch);
//        $result = curl_getinfo($ch);
//        error_log("::checkout_output_call::$output");
//        curl_close($ch);

        $checkoutOutputData = $checkoutResponse->getBody();

        error_log("::checkout_output_call::$checkoutOutputData");

        $endTime = microtime(true);

        error_log(':::cURL Time:::' . ($endTime - $startTime));

        try {
            $result = $this->rateAPIService->getRates($requestPayload);
        } catch (Exception $e) {
            return new JsonResponse($this->buildErrorResponseBody($e->getMessage()));
        }

        return new JsonResponse($result);
    }

    private function buildErrorResponseBody(string $message)
    {
        return [
            'messages' => [
                [
                    'text' => $message,
                    'type' => 'ERROR',
                ]
            ]
        ];
    }

    private function validatePayload($requestPayload)
    {
        return !is_null($requestPayload) && is_array($requestPayload);
    }

    /**
     * Initialises underlying Http client
     *
     * @param array $headers
     * @param array|null $basicAuth
     * @param bool|true $verifySSLCert
     * @param int|null $timeout
     */
    public function setupHttpClient($headers = [], $basicAuth = null, $verifySSLCert = true, $timeout = null)
    {
        $config = [
            'timeout' => $timeout ?: 60,
            'verify' => $verifySSLCert,
            'headers' => $headers,
            'http_errors' => true
        ];
        if ($basicAuth) {
            $config['auth'] = $basicAuth;
        }
        return new Client($config);
    }
}
