<?php

namespace Convo\Pckg\Appointments;

use Convo\Core\Workflow\AbstractWorkflowComponent;
use Convo\Pckg\Appointments\Freeslot\IFreeSlotQueueFactory;
use Convo\Pckg\Appointments\Freeslot\DefaultFreeSlotValidator;
use Convo\Pckg\Appointments\Freeslot\FreeSlotQueue;

class DefaultFreeSlotQueue extends AbstractWorkflowComponent implements IFreeSlotQueueFactory
{
    const KEY_FIRST_NEXT            =   'first_next';
    const KEY_FIRST_SAME_TIME       =   'first_same_time';
    const KEY_FIRST_SAME_DAY_TIME   =   'first_same_day_and_time';
    const KEY_FIRST_NEXT_WEEK       =   'first_next_week';
    
    /**
     * @var string
     */
    private $_maxSuggestions;
    
	public function __construct( $properties)
	{
		parent::__construct( $properties);
		$this->_maxSuggestions    =   $properties['max_suggestions'];
	}

	/**
	 * {@inheritDoc}
	 * @see \Convo\Pckg\Appointments\Freeslot\IFreeSlotQueueFactory::createStack()
	 */
	public function createStack( $targetTime, $systemTimezone) {
	    
	    $queue    =   new FreeSlotQueue( $systemTimezone, $this->evaluateString( $this->_maxSuggestions));
	    
	    $queue->addValidator( $this->_create( self::KEY_FIRST_NEXT, $targetTime));
	    $queue->addValidator( $this->_create( self::KEY_FIRST_SAME_TIME, $targetTime));
	    $queue->addValidator( $this->_create( self::KEY_FIRST_SAME_DAY_TIME, $targetTime));
	    $queue->addValidator( $this->_create( self::KEY_FIRST_NEXT_WEEK, $targetTime));
	    
	    return $queue;
	}
	
	private function _create( $key, $targetTime)
	{
	    if ( $key == self::KEY_FIRST_NEXT) {
	        return new DefaultFreeSlotValidator( $targetTime);
	    }
	    
	    if ( $key == self::KEY_FIRST_SAME_TIME) {
	        return new class( $targetTime) extends DefaultFreeSlotValidator {
	            
	            public function add( $item)
	            {
	                $slot_date  =   \DateTime::createFromFormat( 'U', strval( $item['timestamp']));
	                if ( $this->_time->format( 'H:i') !== $slot_date->format( 'H:i')) {
	                    return false;
	                }
	                return parent::add( $item);
	            }
	        };
	    }
	    
	    if ( $key == self::KEY_FIRST_SAME_DAY_TIME) {
	        return new class( $targetTime) extends DefaultFreeSlotValidator {
	            
	            public function add( $item)
	            {
	                $slot_date  =   \DateTime::createFromFormat( 'U', strval( $item['timestamp']));
	                if ( $this->_time->format( 'H:i') !== $slot_date->format( 'H:i')) {
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
	        return new class( $targetTime) extends DefaultFreeSlotValidator {
	            
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
        return parent::__toString().'['.$this->_maxSuggestions.']';
    }


}