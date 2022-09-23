<?php

namespace Convo\Pckg\Appointments;

use Convo\Core\Workflow\AbstractWorkflowComponent;
use Convo\Pckg\Appointments\Freeslot\IFreeSlotQueueFactory;
use Convo\Pckg\Appointments\Freeslot\DefaultFreeSlotValidator;
use Convo\Pckg\Appointments\Freeslot\FreeSlotQueue;

class DefaultFreeSlotQueue extends AbstractWorkflowComponent implements IFreeSlotQueueFactory
{
    const KEY_FIRST_NEXT            =   'first_next';

    const KEY_SAME_DAY_TIME_BEFORE_REQUEST_TIME = 'same_day_time_before_request_time';
    const KEY_SAME_DAY_TIME_AFTER_REQUEST_TIME = 'same_day_time_after_request_time';
    const KEY_NEXT_DAY_SAME_TIME = 'next_day_and_same_time';

    const KEY_NEXT_DAY_TIME_BEFORE_REQUEST_TIME_IF_REQUEST_DAY_IS_NOT_PRESENT = 'next_day_time_before_request_time_if_request_day_is_not_present';
    const KEY_NEXT_DAY_TIME_AFTER_REQUEST_TIME_IF_REQUEST_DAY_IS_NOT_PRESENT = 'next_day_time_after_request_time_if_request_day_is_not_present';
    const KEY_NEXT_WEEK_SAME_TIME_IF_REQUEST_DAY_IS_NOT_PRESENT = 'next_week_same_time_if_request_day_is_not_present';

    const KEY_DAY_BEFORE_SAME_TIME_IF_REQUEST_DAY_AND_DAY_AFTER_IS_NOT_PRESENT = 'day_before_same_time_if_request_day_and_day_after_is_not_present';
    const KEY_NEXT_DAY_TIME_BEFORE_REQUEST_TIME_IF_REQUEST_DAY_AND_DAY_AFTER_IS_NOT_PRESENT = 'next_day_time_before_request_time_if_request_day_and_day_after_is_not_present';
    const KEY_NEXT_DAY_TIME_AFTER_REQUEST_TIME_IF_REQUEST_DAY_AND_DAY_AFTER_IS_NOT_PRESENT = 'next_day_time_after_request_time_if_request_day_and_day_after_is_not_present';
    const KEY_NEXT_WEEK_SAME_TIME_IF_REQUEST_DAY_AND_DAY_AFTER_IS_NOT_PRESENT = 'next_week_same_time_if_request_day_and_day_after_is_not_present';

    /**
     * @var string
     */
    private $_maxSuggestions;

	public function __construct( $properties)
	{
		parent::__construct( $properties);
		$this->_maxSuggestions    =   $properties['max_suggestions'];
	}

	/**
	 * {@inheritDoc}
	 * @see \Convo\Pckg\Appointments\Freeslot\IFreeSlotQueueFactory::createStack()
	 */
	public function createStack( $targetTime, $systemTimezone) {

	    $queue    =   new FreeSlotQueue( $systemTimezone, $this->evaluateString( $this->_maxSuggestions));

	    $queue->addValidator( $this->_create( self::KEY_FIRST_NEXT, $targetTime));

        $queue->addValidator( $this->_create( self::KEY_SAME_DAY_TIME_BEFORE_REQUEST_TIME, $targetTime));
        $queue->addValidator( $this->_create( self::KEY_SAME_DAY_TIME_AFTER_REQUEST_TIME, $targetTime));
        $queue->addValidator( $this->_create( self::KEY_NEXT_DAY_SAME_TIME, $targetTime));

        $queue->addValidator( $this->_create( self::KEY_NEXT_DAY_TIME_BEFORE_REQUEST_TIME_IF_REQUEST_DAY_IS_NOT_PRESENT, $targetTime));
        $queue->addValidator( $this->_create( self::KEY_NEXT_DAY_TIME_AFTER_REQUEST_TIME_IF_REQUEST_DAY_IS_NOT_PRESENT, $targetTime));
        $queue->addValidator( $this->_create( self::KEY_NEXT_WEEK_SAME_TIME_IF_REQUEST_DAY_IS_NOT_PRESENT, $targetTime));

        $queue->addValidator( $this->_create( self::KEY_DAY_BEFORE_SAME_TIME_IF_REQUEST_DAY_AND_DAY_AFTER_IS_NOT_PRESENT, $targetTime));
        $queue->addValidator( $this->_create( self::KEY_NEXT_DAY_TIME_BEFORE_REQUEST_TIME_IF_REQUEST_DAY_AND_DAY_AFTER_IS_NOT_PRESENT, $targetTime));
        $queue->addValidator( $this->_create( self::KEY_NEXT_DAY_TIME_AFTER_REQUEST_TIME_IF_REQUEST_DAY_AND_DAY_AFTER_IS_NOT_PRESENT, $targetTime));
        $queue->addValidator( $this->_create( self::KEY_NEXT_WEEK_SAME_TIME_IF_REQUEST_DAY_AND_DAY_AFTER_IS_NOT_PRESENT, $targetTime));

	    return $queue;
	}

	private function _create( $key, $targetTime)
	{
	    if ( $key == self::KEY_FIRST_NEXT) {
	        return new DefaultFreeSlotValidator( $targetTime);
	    }

        if ($key === self::KEY_SAME_DAY_TIME_BEFORE_REQUEST_TIME) {
            $newClass = new class( $targetTime) extends DefaultFreeSlotValidator {
                /** @var \Psr\Log\LoggerInterface */
                private $_logger;
                private $_array = [];
                private $_daysPassedSinceTargetDay = 0;
                private $_targetDayAsNumber = 0;

                public function add( $item)
                {
                    $itemDay = \DateTime::createFromFormat( 'U', strval($item['timestamp']))->format('Y-m-d');
                    $requestDay = \DateTime::createFromFormat( 'U', strval($this->_time->getTimestamp()))->format('Y-m-d');

                    $time = \DateTime::createFromFormat( 'U', strval( $this->_time->getTimestamp()))->format('Y-m-d H:i');

                    if ($itemDay === $requestDay) {
                        $this->_targetDayAsNumber = intval(str_replace('-', '', $itemDay)) + 1;
                        $targetTime = \DateTime::createFromFormat( 'U', strval($item['timestamp']))->format('Y-m-d H:i');
                        $this->_array[] = $targetTime;
                    }

                    $itemDayAsNumber = intval(str_replace('-', '', $itemDay));

                    if ($this->_targetDayAsNumber <= $itemDayAsNumber && $this->_targetDayAsNumber > 0) {
                        $this->_daysPassedSinceTargetDay++;
                    }

                    if ($this->_daysPassedSinceTargetDay === 1) {
                        $timesSmallerThenRequestTime = [];
                        foreach ($this->_array as $value) {
                            $valueHoursPartOnly = explode(' ', $value)[1];
                            $timeHoursPartOnly = explode(' ', $time)[1];

                            if ($this->_timeToSeconds($valueHoursPartOnly) < $this->_timeToSeconds($timeHoursPartOnly)) {
                                $timesSmallerThenRequestTime[] = $value;
                            }
                        }

                        $timesSmallerThenRequestTimeValue = $timesSmallerThenRequestTime[count($timesSmallerThenRequestTime) - 1] ?? '';
                        if (!empty($timesSmallerThenRequestTimeValue)) {
                            $this->_logger->info('Adding time slot before requested time slot on the same day.');
                            return parent::add(['timestamp' => strtotime($timesSmallerThenRequestTimeValue)]);
                        }
                    }

                    return false;
                }

                private function _timeToSeconds(string $time): int
                {
                    $arr = explode(':', $time);
                    if (count($arr) === 3) {
                        return $arr[0] * 3600 + $arr[1] * 60 + $arr[2];
                    }
                    return $arr[0] * 60 + $arr[1];
                }

                public function setLogger($logger) {
                    $this->_logger = $logger;
                }
            };
            $newClass->setLogger($this->_logger);
            return $newClass;
        }

        if ($key === self::KEY_SAME_DAY_TIME_AFTER_REQUEST_TIME) {
            $newClass = new class( $targetTime) extends DefaultFreeSlotValidator {
                /**
                 * @var \Psr\Log\LoggerInterface
                 */
                private $_logger;

                public function add( $item)
                {
                    $requestDay = \DateTime::createFromFormat( 'U', strval($this->_time->getTimestamp()))->format('Y-m-d');
                    $time = \DateTime::createFromFormat( 'U', strval( $this->_time->getTimestamp()))->format('H:i');

                    $itemDay = \DateTime::createFromFormat( 'U', strval($item['timestamp']))->format('Y-m-d');

                    if ($itemDay === $requestDay) {
                        $targetTime = \DateTime::createFromFormat( 'U', strval($item['timestamp']))->format('H:i');
                        if ($this->_timeToSeconds($targetTime) > $this->_timeToSeconds($time)) {
                            $this->_logger->info('Adding time slot after requested time slot on the same day.');
                            return parent::add( $item);
                        }
                    }
                    return false;
                }

                private function _timeToSeconds(string $time): int
                {
                    $arr = explode(':', $time);
                    if (count($arr) === 3) {
                        return $arr[0] * 3600 + $arr[1] * 60 + $arr[2];
                    }
                    return $arr[0] * 60 + $arr[1];
                }

                public function setLogger($logger) {
                    $this->_logger = $logger;
                }
            };
            $newClass->setLogger($this->_logger);
            return $newClass;
        }

        if ($key === self::KEY_NEXT_DAY_SAME_TIME) {
            $newClass = new class( $targetTime) extends DefaultFreeSlotValidator {
                /**
                 * @var \Psr\Log\LoggerInterface
                 */
                private $_logger;

                public function add( $item)
                {
                    $nextDayPeriod = new \DateInterval('P1D');
                    $nextDay = \DateTime::createFromFormat( 'U', strval($this->_time->getTimestamp()))->add($nextDayPeriod)->format('Y-m-d');
                    $time = \DateTime::createFromFormat( 'U', strval( $this->_time->getTimestamp()))->format('H:i');

                    $itemDay = \DateTime::createFromFormat( 'U', strval($item['timestamp']))->format('Y-m-d');
                    if ($itemDay === $nextDay) {
                        $targetTime = \DateTime::createFromFormat( 'U', strval($item['timestamp']))->format('H:i');
                        if ($time === $targetTime) {
                            $this->_logger->info('Adding same time slot for the next day.');
                            return parent::add( $item);
                        }
                    }
                    return false;
                }

                public function setLogger($logger) {
                    $this->_logger = $logger;
                }
            };
            $newClass->setLogger($this->_logger);
            return $newClass;
        }

        if ($key === self::KEY_NEXT_DAY_TIME_BEFORE_REQUEST_TIME_IF_REQUEST_DAY_IS_NOT_PRESENT) {
            $newClass = new class( $targetTime) extends DefaultFreeSlotValidator {
                /** @var \Psr\Log\LoggerInterface */
                private $_logger;
                private $_array;
                private $_daysPassedSinceTargetDay = 0;
                private $_isDayReallyMissing = true;
                private $_targetDayAsNumber = 0;
                public function add( $item)
                {
                    $nextDayPeriod = new \DateInterval('P1D');
                    $nextDay = \DateTime::createFromFormat( 'U', strval($this->_time->getTimestamp()))->add($nextDayPeriod)->format('Y-m-d');
                    $requestDay = \DateTime::createFromFormat( 'U', strval( $this->_time->getTimestamp()))->format('Y-m-d');
                    $time = \DateTime::createFromFormat( 'U', strval( $this->_time->getTimestamp()))->format('Y-m-d H:i');

                    $itemDay = \DateTime::createFromFormat( 'U', strval($item['timestamp']))->format('Y-m-d');

                    if ($requestDay === $itemDay) {
                        $this->_isDayReallyMissing = false;
                    }

                    if ($itemDay === $nextDay) {
                        $targetDay = \DateTime::createFromFormat( 'U', strval($item['timestamp']))->format('Y-m-d');
                        $this->_targetDayAsNumber = intval(str_replace('-', '', $targetDay));
                        $targetTime = \DateTime::createFromFormat( 'U', strval($item['timestamp']))->format('Y-m-d H:i');
                        $this->_array[] = $targetTime;
                    }

                    $itemDayAsNumber = intval(str_replace('-', '', $itemDay));
                    if ($itemDayAsNumber > $this->_targetDayAsNumber && $this->_targetDayAsNumber > 0) {
                        $this->_daysPassedSinceTargetDay++;
                    }

                    if ($this->_daysPassedSinceTargetDay === 1 && $this->_isDayReallyMissing) {
                        $timesSmallerThenRequestTime = [];
                        foreach ($this->_array as $value) {
                            $valueHoursPartOnly = explode(' ', $value)[1];
                            $timeHoursPartOnly = explode(' ', $time)[1];
                            if ($this->_timeToSeconds($valueHoursPartOnly) < $this->_timeToSeconds($timeHoursPartOnly)) {
                                $timesSmallerThenRequestTime[] = $value;
                            }
                        }

                        $timesSmallerThenRequestTimeValue = $timesSmallerThenRequestTime[count($timesSmallerThenRequestTime) - 1] ?? '';
                        if (!empty($timesSmallerThenRequestTimeValue)) {
                            $this->_logger->info('Adding time slot before requested time slot on the next day.');
                            return parent::add(['timestamp' => strtotime($timesSmallerThenRequestTimeValue)]);
                        }
                    }

                    return false;
                }

                private function _timeToSeconds(string $time): int
                {
                    $arr = explode(':', $time);
                    if (count($arr) === 3) {
                        return $arr[0] * 3600 + $arr[1] * 60 + $arr[2];
                    }
                    return $arr[0] * 60 + $arr[1];
                }

                public function setLogger($logger) {
                    $this->_logger = $logger;
                }
            };
            $newClass->setLogger($this->_logger);
            return $newClass;
        }

        if ($key === self::KEY_NEXT_DAY_TIME_AFTER_REQUEST_TIME_IF_REQUEST_DAY_IS_NOT_PRESENT) {
            $newClass = new class( $targetTime) extends DefaultFreeSlotValidator {
                /**
                 * @var \Psr\Log\LoggerInterface
                 */
                private $_logger;

                private $_isDayReallyMissing = true;

                public function add( $item)
                {
                    $nextDayPeriod = new \DateInterval('P1D');
                    $nextDay = \DateTime::createFromFormat( 'U', strval($this->_time->getTimestamp()))->add($nextDayPeriod)->format('Y-m-d');
                    $time = \DateTime::createFromFormat( 'U', strval( $this->_time->getTimestamp()))->format('H:i');
                    $requestDay = \DateTime::createFromFormat( 'U', strval( $this->_time->getTimestamp()))->format('Y-m-d');
                    $itemDay = \DateTime::createFromFormat( 'U', strval($item['timestamp']))->format('Y-m-d');

                    if ($requestDay === $itemDay) {
                        $this->_isDayReallyMissing = false;
                    }

                    if ($itemDay === $nextDay && $this->_isDayReallyMissing) {
                        $targetTime = \DateTime::createFromFormat( 'U', strval($item['timestamp']))->format('H:i');
                        if ($this->_timeToSeconds($targetTime) >= $this->_timeToSeconds($time)) {
                            $this->_logger->info('Adding time slot after requested time slot on the next day.');
                            return parent::add( $item);
                        }
                    }
                    return false;
                }

                private function _timeToSeconds(string $time): int
                {
                    $arr = explode(':', $time);
                    if (count($arr) === 3) {
                        return $arr[0] * 3600 + $arr[1] * 60 + $arr[2];
                    }
                    return $arr[0] * 60 + $arr[1];
                }

                public function setLogger($logger) {
                    $this->_logger = $logger;
                }
            };
            $newClass->setLogger($this->_logger);
            return $newClass;
        }

        if ($key === self::KEY_NEXT_WEEK_SAME_TIME_IF_REQUEST_DAY_IS_NOT_PRESENT) {
            $newClass = new class( $targetTime) extends DefaultFreeSlotValidator {
                /**
                 * @var \Psr\Log\LoggerInterface
                 */
                private $_logger;

                private $_isDayReallyMissing = true;

                public function add( $item)
                {

                    $nextWeekPeriod = new \DateInterval('P1W');
                    $nextWeek = \DateTime::createFromFormat( 'U', strval($this->_time->getTimestamp()))->add($nextWeekPeriod)->getTimestamp();
                    $time = \DateTime::createFromFormat( 'U', strval( $this->_time->getTimestamp()))->format('H:i');
                    $itemDay = \DateTime::createFromFormat( 'U', strval($item['timestamp']))->getTimestamp();

                    $requestDay = \DateTime::createFromFormat( 'U', strval( $this->_time->getTimestamp()))->format('Y-m-d');
                    $itemDayFormatted = \DateTime::createFromFormat( 'U', strval($item['timestamp']))->format('Y-m-d');

                    if ($requestDay === $itemDayFormatted) {
                        $this->_isDayReallyMissing = false;
                    }

                    if ($itemDay >= $nextWeek && $this->_isDayReallyMissing) {
                        $targetTime = \DateTime::createFromFormat( 'U', strval($item['timestamp']))->format('H:i');
                        if ($time === $targetTime) {
                            $this->_logger->info('Adding same time slot in one week time period.');
                            return parent::add( $item);
                        }
                    }
                    return false;
                }

                public function setLogger($logger) {
                    $this->_logger = $logger;
                }
            };
            $newClass->setLogger($this->_logger);
            return $newClass;
        }

        if ($key === self::KEY_NEXT_DAY_TIME_BEFORE_REQUEST_TIME_IF_REQUEST_DAY_IS_NOT_PRESENT) {
            $newClass = new class( $targetTime) extends DefaultFreeSlotValidator {
                /** @var \Psr\Log\LoggerInterface */
                private $_logger;
                private $_array;
                private $_daysPassedSinceTargetDay = 0;
                private $_isRequestDayReallyMissing = true;
                private $_targetDayAsNumber = 0;
                public function add( $item)
                {
                    $nextDayPeriod = new \DateInterval('P1D');
                    $nextDay = \DateTime::createFromFormat( 'U', strval($this->_time->getTimestamp()))->add($nextDayPeriod)->format('Y-m-d');
                    $requestDay = \DateTime::createFromFormat( 'U', strval( $this->_time->getTimestamp()))->format('Y-m-d');
                    $time = \DateTime::createFromFormat( 'U', strval( $this->_time->getTimestamp()))->format('Y-m-d H:i');

                    $itemDay = \DateTime::createFromFormat( 'U', strval($item['timestamp']))->format('Y-m-d');

                    if ($requestDay === $itemDay) {
                        $this->_isRequestDayReallyMissing = false;
                    }

                    if ($itemDay === $nextDay) {
                        $targetDay = \DateTime::createFromFormat( 'U', strval($item['timestamp']))->format('Y-m-d');
                        $this->_targetDayAsNumber = intval(str_replace('-', '', $targetDay));
                        $targetTime = \DateTime::createFromFormat( 'U', strval($item['timestamp']))->format('Y-m-d H:i');
                        $this->_array[] = $targetTime;
                    }

                    $itemDayAsNumber = intval(str_replace('-', '', $itemDay));
                    if ($itemDayAsNumber > $this->_targetDayAsNumber && $this->_targetDayAsNumber > 0) {
                        $this->_daysPassedSinceTargetDay++;
                    }

                    if ($this->_daysPassedSinceTargetDay === 1 && $this->_isRequestDayReallyMissing) {
                        $timesSmallerThenRequestTime = [];
                        foreach ($this->_array as $value) {
                            $valueHoursPartOnly = explode(' ', $value)[1];
                            $timeHoursPartOnly = explode(' ', $time)[1];
                            if ($this->_timeToSeconds($valueHoursPartOnly) < $this->_timeToSeconds($timeHoursPartOnly)) {
                                $timesSmallerThenRequestTime[] = $value;
                            }
                        }

                        $timesSmallerThenRequestTimeValue = $timesSmallerThenRequestTime[count($timesSmallerThenRequestTime) - 1] ?? '';
                        if (!empty($timesSmallerThenRequestTimeValue)) {
                            $this->_logger->info('Adding time slot before requested time slot on the next day if day is booked.');
                            return parent::add(['timestamp' => strtotime($timesSmallerThenRequestTimeValue)]);
                        }
                    }

                    return false;
                }

                private function _timeToSeconds(string $time): int
                {
                    $arr = explode(':', $time);
                    if (count($arr) === 3) {
                        return $arr[0] * 3600 + $arr[1] * 60 + $arr[2];
                    }
                    return $arr[0] * 60 + $arr[1];
                }

                public function setLogger($logger) {
                    $this->_logger = $logger;
                }
            };
            $newClass->setLogger($this->_logger);
            return $newClass;
        }

        if ($key === self::KEY_NEXT_DAY_TIME_AFTER_REQUEST_TIME_IF_REQUEST_DAY_IS_NOT_PRESENT) {
            $newClass = new class( $targetTime) extends DefaultFreeSlotValidator {
                /**
                 * @var \Psr\Log\LoggerInterface
                 */
                private $_logger;

                private $_isDayReallyMissing = true;

                public function add( $item)
                {
                    $nextDayPeriod = new \DateInterval('P1D');
                    $nextDay = \DateTime::createFromFormat( 'U', strval($this->_time->getTimestamp()))->add($nextDayPeriod)->format('Y-m-d');
                    $time = \DateTime::createFromFormat( 'U', strval( $this->_time->getTimestamp()))->format('H:i');
                    $requestDay = \DateTime::createFromFormat( 'U', strval( $this->_time->getTimestamp()))->format('Y-m-d');
                    $itemDay = \DateTime::createFromFormat( 'U', strval($item['timestamp']))->format('Y-m-d');

                    if ($requestDay === $itemDay) {
                        $this->_isDayReallyMissing = false;
                    }

                    if ($itemDay === $nextDay && $this->_isDayReallyMissing) {
                        $targetTime = \DateTime::createFromFormat( 'U', strval($item['timestamp']))->format('H:i');
                        if ($this->_timeToSeconds($targetTime) >= $this->_timeToSeconds($time)) {
                            $this->_logger->info('Adding time slot after requested time slot on the next day if day is booked.');
                            return parent::add( $item);
                        }
                    }
                    return false;
                }

                private function _timeToSeconds(string $time): int
                {
                    $arr = explode(':', $time);
                    if (count($arr) === 3) {
                        return $arr[0] * 3600 + $arr[1] * 60 + $arr[2];
                    }
                    return $arr[0] * 60 + $arr[1];
                }

                public function setLogger($logger) {
                    $this->_logger = $logger;
                }
            };
            $newClass->setLogger($this->_logger);
            return $newClass;
        }

        if ($key === self::KEY_NEXT_DAY_TIME_BEFORE_REQUEST_TIME_IF_REQUEST_DAY_AND_DAY_AFTER_IS_NOT_PRESENT) {
            $newClass = new class( $targetTime) extends DefaultFreeSlotValidator {
                /** @var \Psr\Log\LoggerInterface */
                private $_logger;

                private $_array;

                private $_daysPassedSinceTargetDay = 0;

                private $_isRequestDayReallyMissing = true;

                private $_isDayAfterReallyMissing = true;

                private $_targetDayAsNumber = 0;

                public function add( $item)
                {
                    $nextDayPeriod = new \DateInterval('P1D');
                    $dayAfterNonWorking2DaysPeriod = new \DateInterval('P2D');
                    $nextDay = \DateTime::createFromFormat( 'U', strval($this->_time->getTimestamp()))->add($nextDayPeriod)->format('Y-m-d');
                    $dayAfterNonWorking2Days = \DateTime::createFromFormat( 'U', strval($this->_time->getTimestamp()))->add($dayAfterNonWorking2DaysPeriod)->format('Y-m-d');
                    $requestDay = \DateTime::createFromFormat( 'U', strval( $this->_time->getTimestamp()))->format('Y-m-d');
                    $time = \DateTime::createFromFormat( 'U', strval( $this->_time->getTimestamp()))->format('Y-m-d H:i');

                    $itemDay = \DateTime::createFromFormat( 'U', strval($item['timestamp']))->format('Y-m-d');

                    if ($requestDay === $itemDay) {
                        $this->_isRequestDayReallyMissing = false;
                    }

                    if ($requestDay === $nextDay) {
                        $this->_isDayAfterReallyMissing = false;
                    }

                    if ($itemDay === $dayAfterNonWorking2Days) {
                        $targetDay = \DateTime::createFromFormat( 'U', strval($item['timestamp']))->format('Y-m-d');
                        $this->_targetDayAsNumber = intval(str_replace('-', '', $targetDay));
                        $targetTime = \DateTime::createFromFormat( 'U', strval($item['timestamp']))->format('Y-m-d H:i');
                        $this->_array[] = $targetTime;
                    }

                    $itemDayAsNumber = intval(str_replace('-', '', $itemDay));
                    if ($itemDayAsNumber > $this->_targetDayAsNumber && $this->_targetDayAsNumber > 0) {
                        $this->_daysPassedSinceTargetDay++;
                    }

                    if ($this->_daysPassedSinceTargetDay === 1 && $this->_isRequestDayReallyMissing && $this->_isDayAfterReallyMissing) {
                        $timesSmallerThenRequestTime = [];
                        foreach ($this->_array as $value) {
                            $valueHoursPartOnly = explode(' ', $value)[1];
                            $timeHoursPartOnly = explode(' ', $time)[1];
                            if ($this->_timeToSeconds($valueHoursPartOnly) < $this->_timeToSeconds($timeHoursPartOnly)) {
                                $timesSmallerThenRequestTime[] = $value;
                            }
                        }

                        $timesSmallerThenRequestTimeValue = $timesSmallerThenRequestTime[count($timesSmallerThenRequestTime) - 1] ?? '';
                        if (!empty($timesSmallerThenRequestTimeValue)) {
                            $this->_logger->info('Adding time slot before requested time slot on the day before if request day and day after is booked.');
                            return parent::add(['timestamp' => strtotime($timesSmallerThenRequestTimeValue)]);
                        }
                    }

                    return false;
                }

                private function _timeToSeconds(string $time): int
                {
                    $arr = explode(':', $time);
                    if (count($arr) === 3) {
                        return $arr[0] * 3600 + $arr[1] * 60 + $arr[2];
                    }
                    return $arr[0] * 60 + $arr[1];
                }

                public function setLogger($logger) {
                    $this->_logger = $logger;
                }
            };
            $newClass->setLogger($this->_logger);
            return $newClass;
        }

        if ($key === self::KEY_NEXT_DAY_TIME_AFTER_REQUEST_TIME_IF_REQUEST_DAY_AND_DAY_AFTER_IS_NOT_PRESENT) {
            $newClass = new class( $targetTime) extends DefaultFreeSlotValidator {
                /** @var \Psr\Log\LoggerInterface */
                private $_logger;

                private $_isRequestDayReallyMissing = true;

                private $_isDayAfterReallyMissing = true;

                public function add( $item)
                {
                    $nextDayPeriod = new \DateInterval('P1D');
                    $dayAfterNonWorking2DaysPeriod = new \DateInterval('P2D');
                    $nextDay = \DateTime::createFromFormat( 'U', strval($this->_time->getTimestamp()))->add($nextDayPeriod)->format('Y-m-d');
                    $dayAfterNonWorking2Days = \DateTime::createFromFormat( 'U', strval($this->_time->getTimestamp()))->add($dayAfterNonWorking2DaysPeriod)->format('Y-m-d');
                    $requestDay = \DateTime::createFromFormat( 'U', strval( $this->_time->getTimestamp()))->format('Y-m-d');
                    $time = \DateTime::createFromFormat( 'U', strval( $this->_time->getTimestamp()))->format('H:i');

                    $itemDay = \DateTime::createFromFormat( 'U', strval($item['timestamp']))->format('Y-m-d');

                    if ($requestDay === $itemDay) {
                        $this->_isRequestDayReallyMissing = false;
                    }

                    if ($requestDay === $nextDay) {
                        $this->_isDayAfterReallyMissing = false;
                    }

                    if ($itemDay === $dayAfterNonWorking2Days && $this->_isRequestDayReallyMissing && $this->_isDayAfterReallyMissing) {
                        $targetTime = \DateTime::createFromFormat( 'U', strval($item['timestamp']))->format('H:i');
                        if ($this->_timeToSeconds($targetTime) >= $this->_timeToSeconds($time)) {
                            $this->_logger->info('Adding time slot before requested time slot on the day after if request day and day after is booked.');
                            return parent::add( $item);
                        }
                    }

                    return false;
                }

                private function _timeToSeconds(string $time): int
                {
                    $arr = explode(':', $time);
                    if (count($arr) === 3) {
                        return $arr[0] * 3600 + $arr[1] * 60 + $arr[2];
                    }
                    return $arr[0] * 60 + $arr[1];
                }

                public function setLogger($logger) {
                    $this->_logger = $logger;
                }
            };
            $newClass->setLogger($this->_logger);
            return $newClass;
        }

        if ( $key == self::KEY_DAY_BEFORE_SAME_TIME_IF_REQUEST_DAY_AND_DAY_AFTER_IS_NOT_PRESENT) {
            $newClass = new class( $targetTime) extends DefaultFreeSlotValidator {
                /**
                 * @var \Psr\Log\LoggerInterface
                 */
                private $_logger;

                private $_isRequestDayReallyMissing = true;

                private $_isDayAfterReallyMissing = true;

                private $_timeSlotBefore = 0;

                public function add( $item)
                {
                    $nextDayPeriod = new \DateInterval('P1D');
                    $dayBefore = \DateTime::createFromFormat( 'U', strval($this->_time->getTimestamp()))->sub($nextDayPeriod)->format('Y-m-d');
                    $itemDay = \DateTime::createFromFormat( 'U', strval($item['timestamp']))->getTimestamp();

                    $time = \DateTime::createFromFormat( 'U', strval( $this->_time->getTimestamp()))->format('H:i');

                    $nextDay = \DateTime::createFromFormat( 'U', strval($this->_time->getTimestamp()))->add($nextDayPeriod)->format('Y-m-d');
                    $requestDay = \DateTime::createFromFormat( 'U', strval( $this->_time->getTimestamp()))->format('Y-m-d');
                    $itemDayFormatted = \DateTime::createFromFormat( 'U', strval($item['timestamp']))->format('Y-m-d');
                    $itemTimeFormatted = \DateTime::createFromFormat( 'U', strval($item['timestamp']))->format('H:i');

                    if ($itemDayFormatted === $dayBefore) {
                        if ($time === $itemTimeFormatted) {
                            $this->_timeSlotBefore = $itemDay;
                        }
                    }

                    if ($requestDay === $itemDayFormatted) {
                        $this->_isRequestDayReallyMissing = false;
                    }

                    if ($nextDay === $itemDayFormatted) {
                        $this->_isDayAfterReallyMissing = false;
                    }

                    if ($this->_isRequestDayReallyMissing && $this->_isDayAfterReallyMissing) {
                        if (!empty($this->_timeSlotBefore)) {
                            $this->_logger->info('Adding time slot before requested time slot on the day before if request day and day after is booked.');
                            return parent::add( ['timestamp' => $this->_timeSlotBefore]);
                        }
                    }
                    return false;
                }

                public function setLogger($logger) {
                    $this->_logger = $logger;
                }
            };
            $newClass->setLogger($this->_logger);
            return $newClass;
        }

        if ( $key == self::KEY_NEXT_WEEK_SAME_TIME_IF_REQUEST_DAY_AND_DAY_AFTER_IS_NOT_PRESENT) {
            $newClass = new class( $targetTime) extends DefaultFreeSlotValidator {
                /**
                 * @var \Psr\Log\LoggerInterface
                 */
                private $_logger;

                private $_isRequestDayReallyMissing = true;

                private $_isDayAfterReallyMissing = true;

                public function add( $item)
                {

                    $nextWeekPeriod = new \DateInterval('P1W');
                    $nextDayPeriod = new \DateInterval('P1D');
                    $nextWeek = \DateTime::createFromFormat( 'U', strval($this->_time->getTimestamp()))->add($nextWeekPeriod)->getTimestamp();
                    $itemDay = \DateTime::createFromFormat( 'U', strval($item['timestamp']))->getTimestamp();

                    $time = \DateTime::createFromFormat( 'U', strval( $this->_time->getTimestamp()))->format('H:i');

                    $nextDay = \DateTime::createFromFormat( 'U', strval($this->_time->getTimestamp()))->add($nextDayPeriod)->format('Y-m-d');
                    $requestDay = \DateTime::createFromFormat( 'U', strval( $this->_time->getTimestamp()))->format('Y-m-d');
                    $itemDayFormatted = \DateTime::createFromFormat( 'U', strval($item['timestamp']))->format('Y-m-d');

                    if ($requestDay === $itemDayFormatted) {
                        $this->_isRequestDayReallyMissing = false;
                    }

                    if ($nextDay === $itemDayFormatted) {
                        $this->_isDayAfterReallyMissing = false;
                    }

                    if ($this->_isRequestDayReallyMissing && $this->_isDayAfterReallyMissing) {
                        if ($itemDay >= $nextWeek) {
                            $targetTime = \DateTime::createFromFormat( 'U', strval($item['timestamp']))->format('H:i');
                            if ($time === $targetTime) {
                                $this->_logger->info('Adding time slot in next week period slot on the day before if request day and day after is booked.');
                                return parent::add( $item);
                            }
                        }
                    }
                    return false;
                }

                public function setLogger($logger) {
                    $this->_logger = $logger;
                }
            };
            $newClass->setLogger($this->_logger);
            return $newClass;
        }

        throw new \Exception( 'Unexpected key ['.$key.']');
	}

    // UTIL
    public function __toString()
    {
        return parent::__toString().'['.$this->_maxSuggestions.']';
    }


}
