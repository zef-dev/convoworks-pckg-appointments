<?php

declare(strict_types=1);
namespace Convo\Pckg\Appointments;

use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Adapters\Alexa\Api\AlexaSettingsApi;
use Convo\Core\Params\IServiceParamsScope;
use Convo\Pckg\Appointments\Freeslot\IFreeSlotQueueFactory;


class CheckAppointmentTimeElement extends AbstractAppointmentElement
{
    
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
     * @var IFreeSlotQueueFactory
     */
    private $_suggestionsBuilder;
    
    /**
     * @param array $properties
     * @param AlexaSettingsApi $alexaSettingsApi
     */
    public function __construct( $properties, $alexaSettingsApi)
    {
        parent::__construct( $properties, $alexaSettingsApi);
        
        $this->_resultVar           =   $properties['result_var'];
        $this->_appointmentDate     =   $properties['appointment_date'];
        $this->_appointmentTime     =   $properties['appointment_time'];
        $this->_suggestionsBuilder  =   $properties['suggestions_builder'];
        
        $this->addChild( $this->_suggestionsBuilder);
        
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
        $context      =   $this->_getAppointmentsContext();
        $date         =   $this->evaluateString( $this->_appointmentDate);
        $time         =   $this->evaluateString( $this->_appointmentTime);
		$timezone     =   $this->_getTimezone( $request);
		$scope_type   =   IServiceParamsScope::SCOPE_TYPE_REQUEST;
		$params       =   $this->getService()->getComponentParams( $scope_type, $this);
		
        $this->_logger->info( 'Checking time ['.$date.']['.$time.']['.$timezone->getName().']');
        
        $slot_time    =   new \DateTimeImmutable( $date.' '.$time, $timezone);
        $data         =   [
            'suggestions' => [], 
            'requested_time' => $slot_time->getTimestamp(),
            'requested_timezone' => $timezone->getName(), 
            'not_allowed' => false
        ];
        
        if ( $date && $time)
        {
            $this->_logger->debug( 'Going to check if the slot is available ...');
            try {
                if ( $context->isSlotAvailable( $slot_time)) {
                    $this->_logger->info( 'Requested slot is available');
                    $params->setServiceParam( $this->_resultVar, $data);
                    $this->_readElementsInTimezone( $this->_availableFlow, $timezone, $request, $response);
                    return ;
                }
                $this->_logger->info( 'Requested slot is not available');
            } catch ( OutOfBusinessHoursException $e) {
                $this->_logger->info( $e->getMessage());
                $data['not_allowed'] = true;
            }
        }
        
        if ( $this->_suggestionsBuilder) 
        {
            $this->_logger->debug( 'Going to build suggestions ...');
            $queue  =   $this->_suggestionsBuilder->createStack( $slot_time);
            foreach ( $context->getFreeSlotsIterator( $slot_time) as $time)
            {
                $queue->add( $time);
                if ( $queue->isFull()) {
                    break;
                }
            }
            $data['suggestions'] = $queue->values();
            
            $queueCount = $queue->count();
            $this->_logger->info( 'Got ['.$queueCount.'] suggestions');
            
            if ( $queueCount === 0) {
                $selected_flow   =   $this->_noSuggestionsFlow;
            } else if ( $queueCount === 1) {
                $selected_flow   =   $this->_fallbackSuggestionFlows( $this->_singleSuggestionFlow);
            } else {
                $selected_flow   =   $this->_fallbackSuggestionFlows( $this->_suggestionsFlow);
            }
        }
        else
        {
            $selected_flow   =   $this->_noSuggestionsFlow;
        }
        
        $params->setServiceParam( $this->_resultVar, $data);
        
        $this->_readElementsInTimezone( $selected_flow, $timezone, $request, $response);
    }
    
    
    /**
     * If the target flow is empty, it will return another one. That way we are not orceing operators to fill in all three flows if their service workflow is a simple one.
     * @param \Convo\Core\Workflow\IConversationElement[] $flow
     * @return \Convo\Core\Workflow\IConversationElement[]
     */
    private function _fallbackSuggestionFlows( $flow) {
        if ( $flow === $this->_singleSuggestionFlow && empty( $flow)) {
            return $this->_suggestionsFlow;
        }
        if ( $flow === $this->_noSuggestionsFlow && empty( $flow)) {
            $this->_logger->debug( 'Returning no suggestions flow');
            return $this->_suggestionsFlow;
        }
        $this->_logger->debug( 'Returning original flow');
        return $flow;
    }
    
}