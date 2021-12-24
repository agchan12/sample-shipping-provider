<?php

namespace BCSample\Shipping\Domain\ShippingRates;

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
        $requestPayload = json_decode($request->getContent(), true);

        if (!$this->validatePayload($requestPayload)) {
            return new JsonResponse($this->buildErrorResponseBody('Badly formatted request'));
        }

        $requestData =  json_decode($request->getContent(), true);
        $storeHash = $requestData['base_options']['store_id'];
        $cartId = $requestData['base_options']['request_context']['reference_values'][0]['value'];

        error_log(':::cURL request to Checkout API:::');
        $startTime = microtime(true);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
        curl_setopt($ch, CURLOPT_URL, "https://api.service.bcdev/stores/{$storeHash}/v3/checkouts/{$cartId}");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-Auth-Token: o80wd9luhsbh2i50kysuxpuxkvtlhm',
            'X-Auth-Client: 36ko3nsufy5xm7f33ij6pdaeqs2ti33',
            'Accept: application/json',
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
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
}
