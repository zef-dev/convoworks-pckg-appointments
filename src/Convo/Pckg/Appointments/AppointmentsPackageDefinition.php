<?php declare(strict_types=1);

namespace Convo\Pckg\Appointments;

use Convo\Core\Adapters\Alexa\Api\AlexaSettingsApi;
use Convo\Core\Factory\AbstractPackageDefinition;
use Convo\Core\Factory\IComponentFactory;

class AppointmentsPackageDefinition extends AbstractPackageDefinition
{
	const NAMESPACE = 'convo-appointments';

	/**
	 * @var AlexaCustomerProfileApi
	 */
	private $_alexaSettingsApi;

	public function __construct(
		\Psr\Log\LoggerInterface $logger,
		AlexaSettingsApi $alexaSettingsApi
	)
	{
		$this->_alexaSettingsApi = $alexaSettingsApi;
		parent::__construct($logger, self::NAMESPACE, __DIR__);
	}

	protected function _initDefintions()
	{
		return [
			new \Convo\Core\Factory\ComponentDefinition(
				$this->getNamespace(),
				'\Convo\Pckg\Appointments\CheckAppointmentTimeElement',
				'Check Appointment Time Element',
				'Checks if the time for an appointment is available.',
				array(
					'context_id' => array(
						'editor_type' => 'context_id',
						'editor_properties' => array(),
						'defaultValue' => 'your_appointment',
						'name' => 'Context ID',
						'description' => 'Unique ID by which this context is referenced',
						'valueType' => 'string'
					),
					'appointment_date' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Appointment Date',
						'description' => 'Date of the appointment.',
						'valueType' => 'string'
					),
					'appointment_time' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Appointment Time',
						'description' => 'Time of the appointment.',
						'valueType' => 'string'
					),
					'result_var' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => 'status',
						'name' => 'Result Variable Name',
						'description' => 'Status variable of the result from appointment checking.',
						'valueType' => 'string'
					),
					'available_flow' => [
						'editor_type' => 'service_components',
						'editor_properties' => [
							'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
							'multiple' => true
						],
						'defaultValue' => [],
						'defaultOpen' => false,
						'name' => 'Available',
						'description' => 'Flow to be executed if the requested appointment date is available.',
						'valueType' => 'class'
					],
					'no_suggestions_flow' => [
						'editor_type' => 'service_components',
						'editor_properties' => [
							'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
							'multiple' => true
						],
						'defaultValue' => [],
						'defaultOpen' => false,
						'name' => 'No Suggestions',
						'description' => 'Flow to be executed if the requested appointment date is available.',
						'valueType' => 'class'
					],
					'suggestions_flow' => [
						'editor_type' => 'service_components',
						'editor_properties' => [
							'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
							'multiple' => true
						],
						'defaultValue' => [],
						'defaultOpen' => false,
						'name' => 'Suggestions',
						'description' => 'Flow to be executed if the requested appointment date is available.',
						'valueType' => 'class'
					],
					'single_suggestion_flow' => [
						'editor_type' => 'service_components',
						'editor_properties' => [
							'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
							'multiple' => true
						],
						'defaultValue' => [],
						'defaultOpen' => false,
						'name' => 'Single Suggestion',
						'description' => 'Flow to be executed if the requested appointment date is available.',
						'valueType' => 'class'
					],
					'_factory' => new class ($this->_alexaSettingsApi) implements IComponentFactory
					{
						private $_alexaSettingsApi;

						public function __construct($alexaCustomerProfileApi)
						{
							$this->_alexaSettingsApi = $alexaCustomerProfileApi;
						}

						public function createComponent($properties, $service)
						{
							return new \Convo\Pckg\Appointments\CheckAppointmentTimeElement($properties, $this->_alexaSettingsApi);
						}
					},
					'_workflow' => 'read',
					'_preview_angular' => array(
						'type' => 'html',
						'template' => '<div class="code">' .
							'Check time slot <b>{{ component.properties.appointment_date }} {{ component.properties.appointment_time }}</b> for appointment type <b>{{ component.properties.context_id }}</b>' .
							'</div>'
					)
				)
			),
			new \Convo\Core\Factory\ComponentDefinition(
				$this->getNamespace(),
				'\Convo\Pckg\Appointments\LoadAppointmentElement',
				'Load Appointment Element',
				'Loads the details of an appointment for the user.',
				array(
					'context_id' => array(
						'editor_type' => 'context_id',
						'editor_properties' => array(),
						'defaultValue' => 'your_appointment',
						'name' => 'Context ID',
						'description' => 'Unique ID by which this context is referenced',
						'valueType' => 'string'
					),
					'appointment_id' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Appointment ID',
						'description' => 'Appointment ID to load the appointment details from',
						'valueType' => 'string'
					),
					'email' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Email',
						'description' => 'Email of the user to load the appointment details from.',
						'valueType' => 'string'
					),
					'return_var' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => 'status',
						'name' => 'Return Variable Name',
						'description' => 'Status variable of the loaded appointment.',
						'valueType' => 'string'
					),
					'ok' => [
						'editor_type' => 'service_components',
						'editor_properties' => [
							'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
							'multiple' => true
						],
						'defaultValue' => [],
						'defaultOpen' => false,
						'name' => 'OK',
						'description' => 'Flow to be executed if the appointment under ID was found.',
						'valueType' => 'class'
					],
					'not_found' => [
						'editor_type' => 'service_components',
						'editor_properties' => [
							'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
							'multiple' => true
						],
						'defaultValue' => [],
						'defaultOpen' => false,
						'name' => 'Not Found',
						'description' => 'Flow to be executed if the appointment under ID was not found.',
						'valueType' => 'class'
					],
					'_workflow' => 'read',
					'_preview_angular' => array(
						'type' => 'html',
						'template' => '<div class="code">' .
							'Load <b>{{ component.properties.context_id }}</b> <b>{{ component.properties.appointment_id }}</b> for <b>{{ component.properties.email }}</b>' .
							'</div>'
					)
				)
			),
			new \Convo\Core\Factory\ComponentDefinition(
				$this->getNamespace(),
				'\Convo\Pckg\Appointments\LoadAppointmentsElement',
				'Load Appointments Element',
				'Loads appointments for the user.',
				array(
					'context_id' => array(
						'editor_type' => 'context_id',
						'editor_properties' => array(),
						'defaultValue' => 'your_appointment',
						'name' => 'Context ID',
						'description' => 'Unique ID by which this context is referenced',
						'valueType' => 'string'
					),
					'email' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Email',
						'description' => 'Email of the user to load the appointment for.',
						'valueType' => 'string'
					),
					'mode' => array(
						'editor_type' => 'select',
						'editor_properties' => [
							'options' => [
								IAppointmentsContext::LOAD_MODE_ALL => 'All',
								IAppointmentsContext::LOAD_MODE_CURRENT => 'Current',
								IAppointmentsContext::LOAD_MODE_PAST => 'Past'
							]
						],
						'defaultValue' => IAppointmentsContext::LOAD_MODE_CURRENT,
						'name' => 'Mode',
						'description' => 'Loads appointments which are currently active, already finished or both.',
						'valueType' => 'string'
					),
					'number_of_appointments_to_load' => array(
						'editor_type' => 'number',
						'editor_properties' => array(),
						'defaultValue' => 10,
						'name' => 'Number of Appointments to Load',
						'description' => 'Appointment ID to load the appointment details from.',
						'valueType' => 'string'
					),
					'return_var' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => 'status',
						'name' => 'Return Variable Name',
						'description' => 'Status variable of the loaded appointment.',
						'valueType' => 'string'
					),
					'empty' => [
						'editor_type' => 'service_components',
						'editor_properties' => [
							'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
							'multiple' => true
						],
						'defaultValue' => [],
						'defaultOpen' => false,
						'name' => 'Empty',
						'description' => 'Flow to be executed if no appointment could be found.',
						'valueType' => 'class'
					],
					'multiple' => [
						'editor_type' => 'service_components',
						'editor_properties' => [
							'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
							'multiple' => true
						],
						'defaultValue' => [],
						'defaultOpen' => false,
						'name' => 'Multiple',
						'description' => 'Flow to be executed if more than one appointments could be found.',
						'valueType' => 'class'
					],
					'single' => [
						'editor_type' => 'service_components',
						'editor_properties' => [
							'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
							'multiple' => true
						],
						'defaultValue' => [],
						'defaultOpen' => false,
						'name' => 'Single',
						'description' => 'Flow to be executed if one appointment could be found.',
						'valueType' => 'class'
					],
					'_workflow' => 'read',
					'_preview_angular' => array(
						'type' => 'html',
						'template' => '<div class="code">' .
							'Load <b>{{ component.properties.mode }}</b> <b>{{ component.properties.context_id }}</b> for <b>{{ component.properties.email }}</b>' .
							'</div>'
					)
				)
			),
			new \Convo\Core\Factory\ComponentDefinition(
				$this->getNamespace(),
				'\Convo\Pckg\Appointments\CreateAppointmentElement',
				'Create Appointment Element',
				'Creates an appointment for the user.',
				array(
					'context_id' => array(
						'editor_type' => 'context_id',
						'editor_properties' => array(),
						'defaultValue' => 'your_appointment',
						'name' => 'Context ID',
						'description' => 'Unique ID by which this context is referenced',
						'valueType' => 'string'
					),
					'email' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Email',
						'description' => 'Email of the user to create the appointment for.',
						'valueType' => 'string'
					),
					'appointment_date' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Appointment Date',
						'description' => 'Date of the appointment.',
						'valueType' => 'string'
					),
					'appointment_time' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Appointment Time',
						'description' => 'Time of the appointment.',
						'valueType' => 'string'
					),
					'payload' => array(
						'editor_type' => 'params',
						'editor_properties' => array(
							'multiple' => true
						),
						'defaultValue' => array(),
						'name' => 'Payload',
						'description' => 'An array of elements that fills the Additional appointment data such as customer info, appointment notes and etc.',
						'valueType' => 'array'
					),
					'result_var' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => 'status',
						'name' => 'Result Variable Name',
						'description' => 'Status variable of the result of appointment creation.',
						'valueType' => 'string'
					),
					'ok' => [
						'editor_type' => 'service_components',
						'editor_properties' => [
							'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
							'multiple' => true
						],
						'defaultValue' => [],
						'defaultOpen' => false,
						'name' => 'OK',
						'description' => 'Flow to be executed if the appointment could be created.',
						'valueType' => 'class'
					],
					'not_available' => [
						'editor_type' => 'service_components',
						'editor_properties' => [
							'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
							'multiple' => true
						],
						'defaultValue' => [],
						'defaultOpen' => false,
						'name' => 'Not Available',
						'description' => 'Flow to be executed if the appointment could not be created.',
						'valueType' => 'class'
					],
					'_workflow' => 'read',
					'_preview_angular' => array(
						'type' => 'html',
						'template' => '<div class="code">' .
							'Create <b>{{ component.properties.context_id }}</b> for <b>{{ component.properties.email }}</b>' .
							'</div>'
					)
				)
			),
			new \Convo\Core\Factory\ComponentDefinition(
				$this->getNamespace(),
				'\Convo\Pckg\Appointments\UpdateAppointmentElement',
				'Update Appointment Element',
				'Updates an appointment for the user.',
				array(
					'context_id' => array(
						'editor_type' => 'context_id',
						'editor_properties' => array(),
						'defaultValue' => 'your_appointment',
						'name' => 'Context ID',
						'description' => 'Unique ID by which this context is referenced',
						'valueType' => 'string'
					),
					'appointment_id' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Appointment ID',
						'description' => 'ID of the appointment to perform the update.',
						'valueType' => 'string'
					),
					'email' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Email',
						'description' => 'Email of the user which has the appointment.',
						'valueType' => 'string'
					),
					'appointment_date' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Appointment Date',
						'description' => 'Date of the appointment.',
						'valueType' => 'string'
					),
					'appointment_time' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Appointment Time',
						'description' => 'Time of the appointment.',
						'valueType' => 'string'
					),
					'payload' => array(
						'editor_type' => 'params',
						'editor_properties' => array(
							'multiple' => true
						),
						'defaultValue' => array(),
						'name' => 'Payload',
						'description' => 'An array of elements that fills the Additional appointment data such as customer info, appointment notes and etc.',
						'valueType' => 'array'
					),
					'result_var' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => 'status',
						'name' => 'Result Variable Name',
						'description' => 'Status variable of the result of appointment update.',
						'valueType' => 'string'
					),
					'ok' => [
						'editor_type' => 'service_components',
						'editor_properties' => [
							'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
							'multiple' => true
						],
						'defaultValue' => [],
						'defaultOpen' => false,
						'name' => 'OK',
						'description' => 'Flow to be executed if the appointment could be updated.',
						'valueType' => 'class'
					],
					'not_available' => [
						'editor_type' => 'service_components',
						'editor_properties' => [
							'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
							'multiple' => true
						],
						'defaultValue' => [],
						'defaultOpen' => false,
						'name' => 'Not available',
						'description' => 'Flow to be executed if the appointment date is not available for the specified time slot.',
						'valueType' => 'class'
					],
					'not_found' => [
						'editor_type' => 'service_components',
						'editor_properties' => [
							'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
							'multiple' => true
						],
						'defaultValue' => [],
						'defaultOpen' => false,
						'name' => 'Not Found',
						'description' => 'Flow to be executed if the appointment under the ID could not be found.',
						'valueType' => 'class'
					],
					'_workflow' => 'read',
					'_preview_angular' => array(
						'type' => 'html',
						'template' => '<div class="code">' .
							'Update <b>{{ component.properties.context_id }}</b> <b>{{ component.properties.appointment_id }}</b> for <b>{{ component.properties.email }}</b> ' .
							'</div>'
					)
				)
			),
			new \Convo\Core\Factory\ComponentDefinition(
				$this->getNamespace(),
				'\Convo\Pckg\Appointments\CancelAppointmentElement',
				'Cancel Appointment Element',
				'Cancels an appointment for the user.',
				array(
					'context_id' => array(
						'editor_type' => 'context_id',
						'editor_properties' => array(),
						'defaultValue' => 'your_appointment',
						'name' => 'Context ID',
						'description' => 'Unique ID by which this context is referenced',
						'valueType' => 'string'
					),
					'appointment_id' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Appointment ID',
						'description' => 'ID of the appointment to cancel.',
						'valueType' => 'string'
					),
					'email' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Email',
						'description' => 'Email of the user to cancel the appointment.',
						'valueType' => 'string'
					),
					'ok' => [
						'editor_type' => 'service_components',
						'editor_properties' => [
							'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
							'multiple' => true
						],
						'defaultValue' => [],
						'defaultOpen' => false,
						'name' => 'OK',
						'description' => 'Flow to be executed if the appointment was canceled successfully.',
						'valueType' => 'class'
					],
					'not_found' => [
						'editor_type' => 'service_components',
						'editor_properties' => [
							'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
							'multiple' => true
						],
						'defaultValue' => [],
						'defaultOpen' => false,
						'name' => 'Not Found',
						'description' => 'Flow to be executed if the appointment under the ID could not be found.',
						'valueType' => 'class'
					],
					'_workflow' => 'read',
					'_preview_angular' => array(
						'type' => 'html',
						'template' => '<div class="code">' .
							'Cancel <b>{{ component.properties.context_id }}</b> <b>{{ component.properties.appointment_id }}</b> for <b>{{ component.properties.email }}</b>' .
							'</div>'
					)
				)
			)
		];
	}
}