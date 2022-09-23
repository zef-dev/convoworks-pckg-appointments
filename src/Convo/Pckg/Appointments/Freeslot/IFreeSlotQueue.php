<?php

declare(strict_types=1);
namespace Convo\Pckg\Appointments\Freeslot;


interface IFreeSlotQueue extends \Countable
{

    /**
     * @param array $item
     */
    public function add( $item);


    /**
     * @return bool
     */
    public function isFull();

    /**
     * @return array
     */
    public function values();

}
