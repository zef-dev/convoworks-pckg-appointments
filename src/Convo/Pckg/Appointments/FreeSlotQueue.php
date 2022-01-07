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
     * @var IFreeSlotValidator[]
     */
    private $_validators =   [];

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
    
    
    public function addValidator( $validator) {
        $this->_validators[] = $validator;
    }
    
    public function add( $item)
    {
        foreach ( $this->_validators as $val) 
        {
            if ( !$val->active()) {
                continue;
            }
            $val->add( $item);
        }
    }
    
    public function values()
    {
        $values =   [];
        foreach ( $this->_validators as $val) {
            $values =   array_merge( $values, $val->values());
        }
        return $values;
    }
    
    public function isFull()
    {
        return $this->count() >= $this->_maxCount;
    }
    
    // COUNTABLE
    public function count()
    {
        return count( $this->values());
    }
    
    // ITERATOR AGREGATE
    public function getIterator()
    {
        return new \ArrayIterator( $this->values());
    }
    
    // UTIL
    public function __toString()
    {
        return get_class( $this).'['.$this->count().']['.$this->_maxCount.']';
    }

}