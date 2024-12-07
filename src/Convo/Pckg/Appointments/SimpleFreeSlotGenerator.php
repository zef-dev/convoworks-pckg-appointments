<?php

namespace Convo\Pckg\Appointments;

use Convo\Core\Workflow\AbstractWorkflowComponent;
use Convo\Pckg\Appointments\Freeslot\IFreeSlotQueueFactory;
use Convo\Pckg\Appointments\Freeslot\DefaultFreeSlotValidator;
use Convo\Pckg\Appointments\Freeslot\FreeSlotQueue;

/**
 * A simple free slot generator that returns a broad set of available slots
 * without aggressive filtering. This allows other layers (like GPT) to
 * perform the selection logic.
 */
class SimpleFreeSlotGenerator extends AbstractWorkflowComponent implements IFreeSlotQueueFactory
{
    /**
     * @var string
     */
    private $_maxSuggestions;

    public function __construct($properties)
    {
        parent::__construct($properties);
        $this->_maxSuggestions = $properties['max_suggestions'] ?? 50; // Default to something larger
    }

    public function createStack($targetTime, $systemTimezone)
    {
        $queue = new FreeSlotQueue($systemTimezone, $this->evaluateString($this->_maxSuggestions), 0);

        $this->_logger->debug('Creating simple validator');
        $validator = new class($targetTime) extends DefaultFreeSlotValidator {
            private $_values = [];
            public function __construct($targetTime)
            {
                parent::__construct($targetTime);
            }

            public function add($item)
            {
                if ($item['timestamp'] > $this->_time->getTimestamp()) {
                    $this->_values[] = $item;
                    return true;
                }

                return false;
            }

            public function active()
            {
                return true;
            }

            public function values()
            {
                return $this->_values;
            }
        };

        $queue->addValidator($validator);

        return $queue;
    }

    public function __toString()
    {
        return parent::__toString() . '[' . $this->_maxSuggestions . ']';
    }
}
