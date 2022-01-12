<?php

namespace Convo\Pckg\Appointments;

use Convo\Core\Adapters\Alexa\Api\AlexaSettingsApi;
use Convo\Core\DataItemNotFoundException;
use Convo\Core\Params\IServiceParamsScope;
use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;

class LoadAppointmentElement extends AbstractAppointmentElement
{

	/**
	 * @var string
	 */
	private $_email;

	/**
	 * @var string
	 */
	private $_appointmentId;

	/**
	 * @var string
	 */
	private $_returnVar;

	/**
	 * @var IConversationElement[]
	 */
	private $_okFlow = array();

	/**
	 * @var IConversationElement[]
	 */
	private $_notFoundFlow = array();

	/**
	 * @param array $properties
	 * @param AlexaSettingsApi $alexaSettingsApi
	 */
	public function __construct( $properties, AlexaSettingsApi $alexaSettingsApi)
	{
	    parent::__construct( $properties, $alexaSettingsApi);

		$this->_appointmentId     		  	=   $properties['appointment_id'];
		$this->_email     		  			=   $properties['email'];
		$this->_returnVar         			=   $properties['return_var'];

		foreach ( $properties['ok'] as $element) {
			$this->_okFlow[] = $element;
			$this->addChild($element);
		}

		foreach ( $properties['not_found'] as $element) {
			$this->_notFoundFlow[] = $element;
            $this->addChild($element);
        }
    }

    /**
     *
     * @param IConvoRequest $request
     * @param IConvoResponse $response
     */
    public function read(IConvoRequest $request, IConvoResponse $response)
    {
        $context        =   $this->_getAppointmentsContext();
        $email          =   $this->evaluateString($this->_email);
		$appointmentId  =   $this->evaluateString( $this->_appointmentId);
		$returnVar      =   $this->evaluateString( $this->_returnVar);

		$this->_logger->info( 'Loading appointment with id ['.$appointmentId.'] for customer email ['.$email.']');
        
		$data           =   ['appointment' => null];
		
		try {
		    $data['appointment'] = $context->getAppointment( $email, $appointmentId);
			$this->_logger->info('Loaded appointment with id ['.$appointmentId.'] appointments for customer email [' . $email . ']');
			$selected_flow = $this->_okFlow;
		}  catch ( DataItemNotFoundException $e) {
			$this->_logger->info($e->getMessage());
			$selected_flow = $this->_notFoundFlow;
		}
		
		$params       =   $this->getService()->getComponentParams( IServiceParamsScope::SCOPE_TYPE_REQUEST, $this);
		$params->setServiceParam( $returnVar, $data);

		$this->_readElementsInTimezone( $selected_flow, $request, $response);
	}
}