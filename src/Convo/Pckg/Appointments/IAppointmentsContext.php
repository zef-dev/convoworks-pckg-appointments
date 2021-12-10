<?php
namespace Convo\Pckg\Appointments;


use Convo\Core\DataItemNotFoundException;

interface IAppointmentsContext
{
    const LOAD_MODE_CURRENT =   'current';
    const LOAD_MODE_PAST    =   'past';
    const LOAD_MODE_ALL     =   'all';
    
	/**
	 * Checks if given slot is available.
	 * @param int $timestamp
	 * @return bool
	 */
	public function isSlotAvailable( $timestamp);

	/**
	 * Creates new appointment and returns it's id.
	 * @param string $email
	 * @param int $timestamp
	 * @param array $payload
	 * @return string created appointment id
	 * @throws SlotNotAvailableException
	 */
	public function createAppointment( $email, $timestamp, $payload=[]);

	/**
	 * @param string $email
	 * @param string $appointmentId
	 * @param int $timestamp
	 * @param array $payload
	 * @throws SlotNotAvailableException
	 * @throws DataItemNotFoundException
	 */
	public function updateAppointment( $email, $appointmentId, $timestamp, $payload=[]);
	
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
	 * @return array
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
	 * @param int $startTimestamp
	 * @return \Iterator
	 */
	public function getFreeSlotsIterator( $startTimestamp=null);
	
}