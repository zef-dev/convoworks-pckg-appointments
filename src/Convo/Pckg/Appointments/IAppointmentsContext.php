<?php
namespace Convo\Pckg\Appointments;


use Convo\Core\DataItemNotFoundException;

/**
 * @author Tole
 * This interface describes interaction between Convoworks workflow components and underlying appointments system.
 */
interface IAppointmentsContext
{
    const LOAD_MODE_CURRENT =   'current';
    const LOAD_MODE_PAST    =   'past';
    const LOAD_MODE_ALL     =   'all';
    
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
	 * Returns single appointment, otherwise throws not found exception.
	 * Returned appointment structure:
     * ```json
     * {
     *      "appointment_id" : "123",
     *      "timestamp" : 123345678,
     *      "payload" : {
     *          "some_other_fields" : "That is used by implementing appointment context & WP plugin"
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
	 * @return array of appointments. For the details of appointment structure check {@see IAppointmentsContext::getAppointment()}
	 * @throws DataItemNotFoundException
	 */
	public function loadAppointments( $email, $mode=self::LOAD_MODE_CURRENT);
	
	/**
	 * Iterator returns timestamp of the free slots. 
	 * @param \DateTime $startTime
	 * @return \Iterator
	 */
	public function getFreeSlotsIterator( $startTime=null);
	
}