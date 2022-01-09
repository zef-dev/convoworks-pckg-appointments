<?php

declare(strict_types=1);
namespace Convo\Pckg\Appointments\Freeslot;


class FreeSlotQueue implements \Countable, \IteratorAggregate, IFreeSlotQueue
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
     * @var int
     */
    private $_maxCount;
    
    private $_days  =   [];
    
    public function __construct( $maxCount)
    {
        $this->_maxCount    =   $maxCount;
    }
    
    
    public function addValidator( $validator) {
        $this->_validators[] = $validator;
    }
    
    public function add( $item)
    {
        foreach ( $this->_validators as $val) 
        {
            if ( $this->_blocked( $item)) {
                return;
            }
            if ( !$val->active()) {
                continue;
            }
            if ( $val->add( $item)) {
                $this->_register( $item);
                return;
            }
        }
    }
    
    private function _register( $item) {
        $date   =   \DateTimeImmutable::createFromFormat( 'U', strval( $item['timestamp']), new \DateTimeZone( $item['timezone']));
        $day    =   $date->format( 'Y-m-d');
        if ( !isset( $this->_days[$day])) {
            $this->_days[$day]  =   0;
        }
        $this->_days[$day]++;
    }
    
    private function _blocked( $item) {
        $date   =   \DateTimeImmutable::createFromFormat( 'U', strval( $item['timestamp']), new \DateTimeZone( $item['timezone']));
        $day    =   $date->format( 'Y-m-d');
        if ( isset( $this->_days[$day]) && $this->_days[$day] >= 2) {
            return true;
        }
    }
    
    public function values()
    {
        $values =   [];
        foreach ( $this->_validators as $val) {
            if ( $val->value()) {
                $values[]   =   $val->value();
            }
        }
        
        usort( $values, function ( $item1, $item2) {
            return $item1['timestamp'] <=> $item2['timestamp'];
        });
        
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