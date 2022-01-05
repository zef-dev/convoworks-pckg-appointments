<?php

namespace Convo\Pckg\Appointments;

use Convo\Core\Workflow\AbstractBasicComponent;
use Convo\Core\Workflow\IServiceContext;
use Convo\Core\Params\IServiceParamsScope;

class DummyAppointmentsContext extends AbstractBasicComponent implements IServiceContext, IAppointmentsContext
{
	private $_id;

	const DATE_TIME_FORMAT = 'Y-m-d H:i:s';

	const MIN_HOUR =   '09:00';
	const MAX_HOUR =   '16:30';
	const DURATION =   '00:30';
	
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
	
	// APPOINTMENTS INTERFACE
	public function isSlotAvailable( $time)
	{
	    if ( !$this->_isSlotAllowed( $time)) {
	        return false;
	    }
	    return true;
	}
	
	/**
	 * @param \DateTimeInterface $time
	 * @return bool
	 */
	private function _isSlotAllowed( $time) 
	{
	    $requested =   \DateTime::createFromFormat( 'H:i', $time->format( 'H:i'));
	    $start     =   \DateTime::createFromFormat( 'H:i', SELF::MIN_HOUR);
	    $end       =   \DateTime::createFromFormat( 'H:i', SELF::MAX_HOUR);
	    
	    if ( $requested < $start || $requested > $end) {
	        $this->_logger->info( 'Not in allowed period');
	        return false;
	    }
	    
	    if ( $time->format( 'N') >= 6) {
	        $this->_logger->info( 'Weekedns not allowed.');
	        return false;
	    }
	    
	    return true;
	}
	
	public function createAppointment( $email, $time, $payload = [])
	{
	    $appointment_id    =   \Convo\Core\Util\StrUtil::uuidV4();
	    $appointment       =   [
	        'appointment_id' => $appointment_id,
	        'email' => $email,
	        'timestamp' => $time->getTimestamp(),
	        'timezone' => $time->getTimezone()->getName(),
	        'payload' => $payload,
	    ];
	    
	    $appointments      =   $this->_getAppointments();
	    $appointments[]    =   $appointment;
	    $this->_saveAppointments( $appointments);
	    
	    return $appointment_id;
	}
	
	public function updateAppointment( $email, $appointmentId, $time, $payload = [])
	{
	    $appointments      =   $this->_getAppointments();

	    foreach ( $appointments as &$appointment) 
	    {
	        if ( $appointment['appointment_id'] != $appointmentId) {
	            continue;
	        }
	        $appointment['timestamp']  =   $time->getTimestamp();
	        $appointment['timezone']   =   $time->getTimezone()->getName();
	        if ( !empty( $payload)) {
	            $appointment['payload']  =   $payload;
	        }
	    }
	    
	    $this->_saveAppointments( $appointments);
	}
	
    public function cancelAppointment( $email, $appointmentId)
    {
        $appointments   =   $this->_getAppointments();
        
        $appointments   =   array_filter( $appointments, function ( $appointment) use ( $appointmentId) {
            return $appointment['appointment_id'] != $appointmentId;
        });        
        
        $this->_saveAppointments( $appointments);
    }

    public function getAppointment( $email, $appointmentId)
    {
        return [
            'appointment_id' => $appointmentId,
            'timestamp' => time() + 60 * 60 * 24,
            'payload' => [
                'email' => $email,
                'name' => 'Tole Car'
            ],
        ];
    }

    public function getFreeSlotsIterator( $startTime)
    {
        return new \ArrayIterator( [[
            'timestamp' => time() + 60 * 60 * 24,
        ], [
            'timestamp' => time() + 60 * 60 * 24 + 60 * 60 * 2,
        ]]);    
    }

    public function loadAppointments( $email, $mode=self::LOAD_MODE_CURRENT, $count=self::DEFAULT_APPOINTMENTS_COUNT) 
    {
        return [[
            'appointment_id' => '1',
            'timestamp' => time() + 60 * 60 * 24,
            'email' => $email,
            'payload' => [
                'email' => $email,
                'name' => 'Tole Car'
            ],
        ]
        ];
    }

    public function getDefaultTimezone()
    {
        return new \DateTimeZone( date_default_timezone_get());
    }
    
    // DATA
    private function _getAppointments()
    {
        $params         =   $this->getService()->getComponentParams( IServiceParamsScope::SCOPE_TYPE_USER, $this);
        $appointments   =   $params->getServiceParam( 'appointments');
        if ( empty( $appointments)) {
            return [];
        }
        return $appointments;
    }
    
    private function _saveAppointments( $appointments)
    {
        $params =   $this->getService()->getComponentParams( IServiceParamsScope::SCOPE_TYPE_USER, $this);
        $params->setServiceParam( 'appointments', $appointments);
    }
    
    
    // UTIL
    public function __toString()
    {
        return parent::__toString().'['.$this->_id.']';
    }
}