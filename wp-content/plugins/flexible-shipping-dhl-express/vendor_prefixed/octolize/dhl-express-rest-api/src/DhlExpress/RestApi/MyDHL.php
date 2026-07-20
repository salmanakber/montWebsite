<?php

declare (strict_types=1);
namespace DhlVendor\Octolize\DhlExpress\RestApi;

use DhlVendor\Octolize\DhlExpress\RestApi\Services\RateService;
use DhlVendor\Octolize\DhlExpress\RestApi\Services\ShipmentService;
class MyDHL
{
    protected Client $client;
    public function __construct(string $username, string $password, bool $testMode = \false)
    {
        $this->client = new Client($username, $password, $testMode);
    }
    public function enableMockServer(): void
    {
        $this->client->enableMockServer();
    }
    public function getRateService(): RateService
    {
        return new RateService($this->client);
    }
    public function getShipmentService(): ShipmentService
    {
        return new ShipmentService($this->client);
    }
    public function getClient(): Client
    {
        return $this->client;
    }
}
