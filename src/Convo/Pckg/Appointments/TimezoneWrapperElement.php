<?php

namespace Convo\Pckg\Appointments;

use Convo\Core\Adapters\Alexa\Api\AlexaSettingsApi;
use Convo\Core\Params\IServiceParamsScope;
use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;

class TimezoneWrapperElement extends AbstractAppointmentElement
{
    /**
     * @var string
     */
    private $_resultVar;

    /**
     * @var IConversationElement[]
     */
    private $_elements = array();

    /**
     * @param array $properties
     * @param AlexaSettingsApi $alexaSettingsApi
     */
    public function __construct( $properties, $alexaSettingsApi)
    {
        parent::__construct($properties, $alexaSettingsApi);

        $elements = $properties['elements'] ?? [];
        foreach ($elements as $element) {
            $this->addElement($element);
        }

        $this->_resultVar = $properties['result_var'];
    }

    /**
     * @param IConvoRequest $request
     * @param IConvoResponse $response
     */
    public function read(IConvoRequest $request, IConvoResponse $response)
    {
        $scope_type   =   IServiceParamsScope::SCOPE_TYPE_REQUEST;
        $params       =   $this->getService()->getComponentParams( $scope_type, $this);

        $timezone = $this->_getTimezone($request);

        $this->_logger->debug('Reading ['.count( $this->_elements).'] elements in timezone ['.$timezone->getName().']');

        $params->setServiceParam( $this->_resultVar, ['timezone' => $timezone]);
        $this->_readElementsInTimezone($this->_elements, $request, $response);
    }

    public function addElement(\Convo\Core\Workflow\IConversationElement $element)
    {
        $this->_elements[] = $element;
        $this->addChild($element);
    }

    /**
     * @return \Convo\Core\Workflow\IConversationElement[]
     */
    public function getElements() {
        return $this->_elements;
    }
}
