<?php

declare(strict_types=1);

namespace Convo\Pckg\Appointments\Freeslot;

use Convo\Core\Workflow\IBasicServiceComponent;

interface IFreeSlotQueueFactory extends IBasicServiceComponent
{



    /**
     * @param \DateTime $targetTime
     * @param \DateTimeZone $systemTimezone
     * @return IFreeSlotQueue
     */
    public function createStack($targetTime, $systemTimezone);
}
