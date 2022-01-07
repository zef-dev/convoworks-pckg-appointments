<?php

declare(strict_types=1);
namespace Convo\Pckg\Appointments;


interface IFreeSlotValidator
{
    
    /**
     * @param array $item
     */
    public function add( $item);

    /**
     * @param array $item
     * @return bool
     */
    public function active();
    
    
    /**
     * @return array
     */
    public function values();
    
}