<?php

namespace Convo\Pckg\Appointments;

use Convo\Core\Adapters\Alexa\Api\AlexaSettingsApi;
use Convo\Core\DataItemNotFoundException;
use Convo\Core\Params\IServiceParamsScope;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;

class LoadAppointmentElement extends AbstractWorkflowContainerComponent implements IConversationElement
{
	/**
	 * @var string
	 */
	private $_contextId;

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
	public function __construct( $properties)
	{
		parent::__construct( $properties);

		$this->_contextId         			=   $properties['context_id'];
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
	 * @param IConvoRequest $request
	 * @param IConvoResponse $response
	 */
	public function read(IConvoRequest $request, IConvoResponse $response)
	{
		$context      	=   $this->_getSimpleSchedulingContext();
		$email  =   $this->evaluateString($this->_email);
		$appointmentId  =   $this->evaluateString($this->_appointmentId);

		$returnVar  =   $this->evaluateString($this->_returnVar);

		$this->_logger->info('Loading appointment with id ['.$appointmentId.'] for customer email [' . $email . ']');

		try {
			$appointment = $context->getAppointment($email, $appointmentId);

			$this->_logger->info('Loaded appointment with id ['.$appointmentId.'] appointments for customer email [' . $email . ']');

			$scope_type   =   IServiceParamsScope::SCOPE_TYPE_REQUEST;
			$params       =   $this->getService()->getComponentParams($scope_type, $this);
			$params->setServiceParam($returnVar, ['appointment' => $appointment]);

			$selected_flow = $this->_okFlow;
		}  catch (DataItemNotFoundException $e) {
			$this->_logger->info($e->getMessage());
			$selected_flow = $this->_notFoundFlow;
		}

		foreach ($selected_flow as $element) {
			$element->read( $request, $response);
		}
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