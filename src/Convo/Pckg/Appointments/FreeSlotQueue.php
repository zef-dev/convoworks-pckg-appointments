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
//     private $_values =   [];
    
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
    
    
    public function addValidator( $key, $validator) {
        $this->_validators[$key] = $validator;
    }
    
    public function add( $item)
    {
//         if ( isset( $this->_values[strval($item['timestamp'])])) {
//             return;
//         }
        
        foreach ( $this->_validators as $key => $val) 
        {
            if ( isset( $this->_items[$key])) {
                continue;
            }
            
            if ( $val->isValid( $item)) {
                $this->_items[$key] = $item;
//                 $this->_values[strval($item['timestamp'])] =   true;
                return ;
            }
        }
    }
    
    public function values()
    {
        return array_values( $this->_items);
    }
    
    public function isFull()
    {
        return $this->count() >= $this->_maxCount;
    }
    
    // COUNTABLE
    public function count()
    {
        return count( $this->_items);
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