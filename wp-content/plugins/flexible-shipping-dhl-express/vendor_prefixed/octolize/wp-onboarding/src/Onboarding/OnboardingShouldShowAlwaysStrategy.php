<?php

/**
 * @package Octolize\Onboarding
 */
namespace DhlVendor\Octolize\Onboarding;

/**
 * Always display strategy.
 */
class OnboardingShouldShowAlwaysStrategy implements OnboardingShouldShowStrategy
{
    public function should_display(): bool
    {
        return \true;
    }
}
