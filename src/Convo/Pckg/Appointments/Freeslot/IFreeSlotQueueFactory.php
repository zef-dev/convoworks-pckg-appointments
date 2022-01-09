<?php

declare(strict_types=1);
namespace Convo\Pckg\Appointments\Freeslot;


interface IFreeSlotQueueFactory
{
    
    
    
    /**
     * @param \DateTime $targetTime
     * @return IFreeSlotQueue
     */
    public function createStack( $targetTime);
    
}