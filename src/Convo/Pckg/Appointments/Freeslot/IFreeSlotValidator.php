<?php

declare(strict_types=1);
namespace Convo\Pckg\Appointments\Freeslot;


interface IFreeSlotValidator
{
    
    /**
     * @param array $item
     * @return bool true when actually added
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