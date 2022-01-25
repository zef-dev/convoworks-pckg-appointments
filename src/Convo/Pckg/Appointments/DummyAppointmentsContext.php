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
	const DATE_FORMAT      =   'Y-m-d';
	const TIME_FORMAT      =   'H:i:s';

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
	    $this->_logger->debug( 'Checking time ['.$time->format( self::DATE_TIME_FORMAT).']');

	    $this->_checkSlotAllowed( $time);

        if ($time->getTimestamp() <= time()) {
            $this->_logger->notice( 'Time [' . $time->format('Y-m-d H:s:i') . '] in the past is not allowed.');
            return false;
        }

	    $appointments  =   $this->_getAppointments();

	    foreach ( $appointments as $appointment)
	    {
	        $start     =   \DateTime::createFromFormat( 'U', strval( $appointment['timestamp']), $time->getTimezone());
	        $end       =   \DateTime::createFromFormat( 'U', strval( $appointment['timestamp'] + self::DURATION_MINUTES * 60), $time->getTimezone());

	        $this->_logger->debug( 'Checking against ['.$start->format( self::DATE_TIME_FORMAT).']['.$end->format( self::TIME_FORMAT).']');
	        if ( $time >= $start && $time < $end) {
	            $this->_logger->info( 'Taken slot by ['.$appointment['appointment_id'].']');
	            return false;
	        }
	    }

	    return true;
	}

	/**
	 * @param \DateTimeInterface $time
	 * @throws OutOfBusinessHoursException
	 */
	private function _checkSlotAllowed( $time)
	{
	    $requested =   \DateTime::createFromFormat( 'H:i', $time->format( 'H:i'));
	    $start     =   \DateTime::createFromFormat( 'H:i', self::MIN_HOUR);
	    $end       =   \DateTime::createFromFormat( 'H:i', self::MAX_HOUR);

	    if ( $requested < $start || $requested > $end) {
	        throw new OutOfBusinessHoursException( 'Not in allowed period.');
	    }

	    if ( $time->format( 'N') >= 6) {
	        throw new OutOfBusinessHoursException( 'Weekedns not allowed.');
	    }
	}

    /**
     * @param \DateTimeInterface $time
     * @throws SlotNotAvailableException
     * @throws OutOfBusinessHoursException
     */
    private function _checkSlotAvailability($time) {
        $isSlotAvailable = $this->isSlotAvailable($time);
        if (!$isSlotAvailable) {
            throw new SlotNotAvailableException( 'The time slot [' . $time->format('Y-m-d H:s:i') . '] is already taken.');
        }
        if ($time->getTimestamp() <= time()) {
            throw new SlotNotAvailableException( 'Time [' . $time->format('Y-m-d H:s:i') . '] in the past is not allowed.');
        }
    }

	public function createAppointment( $email, $time, $payload = [])
	{
        $this->_checkSlotAvailability($time);
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
        $this->_checkSlotAvailability($time);
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

        $this->_logger->debug( 'Searching for appointment ['.$appointmentId.']');
        foreach ( $appointments as $appointment)
        {
            $this->_logger->debug( 'Checking appointment ['.print_r( $appointment, true).']');
            if ( $appointment['appointment_id'] == $appointmentId) {
                $this->_logger->info( 'Found appointment ['.$appointmentId.']');
                return $appointment;
            }
        }

        throw new DataItemNotFoundException( 'Could not find appointment ['.$appointmentId.']');
    }

    public function getFreeSlotsIterator( $startTime)
    {
        $end        =   clone $startTime;
        $end        =   $end->modify( '+'.self::MAX_DAYS.' days' );

        $this->_logger->info( 'Checking free slots from ['.$startTime->format( self::DATE_TIME_FORMAT).'] to ['.$end->format( self::DATE_TIME_FORMAT).']');

        $interval   =   new \DateInterval( 'P1D');
        $daterange  =   new \DatePeriod( $startTime, $interval, $end);

        foreach ( $daterange as $day)
        {
            if ( $day->format( 'N') >= 6) {
                $this->_logger->info( 'Weekedns not allowed ['.$day->format( self::DATE_FORMAT).'].');
                continue;
            }

            $this->_logger->debug( 'Checking day ['.$day->format( self::DATE_TIME_FORMAT).']');
            /* @var \DateTime $day */
            $first      =   \DateTime::createFromFormat( 'H:i', self::MIN_HOUR);
            $last       =   \DateTime::createFromFormat( 'H:i', self::MAX_HOUR);

            $this->_logger->debug( 'Checking day ['.$day->format( self::DATE_TIME_FORMAT).'] from ['.$day->format( self::TIME_FORMAT).'] to ['.$day->format( self::TIME_FORMAT).']');

//             PT30M
            $slots      =   new \DateInterval( 'PT'.self::DURATION_MINUTES.'M');
            $timerange  =   new \DatePeriod( $first, $slots, $last);

            $now = time();
            foreach ( $timerange as $slot)
            {
                /* @var \DateTime $slot */
                // 'Y-m-d H:i:s';
                $current    =   \DateTime::createFromFormat(
                    self::DATE_TIME_FORMAT,
                    $day->format( self::DATE_FORMAT).' '.$slot->format( self::TIME_FORMAT),
                    $startTime->getTimezone());

                $this->_logger->debug( 'Got current ['.$current->format( self::DATE_TIME_FORMAT).'] from slot ['.$slot->format( self::TIME_FORMAT).']');

                if ($current->getTimestamp() <= $now) {
                    $this->_logger->info('Slot in past is not allowed.');
                    continue;
                }

                try {
                    if ($this->isSlotAvailable($current)) {
                        $this->_logger->debug('Returning match [' . $current->format(self::DATE_TIME_FORMAT) . ']');
                        yield ['timestamp' => $current->getTimestamp()];
                    }
                } catch (OutOfBusinessHoursException $e) {
                    $this->_logger->notice($e->getMessage());
                }
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
        $appointments       =   $this->_getAppointments();

        if ( $mode == self::LOAD_MODE_ALL) {
            usort($appointments, function ($first, $second) {
                return $first['timestamp'] > $second['timestamp'];
            });
            return $appointments;
        }

        $filtered           =   [];
        $now                =   time();
        if ( $mode == self::LOAD_MODE_CURRENT) {
            usort($appointments, function ($first, $second) {
                return $first['timestamp'] > $second['timestamp'];
            });
            foreach ( $appointments as $appointment) {
                if ( $appointment['timestamp'] > $now) {
                    $filtered[] = $appointment;
                }
            }
        } else if ( $mode == self::LOAD_MODE_PAST) {
            usort($appointments, function ($first, $second) {
                return $first['timestamp'] < $second['timestamp'];
            });
            foreach ( $appointments as $appointment) {
                if ( $appointment['timestamp'] < $now) {
                    $filtered[] = $appointment;
                }
            }
        } else {
            throw new \Exception( 'Unexpected mode ['.$mode.']');
        }

        return $filtered;
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
