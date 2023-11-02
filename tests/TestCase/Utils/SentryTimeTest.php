<?php
declare(strict_types=1);

namespace App\Test\TestCase\Utils;

use App\Utils\SentryTime;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

class SentryTimeTest extends TestCase
{
    public function testGetStartOfCurrentQuarter(): void
    {
        $mockTime = new DateTime('2023-01-01 09:41:00');
        DateTime::setTestNow($mockTime);
        // Should be Q4 of 2022
        $this->assertEquals(new DateTime('2022-11-01 00:00:00'), SentryTime::getStartOfCurrentQuarter());

        $mockTime = new DateTime('2023-03-01 09:41:00');
        DateTime::setTestNow($mockTime);
        // Should be Q1 of 2023
        $this->assertEquals(new DateTime('2023-02-01 00:00:00'), SentryTime::getStartOfCurrentQuarter());

        $mockTime = new DateTime('2023-06-01 09:41:00');
        DateTime::setTestNow($mockTime);
        // Should be Q2 of 2023
        $this->assertEquals(new DateTime('2023-05-01 00:00:00'), SentryTime::getStartOfCurrentQuarter());

        $mockTime = new DateTime('2023-10-01 09:41:00');
        DateTime::setTestNow($mockTime);
        // Should be Q3 of 2023
        $this->assertEquals(new DateTime('2023-08-01 00:00:00'), SentryTime::getStartOfCurrentQuarter());
    }
}
