<?php

namespace DhlVendor\Octolize\Tracker\DeactivationTracker;

use DhlVendor\WPDesk\Tracker\Deactivation\Reason;
use DhlVendor\WPDesk\Tracker\Deactivation\ReasonsFactory;
class OctolizeProReasonsFactory implements ReasonsFactory
{
    private OctolizeReasonsFactory $reasons_factory;
    public function __construct(string $plugin_docs_url = '', string $contact_us_url = '')
    {
        $this->reasons_factory = new OctolizeReasonsFactory($plugin_docs_url, '', '', $contact_us_url);
    }
    /**
     * Create reasons.
     *
     * @return Reason[]
     */
    public function createReasons(): array
    {
        $reasons = $this->reasons_factory->createReasons();
        $reasons[OctolizeReasonsFactory::MISSING_FEATURE]->setDescription(__('Can you let us know, what functionality you\'re looking for?', 'flexible-shipping-dhl-express'));
        return $reasons;
    }
}
