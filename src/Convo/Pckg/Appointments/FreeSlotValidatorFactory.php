<?php

declare(strict_types=1);
namespace Convo\Pckg\Appointments;


class FreeSlotValidatorFactory
{
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
    
    
    public function create( $key)
    {
        if ( $key == self::KEY_FIRST_NEXT) {
            return new class() implements IFreeSlotValidator {
                public function isValid( $item)
                {
                    return true;
                }
            };
        }
        
        if ( $key == self::KEY_FIRST_SAME_TIME) {
            return new class( $this->_time) implements IFreeSlotValidator {
                
                /**
                 * @var \DateTime
                 **/
                private $_time;
                
                public function __construct( $time) {
                    $this->_time    =   $time;
                }
                
                public function isValid( $item) 
                {
                    
                    $slot_date  =   \DateTime::createFromFormat( 'U', strval( $item['timestamp']));
                    if ( $this->_time->format( FreeSlotValidatorFactory::TIME_FORMAT) !== $slot_date->format( FreeSlotValidatorFactory::TIME_FORMAT)) {
                        return false;
                    }
                    return true;
                }
            };
        }
        
        if ( $key == self::KEY_FIRST_SAME_DAY_TIME) {
            return new class( $this->_time) implements IFreeSlotValidator {
                
                /**
                 * @var \DateTime
                 **/
                private $_time;
                
                public function __construct( $time) {
                    $this->_time    =   $time;
                }
                
                public function isValid( $item) 
                {
                    $slot_date  =   \DateTime::createFromFormat( 'U', strval( $item['timestamp']));
                    if ( $this->_time->format( FreeSlotValidatorFactory::TIME_FORMAT) !== $slot_date->format( FreeSlotValidatorFactory::TIME_FORMAT)) {
                        return false;
                    }
                    if ( $this->_time->format( 'N') !== $slot_date->format( 'N')) {
                        return false;
                    }
                    return true;
                }
            };
        }
        
        if ( $key == self::KEY_FIRST_NEXT_WEEK) {
            return new class( $this->_time) implements IFreeSlotValidator {
                
                /**
                 * @var \DateTime
                 **/
                private $_time;
                
                public function __construct( $time) {
                    $this->_time    =   $time;
                }
                
                public function isValid( $item) 
                {
                    $slot_date  =   \DateTime::createFromFormat( 'U', strval( $item['timestamp']));
                    if ( $this->_time->format( 'W') < $slot_date->format( 'W')) {
                        return true;
                    }
                    return false;
                }
            };
        }
    }
    
    // UTIL
    public function __toString()
    {
        return get_class( $this).'[]';
    }

}