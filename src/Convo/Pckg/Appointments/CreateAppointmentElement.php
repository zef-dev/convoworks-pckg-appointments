<?php

namespace Convo\Pckg\Appointments;

use Convo\Core\Params\IServiceParamsScope;
use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Adapters\Alexa\Api\AlexaSettingsApi;

class CreateAppointmentElement extends AbstractAppointmentElement
{

	/**
	 * @var string
	 */
	private $_email;

	/**
	 * @var string
	 */
	private $_appointmentDate;

	/**
	 * @var string
	 */
	private $_appointmentTime;

	/**
	 * @var array
	 */
	private $_payload;

	/**
	 * @var string
	 */
	private $_resultVar;

	/**
	 * @var IConversationElement[]
	 */
	private $_okFlow = array();

	/**
	 * @var IConversationElement[]
	 */
	private $_notAvailableFlow = array();

	/**
	 * @param array $properties
	 * @param AlexaSettingsApi $alexaSettingsApi
	 */
	public function __construct( $properties, AlexaSettingsApi $alexaSettingsApi)
	{
	    parent::__construct( $properties, $alexaSettingsApi);

		$this->_email   		  =   $properties['email'];
		$this->_appointmentDate   =   $properties['appointment_date'];
		$this->_appointmentTime   =   $properties['appointment_time'];
		$this->_payload   		  =   $properties['payload'];
		$this->_resultVar         =   $properties['result_var'];

		foreach ( $properties['ok'] as $element) {
			$this->_okFlow[] = $element;
			$this->addChild($element);
		}

		foreach ( $properties['not_available'] as $element) {
			$this->_notAvailableFlow[] = $element;
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
		$timezone     =   $this->_getTimezone( $request);
		$email        =   $this->evaluateString( $this->_email);
		$date         =   $this->evaluateString( $this->_appointmentDate);
		$time         =   $this->evaluateString( $this->_appointmentTime);
		$payload      =   $this->_evaluateArgs( $this->_payload);

		$this->_logger->info('Creating appointment at ['.$date.']['.$time.'] for customer email [' . $email . ']');
		$slot_time    =   new \DateTime( $date.' '.$time, $timezone);

		$params       =   $this->getService()->getComponentParams( IServiceParamsScope::SCOPE_TYPE_REQUEST, $this);
		
		// TODO add exception for invalid data
		try {
		    $appointment_id   =   $context->createAppointment($email, $slot_time, $payload);
			$params->setServiceParam( 
			    $this->_resultVar, 
			    ['appointment_id' => $appointment_id, 'timezone' => $timezone->getName(), 'requested_time' => $slot_time->getTimestamp()]);
			$this->_logger->info( 'Created appointment successfully ['.$appointment_id.']');
			$selected_flow = $this->_okFlow;
		} catch ( SlotNotAvailableException $e) {
			$this->_logger->info( $e->getMessage());
			$selected_flow = $this->_notAvailableFlow;
			$params->setServiceParam(
			    $this->_resultVar,
			    ['appointment_id' => null, 'timezone' => $timezone->getName(), 'requested_time' => $slot_time->getTimestamp()]);
		}

		foreach ($selected_flow as $element) {
			$element->read( $request, $response);
		}
	}

}