<?php

declare(strict_types=1);
namespace Convo\Pckg\Appointments;


class FreeSlotValidatorFactory
{
    const KEY_MAX_PER_DAY           =   'max_per_day';
    const KEY_FIRST_NEXT            =   'first_next';
    const KEY_FIRST_SAME_TIME       =   'first_same_time';
    const KEY_FIRST_SAME_DAY_TIME   =   'first_same_day_and_time';
    const KEY_FIRST_NEXT_WEEK       =   'first_next_week';
    
    const TIME_FORMAT      =   'H:i';
    
    /**
     * @var \DateTime
     **/
    private $_time;
    
    public function __construct( $time)
    {
        $this->_time    =   $time;
    }
    
    public function getDefaultQueue()
    {
        $MAX      =   3;
        $queue    =   new FreeSlotQueue( $MAX, []);
        $queue->addValidator( $this->create( FreeSlotValidatorFactory::KEY_MAX_PER_DAY));
        $queue->addValidator( $this->create( FreeSlotValidatorFactory::KEY_FIRST_NEXT));
        $queue->addValidator( $this->create( FreeSlotValidatorFactory::KEY_FIRST_SAME_TIME));
        $queue->addValidator( $this->create( FreeSlotValidatorFactory::KEY_FIRST_SAME_DAY_TIME));
        $queue->addValidator( $this->create( FreeSlotValidatorFactory::KEY_FIRST_NEXT_WEEK));
        return $queue;
    }
    
    public function create( $key)
    {
        if ( $key == self::KEY_MAX_PER_DAY) {
            return new class( $this->_time) extends DefaultFreeSlotValidator {
                
                private $_days  =   [];
                
                public function add( $item)
                {
                    $slot_date  =   \DateTime::createFromFormat( 'U', strval( $item['timestamp']));
                    if ( $this->_time->format( 'W') < $slot_date->format( 'W')) {
                        return parent::add( $item);
                    }
                    return false;
                }
            };
        }
        
        if ( $key == self::KEY_FIRST_NEXT) {
            return new DefaultFreeSlotValidator( $this->_time);
        }
        
        if ( $key == self::KEY_FIRST_SAME_TIME) {
            return new class( $this->_time) extends DefaultFreeSlotValidator {
                
                public function add( $item) 
                {
                    $slot_date  =   \DateTime::createFromFormat( 'U', strval( $item['timestamp']));
                    if ( $this->_time->format( FreeSlotValidatorFactory::TIME_FORMAT) !== $slot_date->format( FreeSlotValidatorFactory::TIME_FORMAT)) {
                        return false;
                    }
                    return parent::add( $item);
                }
            };
        }
        
        if ( $key == self::KEY_FIRST_SAME_DAY_TIME) {
            return new class( $this->_time) extends DefaultFreeSlotValidator {
                
                public function add( $item) 
                {
                    $slot_date  =   \DateTime::createFromFormat( 'U', strval( $item['timestamp']));
                    if ( $this->_time->format( FreeSlotValidatorFactory::TIME_FORMAT) !== $slot_date->format( FreeSlotValidatorFactory::TIME_FORMAT)) {
                        return false;
                    }
                    if ( $this->_time->format( 'N') !== $slot_date->format( 'N')) {
                        return false;
                    }
                    return parent::add( $item);
                }
            };
        }
        
        if ( $key == self::KEY_FIRST_NEXT_WEEK) {
            return new class( $this->_time) extends DefaultFreeSlotValidator {

                public function add( $item) 
                {
                    $slot_date  =   \DateTime::createFromFormat( 'U', strval( $item['timestamp']));
                    if ( $this->_time->format( 'W') < $slot_date->format( 'W')) {
                        return parent::add( $item);
                    }
                    return false;
                }
            };
        }
        
        throw new \Exception( 'Unexpected key ['.$key.']');
    }
    
    // UTIL
    public function __toString()
    {
        return get_class( $this).'[]';
    }

}