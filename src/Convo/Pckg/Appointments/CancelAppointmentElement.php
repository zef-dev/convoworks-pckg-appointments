<?php

namespace Convo\Pckg\Appointments;

use Convo\Core\Adapters\Alexa\Api\AlexaSettingsApi;
use Convo\Core\DataItemNotFoundException;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;

class CancelAppointmentElement extends AbstractAppointmentElement
{

	/**
	 * @var string
	 */
	private $_appointmentId;

	/**
	 * @var string
	 */
	private $_email;

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
	public function __construct( $properties)
	{
		parent::__construct( $properties);

		$this->_appointmentId     =   $properties['appointment_id'];
		$this->_email   		  =   $properties['email'];

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
	 * @param IConvoRequest $request
	 * @param IConvoResponse $response
	 */
	public function read(IConvoRequest $request, IConvoResponse $response)
	{
		$context      	=   $this->_getSimpleSchedulingContext();
		$appointmentId  =   $this->evaluateString($this->_appointmentId);
		$email          =   $this->evaluateString($this->_email);

		$this->_logger->info('Canceling appointment with id ['.$appointmentId.'] for customer email [' . $email . ']');

		try {
			$context->cancelAppointment($email, $appointmentId);
			$this->_logger->info('Canceled appointment with id ['. $appointmentId .'] for the customers email [' . $email . ']');
			$selected_flow = $this->_okFlow;
		}  catch (DataItemNotFoundException $e) {
			$this->_logger->info($e->getMessage());
			$selected_flow = $this->_notFoundFlow;
		}

		foreach ($selected_flow as $element) {
			$element->read( $request, $response);
		}
	}

}