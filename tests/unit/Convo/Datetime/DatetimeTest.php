<?php

namespace Convo\Datetime;

class DatetimeTest extends \PHPUnit\Framework\TestCase
{
    const DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    public function testDateTimeImmutableTimestamps() {
        $datetime = '2022-01-14 18:00';
        $datetimeUtc = new \DateTimeImmutable($datetime, new \DateTimeZone('UTC'));

        $datetimeUsNy = new \DateTimeImmutable($datetime, new \DateTimeZone('America/New_York'));
        $datetimeEuMo = new \DateTimeImmutable($datetime, new \DateTimeZone('Europe/Moscow'));
        $datetimeEuZg = new \DateTimeImmutable($datetime, new \DateTimeZone('Europe/Zagreb'));

        $this->assertNotEquals($datetimeUtc->getTimestamp(), $datetimeUsNy->getTimestamp());
        $this->assertNotEquals($datetimeUtc->getTimestamp(), $datetimeEuMo->getTimestamp());
        $this->assertNotEquals($datetimeUtc->getTimestamp(), $datetimeEuZg->getTimestamp());
    }

    public function testUtcDateTimeImmutableFormat() {
        $datetime = '2022-01-14 18:00';
        $datetimeUtc = new \DateTimeImmutable($datetime, new \DateTimeZone('UTC'));

        $this->assertEquals(
            $datetimeUtc->format(self::DATE_TIME_FORMAT),
            gmdate(self::DATE_TIME_FORMAT, $datetimeUtc->getTimestamp())
        );
    }

    public function testUtcDateTimeImmutableFormatWithAndWithoutOffset() {
        $timezone = 'UTC';
        $datetime = '2022-01-14 18:00 +0200';
        $datetimeUtcWithOffset = new \DateTimeImmutable($datetime, new \DateTimeZone($timezone));

        // test that timezone is overridden by offset declaration in datetime string variable
        $this->assertNotEquals(
            $timezone,
            $datetimeUtcWithOffset->getTimezone()->getName()
        );
        $this->assertEquals(7200, $datetimeUtcWithOffset->getOffset());

        $datetime = '2022-01-14 18:00';
        $datetimeUtcWithoutOffset = new \DateTimeImmutable($datetime, new \DateTimeZone($timezone));

        // test that timezone is in UTC
        $this->assertEquals(
            $timezone,
            $datetimeUtcWithoutOffset->getTimezone()->getName()
        );
        $this->assertEquals(0, $datetimeUtcWithoutOffset->getOffset());

        // test that timestamps are not equal despite the timezone was given in both
        // $datetimeUtcWithOffset and $datetimeUtcWithoutOffset
        $this->assertNotEquals($datetimeUtcWithOffset->getTimestamp(), $datetimeUtcWithoutOffset->getTimestamp());
    }
}
