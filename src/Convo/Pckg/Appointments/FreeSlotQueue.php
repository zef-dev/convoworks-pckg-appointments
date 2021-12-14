<?php

declare(strict_types=1);
namespace Convo\Pckg\Appointments;


class FreeSlotQueue implements \Countable, \IteratorAggregate
{
    
    /**
     * @var array
     */
    private $_items =   [];

    /**
     * @var array
     */
    private $_config;
    
    /**
     * @var int
     */
    private $_maxCount;
    
    public function __construct( $maxCount, $config)
    {
        $this->_maxCount    =   $maxCount;
        $this->_config      =   $config;
    }
    
    
    public function add( $item)
    {
        $this->_items[] = $item;
    }
    
    public function values()
    {
        return $this->_items;
    }
    
    public function isFull()
    {
        return $this->count() >= $this->_maxCount;
    }
    
    // COUNTABLE
    public function count()
    {
        return $this->_items;
    }
    
    // ITERATOR AGREGATE
    public function getIterator()
    {
        return new \ArrayIterator( $this->_items);
    }
    
    // UTIL
    public function __toString()
    {
        return get_class( $this).'['.$this->count().']['.$this->_maxCount.']';
    }

}