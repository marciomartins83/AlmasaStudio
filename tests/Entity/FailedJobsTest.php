<?php

namespace App\Tests\Entity;

use App\Entity\FailedJobs;
use PHPUnit\Framework\TestCase;

class FailedJobsTest extends TestCase
{
    public function testCreateFailedJob(): void
    {
        $job = new FailedJobs();
        $this->assertInstanceOf(FailedJobs::class, $job);
    }

    public function testBasicMethods(): void
    {
        $job = new FailedJobs();
        $this->assertTrue(method_exists($job, 'getId'));
    }
}
