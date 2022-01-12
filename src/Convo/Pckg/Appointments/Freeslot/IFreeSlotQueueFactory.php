<?php

declare(strict_types=1);
namespace Convo\Pckg\Appointments\Freeslot;


interface IFreeSlotQueueFactory
{
    
    
    
    /**
     * @param \DateTime $targetTime
     * @param \DateTimeZone $systemTimezone
     * @return IFreeSlotQueue
     */
    public function createStack( $targetTime, $systemTimezone);
    
}