<?php

namespace DhlVendor\WPDesk\DhlExpressShippingService\DhlApi\RestApi;

use DhlVendor\Octolize\DhlExpress\RestApi\MyDHL;
use DhlVendor\Octolize\DhlExpress\RestApi\ValueObjects\Account;
use DhlVendor\Octolize\DhlExpress\RestApi\ValueObjects\Package;
use DhlVendor\Octolize\DhlExpress\RestApi\ValueObjects\RateAddress;
use DhlVendor\Psr\Log\LoggerInterface;
use DhlVendor\WPDesk\AbstractShipping\Settings\SettingsValues;
use DhlVendor\WPDesk\DhlExpressShippingService\DhlApi\ApiConnectionChecker;
use DhlVendor\WPDesk\DhlExpressShippingService\DhlSettingsDefinition;
class RestApiConnectionChecker implements ApiConnectionChecker
{
    /**
     * Settings.
     *
     * @var SettingsValues
     */
    private $settings;
    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    private $logger;
    /** @var bool */
    private $is_testing;
    /**
     * ConnectionChecker constructor.
     *
     * @param SettingsValues $settings .
     * @param LoggerInterface $logger .
     * @param bool $is_testing .
     */
    public function __construct(SettingsValues $settings, LoggerInterface $logger, $is_testing)
    {
        $this->settings = $settings;
        $this->logger = $logger;
        $this->is_testing = $is_testing;
    }
    /**
     * Pings API.
     * Throws exception on failure.
     *
     * @return void
     * @throws \Exception .
     */
    public function check_connection()
    {
        $myDhl = new MyDHL($this->settings->get_value(DhlSettingsDefinition::FIELD_API_KEY, ''), $this->settings->get_value(DhlSettingsDefinition::FIELD_API_SECRET, ''), $this->is_testing);
        $rateService = $myDhl->getRateService();
        $originAddress = new RateAddress('DE', '10117', 'Berlin');
        $destinationAddress = new RateAddress('DE', '20099', 'Hamburg');
        $package = new Package(
            1,
            // kg
            2,
            // cm
            2,
            // cm
            20
        );
        $shippingDate = new \DateTimeImmutable('now');
        try {
            $rateService->addAccount(new Account('shipper', $this->settings->get_value(DhlSettingsDefinition::FIELD_ACCOUNT_NUMBER, '')))->setOriginAddress($originAddress)->setDestinationAddress($destinationAddress)->setPlannedShippingDate($shippingDate)->addPackage($package)->setNextBusinessDay(\true)->setCustomsDeclarable(\false)->setPayerCountryCode('DE')->getRates();
        } catch (\Exception $e) {
            if ($this->allowed_exception($e)) {
                return;
            }
            throw $e;
        }
    }
    private function allowed_exception(\Exception $e): bool
    {
        // Bad request 410301: Product not available between this origin and destination (network segment).
        return strpos($e->getMessage(), 'Bad request 410301') !== \false;
    }
}
