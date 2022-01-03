<?php

namespace Convo\Pckg\Appointments;

use Convo\Core\Util\ArrayUtil;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Core\Workflow\IConversationElement;

abstract class AbstractAppointmentElement extends AbstractWorkflowContainerComponent implements IConversationElement
{
	/**
	 * @var string
	 */
	protected $_contextId;


	/**
	 * @param array $properties
	 */
	public function __construct( $properties)
	{
		parent::__construct( $properties);

		$this->_contextId         =   $properties['context_id'];
	}

	/**
	 * @return IAppointmentsContext
	 */
	protected function _getSimpleSchedulingContext()
	{
		return $this->getService()->findContext(
			$this->evaluateString( $this->_contextId),
			IAppointmentsContext::class);
	}

	protected function _evaluateArgs( $args)
	{
		// $this->_logger->debug( 'Got raw args ['.print_r( $args, true).']');
		$returnedArgs   =   [];
		foreach ( $args as $key => $val)
		{
			$key	=	$this->evaluateString( $key);
			$parsed =   $this->evaluateString( $val);

			if ( !ArrayUtil::isComplexKey( $key))
			{
				$returnedArgs[$key] =   $parsed;
			}
			else
			{
				$root           =   ArrayUtil::getRootOfKey( $key);
				$final          =   ArrayUtil::setDeepObject( $key, $parsed, $returnedArgs[$root] ?? []);
				$returnedArgs[$root]    =   $final;
			}
		}
		// $this->_logger->debug( 'Got evaluated args ['.print_r( $returnedArgs, true).']');
		return $returnedArgs;
	}
	
	// UTIL
	public function __toString()
	{
	    return parent::__toString().'['.$this->_contextId.']';
	}
}