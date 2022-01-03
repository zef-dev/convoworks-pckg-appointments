<?php

namespace Convo\Pckg\Appointments;

use Convo\Core\Params\IServiceParamsScope;
use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;

class LoadAppointmentsElement extends AbstractAppointmentElement
{

	/**
	 * @var string
	 */
	private $_email;

	/**
	 * @var string
	 */
	private $_limit;

	/**
	 * @var string
	 */
	private $_mode;

	/**
	 * @var string
	 */
	private $_returnVar;

	/**
	 * @var IConversationElement[]
	 */
	private $_emptyFlow = array();

	/**
	 * @var IConversationElement[]
	 */
	private $_multipleFlow = array();

	/**
	 * @var IConversationElement[]
	 */
	private $_singleFlow = array();

	/**
	 * @param array $properties
	 */
	public function __construct( $properties)
	{
		parent::__construct( $properties);

		$this->_mode     		  			=   $properties['mode'];
		$this->_email     		  			=   $properties['email'];
		$this->_limit                       =   $properties['limit'];
		$this->_returnVar         			=   $properties['return_var'];

		foreach ( $properties['empty'] as $element) {
			$this->_emptyFlow[] = $element;
			$this->addChild($element);
		}

		foreach ( $properties['multiple'] as $element) {
			$this->_multipleFlow[] = $element;
			$this->addChild($element);
		}

		foreach ( $properties['single'] as $element) {
			$this->_singleFlow[] = $element;
			$this->addChild($element);
		}
	}

	/**
	 * @param IConvoRequest $request
	 * @param IConvoResponse $response
	 */
	public function read(IConvoRequest $request, IConvoResponse $response)
	{
		$context      	=   $this->_getAppointmentsContext();
		$email  =   $this->evaluateString($this->_email);
		$mode  =   $this->evaluateString($this->_mode);
		$numberOfAppointmentsToLoad  =   $this->evaluateString($this->_limit);
		$returnVar  =   $this->evaluateString($this->_returnVar);

		$this->_logger->info('Loading ['.$mode.'] appointments for customer email [' . $email . ']');


		$appointments = $context->loadAppointments($email, $mode, $numberOfAppointmentsToLoad);
		$appointmentsCount = count($appointments);
		$this->_logger->info('Loaded ['.$appointmentsCount.'] appointments for customer email [' . $email . ']');

		$scope_type   =   IServiceParamsScope::SCOPE_TYPE_REQUEST;
		$params       =   $this->getService()->getComponentParams($scope_type, $this);
		$params->setServiceParam($returnVar, ['appointments' => $appointments]);

		if ($appointmentsCount === 1) {
			$selected_flow = $this->_singleFlow;
		} else if ($appointmentsCount > 1) {
			$selected_flow = $this->_multipleFlow;
		} else {
			$selected_flow = $this->_emptyFlow;
		}

		foreach ($selected_flow as $element) {
			$element->read( $request, $response);
		}
	}

}