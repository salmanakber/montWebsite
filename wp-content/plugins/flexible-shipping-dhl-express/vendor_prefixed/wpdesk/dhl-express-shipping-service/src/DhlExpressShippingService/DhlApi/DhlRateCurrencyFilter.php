<?php

namespace DhlVendor\WPDesk\DhlExpressShippingService\DhlApi;

use DhlVendor\WPDesk\AbstractShipping\Rate\ShipmentRating;
use DhlVendor\WPDesk\AbstractShipping\Rate\SingleRate;
use DhlVendor\WPDesk\AbstractShipping\Shop\ShopSettings;
use DhlVendor\WPDesk\DhlExpressShippingService\Exception\NoRatesInCurrencyInRatingsException;
/**
 * Can filter rates by currency.
 *
 * @package WPDesk\DhlExpressShippingService\DhlApi
 */
class DhlRateCurrencyFilter implements ShipmentRating
{
    /** @var ShipmentRating */
    private $rating;
    /** Shipping method helper.
     *
     * @var ShopSettings
     */
    private $shop_settings;
    /**
     * .
     *
     * @param ShipmentRating $rating .
     * @param ShopSettings $shop_settings .
     */
    public function __construct(ShipmentRating $rating, ShopSettings $shop_settings)
    {
        $this->rating = $rating;
        $this->shop_settings = $shop_settings;
    }
    /**
     * Get filtered ratings.
     *
     * @return SingleRate[]
     */
    public function get_ratings()
    {
        $rates = [];
        $ratings = $this->rating->get_ratings();
        foreach ($ratings as $key => $rate) {
            if ($rate->total_charge->currency === $this->shop_settings->get_default_currency()) {
                $rates[$key] = $rate;
            }
        }
        if (0 !== count($ratings) && 0 === count($rates)) {
            throw new NoRatesInCurrencyInRatingsException();
        }
        return $rates;
    }
}
