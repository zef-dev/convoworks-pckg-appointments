<?php

use Convo\Pckg\Appointments\Freeslot\DefaultFreeSlotValidator;

class DefaultFreeSlotValidatorTest extends \PHPUnit\Framework\TestCase
{

	public function testDefaultFreeSlotValidator() {
		$freeSlotValidator = new DefaultFreeSlotValidator(new \DateTime('now', new \DateTimeZone('Europe/Zagreb')));
		$added = $freeSlotValidator->add(['test']);
		$this->assertEquals(true, $added);
	}
}