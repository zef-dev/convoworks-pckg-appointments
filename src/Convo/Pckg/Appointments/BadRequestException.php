<?php
namespace Convo\Pckg\Appointments;

/**
 * @author Tole
 * 
 * Different implementations might require some additional data to be populeated. If it is missing, methods should throw BadRequestException
 *
 */
class BadRequestException extends \Exception
{}