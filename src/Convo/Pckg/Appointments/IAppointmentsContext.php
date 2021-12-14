<?php declare(strict_types=1);

namespace Convo\Pckg\Appointments;


use Convo\Core\DataItemNotFoundException;

/**
 * @author Tole
 * This interface describes interaction between Convoworks workflow components and underlying appointments system.
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
	 * @throws SlotNotAvailableException
	 */
    public function createAppointment( $email, $time, $payload=[]);

	/**
	 * @param string $email
	 * @param string $appointmentId
	 * @param \DateTime $time
	 * @param array $payload
	 * @throws SlotNotAvailableException
	 * @throws DataItemNotFoundException
	 */
	public function updateAppointment( $email, $appointmentId, $time, $payload=[]);
	
	/**
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
	 * @throws DataItemNotFoundException
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
	
}