<?php

namespace Convo\Pckg\Appointments;

use Convo\Core\DataItemNotFoundException;
use Convo\Core\Params\IServiceParamsScope;
use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;

class UpdateAppointmentElement extends AbstractAppointmentElement
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
	private $_notFoundFlow = array();

	/**
	 * @var IConversationElement[]
	 */
	private $_notAvailableFlow = array();

	/**
	 * @param array $properties
	 */
	public function __construct( $properties)
	{
		parent::__construct( $properties);

		$this->_appointmentId     =   $properties['appointment_id'];
		$this->_email   		  =   $properties['email'];
		$this->_appointmentDate   =   $properties['appointment_date'];
		$this->_appointmentTime   =   $properties['appointment_time'];
		$this->_payload   		  =   $properties['payload'];
		$this->_resultVar         =   $properties['result_var'];

		foreach ( $properties['ok'] as $element) {
			$this->_okFlow[] = $element;
			$this->addChild($element);
		}

		foreach ( $properties['not_found'] as $element) {
			$this->_notFoundFlow[] = $element;
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
	public function read(IConvoRequest $request, IConvoResponse $response)
	{
		$context      	=   $this->_getSimpleSchedulingContext();
		$appointmentId  =   $this->evaluateString($this->_appointmentId);
		$email          =   $this->evaluateString($this->_email);
		$date           =   $this->evaluateString($this->_appointmentDate);
		$time           =   $this->evaluateString($this->_appointmentTime);
		$payload        =   $this->_evaluateArgs($this->_payload);
		$resultVar      =   $this->evaluateString($this->_resultVar);

		$this->_logger->info('Creating appointment at ['.$date.']['.$time.'] for customer email [' . $email . ']');
		$slot_time    =   new \DateTime($date.' '.$time);

		try {
			$updatedAppointment = $context->updateAppointment($email, $appointmentId, $slot_time, $payload);

			$scope_type   =   IServiceParamsScope::SCOPE_TYPE_REQUEST;
			$params       =   $this->getService()->getComponentParams($scope_type, $this);
			$params->setServiceParam($resultVar, $updatedAppointment);

			$this->_logger->info('Updated appointment successfully ['.json_encode($updatedAppointment).']');

			$selected_flow = $this->_okFlow;
		} catch (SlotNotAvailableException $e) {
			$this->_logger->info($e->getMessage());
			$selected_flow = $this->_notAvailableFlow;
		} catch (DataItemNotFoundException $e) {
			$this->_logger->info($e->getMessage());
			$selected_flow = $this->_notFoundFlow;
		}

		foreach ($selected_flow as $element) {
			$element->read( $request, $response);
		}
	}
}