<?php

namespace Convo\Pckg\Appointments;

use Convo\Core\Workflow\AbstractBasicComponent;
use Convo\Core\Workflow\IServiceContext;
use Convo\Core\Params\IServiceParamsScope;
use Convo\Core\DataItemNotFoundException;

class DummyAppointmentsContext extends AbstractBasicComponent implements IServiceContext, IAppointmentsContext
{
	private $_id;

	const DATE_TIME_FORMAT =   'Y-m-d H:i:s';

	const MIN_HOUR         =   '09:00';
	const MAX_HOUR         =   '16:30';
	const DURATION_MINUTES =   30;
	const MAX_DAYS         =   15;
	
	private $_cachedAppointments;
	
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
        $appointments      =   $this->_getAppointments();
        
        foreach ( $appointments as &$appointment)
        {
            if ( $appointment['appointment_id'] == $appointmentId) {
                return $appointment;
            }
        }
        
        throw new DataItemNotFoundException( 'COuld not find appointment ['.$appointmentId.']');
    }

    public function getFreeSlotsIterator( $startTime)
    {
        $end        =   clone $startTime;
        $end        =   $end->modify( '+'.self::MAX_DAYS.' days' );
        $interval   =   new \DateInterval( 'P1D');
        $daterange  =   new \DatePeriod( $startTime, $interval, $end);
        
        foreach ( $daterange as $day) 
        {
            /* @var \DateTime $day */
            $first      =   \DateTime::createFromFormat( 'H:i', self::MIN_HOUR);
            $last       =   \DateTime::createFromFormat( 'H:i', self::MAX_HOUR);
            
            $slots      =   new \DateInterval( 'P'.self::DURATION_MINUTES.'I');
            $timerange  =   new \DatePeriod( $first, $slots, $last);
            
            foreach ( $timerange as $slot) 
            {
                /* @var \DateTime $slot */
                // 'Y-m-d H:i:s';
                $current    =   \DateTime::createFromFormat( 
                    self::DATE_TIME_FORMAT, 
                    $day->format( 'Y-m-d').' '.$slot->format( 'H:i:s'),
                    $startTime->getTimezone());
                
                if ( $this->_isSlotAllowed( $current)) {
                    yield ['timestamp' => $current->getTimestamp()];                }
            }
        }
        
//         return new \ArrayIterator( [[
//             'timestamp' => time() + 60 * 60 * 24,
//         ], [
//             'timestamp' => time() + 60 * 60 * 24 + 60 * 60 * 2,
//         ]]);    
    }

    public function loadAppointments( $email, $mode=self::LOAD_MODE_CURRENT, $count=self::DEFAULT_APPOINTMENTS_COUNT) 
    {
        $appointments      =   $this->_getAppointments();
        return $appointments;
    }

    public function getDefaultTimezone()
    {
        return new \DateTimeZone( date_default_timezone_get());
    }
    
    // DATA
    private function _getAppointments()
    {
        if ( !isset( $this->_cachedAppointments)) {
            $params                      =   $this->getService()->getComponentParams( IServiceParamsScope::SCOPE_TYPE_USER, $this);
            $this->_cachedAppointments   =   $params->getServiceParam( 'appointments');
            if ( is_null( $this->_cachedAppointments)) {
                $this->_cachedAppointments   =    [];
            }
        }
        return $this->_cachedAppointments;
    }
    
    private function _saveAppointments( $appointments)
    {
        $params =   $this->getService()->getComponentParams( IServiceParamsScope::SCOPE_TYPE_USER, $this);
        $params->setServiceParam( 'appointments', $appointments);
        $this->_cachedAppointments  =   $appointments;
    }
    
    
    // UTIL
    public function __toString()
    {
        return parent::__toString().'['.$this->_id.']';
    }
}