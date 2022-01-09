<?php

namespace Convo\Pckg\Appointments;

use Convo\Core\Workflow\AbstractWorkflowComponent;

class DefaultFreeSlotQueue extends AbstractWorkflowComponent implements IFreeSlotQueueFactory
{
    
    /**
     * @var string
     */
    private $_maxSuggestions;
    
	public function __construct( $properties)
	{
		parent::__construct( $properties);
		$this->_maxSuggestions    =   $properties['max_suggestions'];
	}

	public function createStack( $targetTime) {
	    $factory  =   new FreeSlotValidatorFactory( $targetTime);
	    $queue    =   $factory->getDefaultQueue( $this->evaluateString( $this->_maxSuggestions)); 
	    return $queue;
	}
    
    // UTIL
    public function __toString()
    {
        return parent::__toString().'['.$this->_maxSuggestions.']';
    }


}