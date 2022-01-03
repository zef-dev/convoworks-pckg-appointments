<?php declare(strict_types=1);

namespace Convo\Pckg\Appointments;


use Convo\Core\DataItemNotFoundException;

/**
 * @author Tole
 * This interface describes interaction between Convoworks workflow components and underlying appointments system.
 * Some methods should throw an exception if required condition is meet.
 *  DataItemNotFoundException - when the appointment with requested appointment_id is not found.
 *  BadRequestException - When required data in payload is not populated.
 *  SlotNotAvailableException - When the requested time is not available.
 */
interface IAppointmentsContext
{
    const LOAD_MODE_CURRENT             =   'current';
    const LOAD_MODE_PAST                =   'past';
    const LOAD_MODE_ALL                 =   'all';
    
    const DEFAULT_APPOINTMENTS_COUNT    =   10;
    
	/**
	 * Checks if given slot is available.
	 * @param \DateTime $time
	 * @return bool
	 */
    public function isSlotAvailable( $time);

	/**
	 * Creates new appointment and returns it's id.
	 * @param string $email
	 * @param \DateTime $time
	 * @param array $payload
	 * @return string created appointment id
	 * @throws BadRequestException
	 * @throws SlotNotAvailableException
	 */
    public function createAppointment( $email, $time, $payload=[]);

	/**
	 * Updates existing appointment.
	 * @param string $email
	 * @param string $appointmentId
	 * @param \DateTime $time
	 * @param array $payload
	 * @throws DataItemNotFoundException
	 * @throws BadRequestException
	 * @throws SlotNotAvailableException
	 */
	public function updateAppointment( $email, $appointmentId, $time, $payload=[]);
	
	/**
	 * Cancels existing appointment
	 * @param string $email
	 * @param string $appointmentId
	 * @throws DataItemNotFoundException
	 */
	public function cancelAppointment( $email, $appointmentId);
	
	/**
	 * Returns single appointment, otherwise throws a not found exception.
	 * Returned appointment structure:
     * ```json
     * {
     *      "appointment_id" : "123",
     *      "timestamp" : 123345678,
     *      "timezone" : "America/New_York",
     *      "payload" : {
     *          "some_other_fields" : "That is used by implementing appointment context & WP plugin",
     *          "more_fields" : "Some other data"
     *      }
     * }
     * ```
	 * @param string $email
	 * @param string $appointmentId
	 * @return array For the details of appointment structure check {@see IAppointmentsContext::getAppointment()}
	 * @throws DataItemNotFoundException
	 */
	public function getAppointment( $email, $appointmentId);
	
	/**
	 * Loads existing appointments.
	 * 
	 * @param string $email
	 * @param string $mode
	 * @param int $count
	 * @return array of appointments. For the details of appointment structure check {@see IAppointmentsContext::getAppointment()}
	 */
	public function loadAppointments( $email, $mode=self::LOAD_MODE_CURRENT, $count=self::DEFAULT_APPOINTMENTS_COUNT);
	
	/**
	 * TBD.
	 * Iterator returns timestamp of the free slots.
     * ```json
     * [{
     *      "timestamp" : 123345678,
     *      "timezone" : "America/New_York"
     * }, {
     *      "timestamp" : 123347678,
     *      "timezone" : "America/New_York"
     * }]
     * ```
	 * @param \DateTime $startTime
	 * @return \Iterator
	 */
	public function getFreeSlotsIterator( $startTime=null);
	
	
	
	/**
	 * Returns default timezone used by the implementing system. It can be appointment type timezone, it might be server default, depends on the underlying logic.
	 * @return \DateTimeZone
	 */
	public function getDefaultTimezone();
	
}