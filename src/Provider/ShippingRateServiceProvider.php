<?php

namespace BCSample\Shipping\Provider;

use BCSample\Shipping\Domain\ShippingRates\ShippingRateAPIController;
use BCSample\Shipping\Domain\ShippingRates\ShippingRateAPIService;
use BCSample\Shipping\Helper\FixtureLoader;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * Class ShippingRateServiceProvider
 *
 * Silex provider to register all the bits of the shipping rate service in the DI Container
 *
 * @package BCSample\Shipping\Provider
 */
class ShippingRateServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Container $app
     */
    public function register(Container $app)
    {
        $app[ShippingRateAPIService::class] = new ShippingRateAPIService($app[FixtureLoader::class]);
        $app[ShippingRateAPIController::class] = new ShippingRateAPIController($app[ShippingRateAPIService::class]);
    }
}
