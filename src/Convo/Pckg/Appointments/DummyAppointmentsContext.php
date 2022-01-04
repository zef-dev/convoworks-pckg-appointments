<?php

namespace Convo\Pckg\Appointments;

use Convo\Core\Workflow\AbstractBasicComponent;
use Convo\Core\Workflow\IServiceContext;

class DummyAppointmentsContext extends AbstractBasicComponent implements IServiceContext, IAppointmentsContext
{
	private $_id;

	const DATE_TIME_FORMAT = 'Y-m-d H:i:s';

	public function __construct( $properties)
	{
		parent::__construct( $properties);

		$this->_id = $properties['id'];
	}

	/**
	 * @return mixed
	 */
	public function init()
	{
		$this->_logger->info( 'Initializing ['.$this.']');
	}

	/**
	 * @return mixed
	 */
	public function getComponent()
	{
		return $this;
	}

	public function getId()
	{
		return $this->_id;
	}
	
	
	public function isSlotAvailable( $time)
	{
	    return true;
	}
	
	public function createAppointment( $email, $time, $payload = [])
	{
	    return \Convo\Core\Util\StrUtil::uuidV4();
	}
	
	public function updateAppointment( $email, $appointmentId, $time, $payload = [])
	{}
	
    public function cancelAppointment( $email, $appointmentId)
    {}

    public function getAppointment( $email, $appointmentId)
    {
        return [
            'appointment_id' => $appointmentId,
            'timestamp' => time() + 60 * 60 * 24,
            'timezone' => date_default_timezone_get(),
            'payload' => [
                'email' => $email
            ],
        ];
    }

    public function getFreeSlotsIterator( $startTime = null)
    {
        return new \ArrayIterator( []);    
    }

    public function loadAppointments( $email, $mode=self::LOAD_MODE_CURRENT, $count=self::DEFAULT_APPOINTMENTS_COUNT) 
    {
        return [];
    }

    public function getDefaultTimezone()
    {
        return new \DateTimeZone( date_default_timezone_get());
    }

    
    // UTIL
    public function __toString()
    {
        return parent::__toString().'['.$this->_id.']';
    }
}