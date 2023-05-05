<?php

namespace Convo\Pckg\Appointments;

use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Adapters\Alexa\Api\AlexaSettingsApi;
use Convo\Core\Workflow\IConvoResponse;

abstract class AbstractAppointmentElement extends AbstractWorkflowContainerComponent implements IConversationElement
{
    const TIMEZONE_MODE_DEFAULT  =   'DEFAULT';
    const TIMEZONE_MODE_CLIENT   =   'CLIENT';
    const TIMEZONE_MODE_SET      =   'SET';
    
	/**
	 * @var string
	 */
	protected $_contextId;
    
	/**
	 * @var string
	 */
	protected $_timezoneMode;
    
	/**
	 * @var string
	 */
	protected $_timezone;

	/**
	 * @var AlexaSettingsApi
	 */
	private $_alexaSettingsApi;

	/**
	 * @param array $properties
	 */
	public function __construct( $properties, AlexaSettingsApi $alexaSettingsApi)
	{
		parent::__construct( $properties);

		$this->_contextId         =   $properties['context_id'];
		$this->_timezoneMode      =   $properties['timezone_mode'];
		$this->_timezone          =   $properties['timezone'];
		
		$this->_alexaSettingsApi  =   $alexaSettingsApi;
	}
	

	/**
	 * @param IConversationElement[] $elements
     * @param IConvoRequest $request
     * @param IConvoResponse $response
	 * @throws \Throwable
	 */
	protected function _readElementsInTimezone( $elements, $request, $response) 
	{
	    $timezone       =   $this->_getTimezone( $request);
	    
	    $default  =    date_default_timezone_get();
	    
	    try {
	        date_default_timezone_set( $timezone->getName());
	        foreach ($elements as $element) {
	            $element->read( $request, $response);
	        }
	    } catch ( \Throwable $e) {
	        throw $e;
	    } finally {
	        date_default_timezone_set( $default);
	    }
	}
	
	/**
	 * @param IConvoRequest $request
	 * @return \DateTimeZone
	 */
	protected function _getTimezone( IConvoRequest $request)
	{
	    $mode      =   $this->evaluateString( $this->_timezoneMode);
	    
	    if ( $mode === self::TIMEZONE_MODE_DEFAULT) {
	        return $this->_getAppointmentsContext()->getDefaultTimezone();
	    }
	    
	    if ( $mode === self::TIMEZONE_MODE_CLIENT) {
	        if ( is_a( $request, \Convo\Core\Adapters\Alexa\AmazonCommandRequest::class)) {
	            return $this->_alexaSettingsApi->getTimezone( $request);
	        }
	        if ( is_a( $request, \Convo\Core\Workflow\ITimezoneAwareRequest::class)) {
	            /* @var \Convo\Core\Workflow\ITimezoneAwareRequest $request */
	            return $request->getTimeZone();
	        }
	        return $this->_getAppointmentsContext()->getDefaultTimezone();
	    } 
	    
	    if ( $mode === self::TIMEZONE_MODE_SET) {
	        return new \DateTimeZone( $this->evaluateString( $this->_timezone));
	    }
	    
	    throw new \Exception( 'Unexpected timezone mode ['.$mode.'] from ['.$this->_timezoneMode.']');
	}

	/**
	 * @return IAppointmentsContext
	 */
	protected function _getAppointmentsContext()
	{
		return $this->getService()->findContext(
			$this->evaluateString( $this->_contextId),
			IAppointmentsContext::class);
	}

	
	// UTIL
	public function __toString()
	{
	    return parent::__toString().'['.$this->_contextId.']';
	}
}