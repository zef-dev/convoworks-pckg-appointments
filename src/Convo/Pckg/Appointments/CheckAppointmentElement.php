<?php 

declare(strict_types=1);
namespace Convo\Pckg\Appointments;

use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Core\Adapters\Alexa\Api\AlexaSettingsApi;
use Convo\Core\Params\IServiceParamsScope;


class CheckAppointmentElement extends AbstractWorkflowContainerComponent implements IConversationElement
{
    
    /**
     * @var string
     */
	private $_contextId;
	
	/**
	 * @var string
	 */
	private $_appointmentDate;
	
	/**
	 * @var string
	 */
	private $_appointmentTime;
	
	/**
	 * @var string
	 */
	private $_resultVar;

	/**
	 * @var IConversationElement[]
	 */
	private $_availableFlow = array();

	/**
	 * @var IConversationElement[]
	 */
	private $_noSuggestionsFlow = array();
	
	/**
	 * @var IConversationElement[]
	 */
	private $_suggestionsFlow = array();
	
	/**
	 * @var IConversationElement[]
	 */
	private $_singleSuggestionFlow = array();
	
	/**
	 * @var AlexaSettingsApi
	 */
	private $_alexaSettingsApi;

	/**
	 * @param array $properties
	 * @param AlexaSettingsApi $alexaSettingsApi
	 */
	public function __construct( $properties, $alexaSettingsApi)
	{
		parent::__construct( $properties);
		
		$this->_alexaSettingsApi  =   $alexaSettingsApi;
		
		$this->_contextId         =   $properties['context_id'];
		$this->_resultVar         =   $properties['result_var'];
		$this->_appointmentDate   =   $properties['appointment_date'];
		$this->_appointmentTime   =   $properties['appointment_time'];

		foreach ( $properties['available_flow'] as $element) {
			$this->_availableFlow[] = $element;
			$this->addChild($element);
		}

		foreach ( $properties['no_suggestions_flow'] as $element) {
			$this->_noSuggestionsFlow[] = $element;
			$this->addChild($element);
		}

		foreach ( $properties['suggestions_flow'] as $element) {
			$this->_suggestionsFlow[] = $element;
			$this->addChild($element);
		}

		foreach ( $properties['single_suggestion_flow'] as $element) {
			$this->_singleSuggestionFlow[] = $element;
			$this->addChild($element);
		}
	}

	/**
	 * @param IConvoRequest $request
	 * @param IConvoResponse $response
	 */
	public function read( IConvoRequest $request, IConvoResponse $response)
	{
		$context      =   $this->_getSimpleSchedulingContext();
		
		$date         =   $this->evaluateString( $this->_appointmentDate);
		$time         =   $this->evaluateString( $this->_appointmentTime);
		
		$timezone     =   $this->_alexaSettingsApi->getSetting( $request, AlexaSettingsApi::ALEXA_SYSTEM_TIMEZONE);
		$slot_time    =   new \DateTime( $date.' '.$time, new \DateTimeZone( $timezone));
		
		if ( $date && $time) 
		{
		    if ( $context->isSlotAvailable( $slot_time)) {
		        foreach ( $this->_availableFlow as $element) {
		            $element->read( $request, $response);
		        }
		        return ;
		    }
		}
		
		$MAX      =   3;
		$queue    =   new FreeSlotQueue( $MAX);
		foreach ( $context->getFreeSlotsIterator( $slot_time) as $time) 
		{
		    $queue->add( $time);
		    if ( $queue->isFull()) {
		        break;
		    }
		}
        
		$scope_type	  =   IServiceParamsScope::SCOPE_TYPE_REQUEST;
		$params       =   $this->getService()->getComponentParams( $scope_type, $this);
		$params->setServiceParam( $this->_resultVar, [ 'suggestions' => $queue->values()]);

		if ( $queue->getCount() === 0) {
		    $selected_flow   =   $this->_noSuggestionsFlow;
		} else if ( $queue->getCount() === 1) {
		    $selected_flow   =   $this->_fallbackSuggestionFlows( $this->_singleSuggestionFlow);
		} else {
		    $selected_flow   =   $this->_fallbackSuggestionFlows( $this->_suggestionsFlow);
		}
		
		foreach ( $selected_flow as $element) {
		    $element->read( $request, $response);
		}
	}
	
	
	/**
	 * @param \Convo\Core\Workflow\IConversationElement[] $flow
	 * @return \Convo\Core\Workflow\IConversationElement[]
	 */
	private function _fallbackSuggestionFlows( $flow) {
	    if ( $flow === $this->_singleSuggestionFlow && empty( $flow)) {
	        return $this->_fallbackSuggestionFlows( $this->_suggestionsFlow);
	    }
	    if ( $flow === $this->_suggestionsFlow && empty( $flow)) {
	        return $this->_noSuggestionsFlow;
	    }
	    return $flow;
	}

	/**
	 * @return IAppointmentsContext
	 */
	private function _getSimpleSchedulingContext()
	{
		return $this->getService()->findContext(
			$this->evaluateString( $this->_contextId),
		    IAppointmentsContext::class);
	}
}