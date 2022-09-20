<?php

namespace SmartContact\TrustpilotService;

use Illuminate\Support\Facades\Facade;

/**
 * @see \SmartContact\TrustpilotService\Skeleton\SkeletonClass
 */
class TrustpilotServiceFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'trustpilot-service';
    }
}
