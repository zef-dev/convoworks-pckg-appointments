<?php

declare(strict_types=1);
namespace Convo\Pckg\Appointments;


interface IFreeSlotValidator
{
    
    /**
     * @param array $item
     * @return bool 
     */
    public function isValid( $item);
    
}