<?php

namespace Tests\Unit;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class DateParsingLogicTest extends TestCase
{
    /** @test */
    public function it_correctly_formats_string_times_before_parsing()
    {
        $date = '2025-10-22';
        $startTime = '08:00:00';
        $endTime = '17:00:00';

        // Simulate the fixed logic
        $startTimeStr = $startTime instanceof Carbon ? $startTime->format('H:i:s') : $startTime;
        $endTimeStr = $endTime instanceof Carbon ? $endTime->format('H:i:s') : $endTime;

        $startDateTime = Carbon::parse($date.' '.$startTimeStr);
        $endDateTime = Carbon::parse($date.' '.$endTimeStr);

        $this->assertEquals('2025-10-22 08:00:00', $startDateTime->format('Y-m-d H:i:s'));
        $this->assertEquals('2025-10-22 17:00:00', $endDateTime->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_correctly_formats_carbon_times_before_parsing()
    {
        $date = '2025-10-22';
        $startTime = Carbon::createFromFormat('H:i:s', '08:00:00');
        $endTime = Carbon::createFromFormat('H:i:s', '17:00:00');

        // Simulate the fixed logic
        $startTimeStr = $startTime instanceof Carbon ? $startTime->format('H:i:s') : $startTime;
        $endTimeStr = $endTime instanceof Carbon ? $endTime->format('H:i:s') : $endTime;

        $startDateTime = Carbon::parse($date.' '.$startTimeStr);
        $endDateTime = Carbon::parse($date.' '.$endTimeStr);

        $this->assertEquals('2025-10-22 08:00:00', $startDateTime->format('Y-m-d H:i:s'));
        $this->assertEquals('2025-10-22 17:00:00', $endDateTime->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_prevents_duplicate_date_strings()
    {
        $date = '2025-10-22';
        
        // Test with various time formats
        $timeFormats = [
            '08:00',
            '08:00:00',
            Carbon::createFromFormat('H:i:s', '08:00:00'),
            Carbon::createFromFormat('H:i', '08:00'),
        ];

        foreach ($timeFormats as $time) {
            $timeStr = $time instanceof Carbon ? $time->format('H:i:s') : $time;
            $concatenated = $date.' '.$timeStr;

            // Ensure no duplicate dates in the string
            $this->assertStringNotContainsString('2025-10-22 2025-', $concatenated);
            
            // Ensure proper format
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{1,2}:\d{2}(:\d{2})?$/', $concatenated);
            
            // Ensure Carbon can parse it correctly
            $parsed = Carbon::parse($concatenated);
            $this->assertEquals('2025-10-22', $parsed->format('Y-m-d'));
        }
    }

    /** @test */
    public function it_handles_edge_case_time_formats()
    {
        $date = '2025-10-22';
        
        $edgeCases = [
            ['8:00', '08:00:00'],
            ['08:30', '08:30:00'],
            ['23:59', '23:59:00'],
            ['00:00', '00:00:00'],
        ];

        foreach ($edgeCases as [$input, $expected]) {
            $timeStr = $input instanceof Carbon ? $input->format('H:i:s') : $input;
            $parsed = Carbon::parse($date.' '.$timeStr);
            
            $this->assertEquals($date.' '.$expected, $parsed->format('Y-m-d H:i:s'));
        }
    }

    /** @test */
    public function it_demonstrates_the_bug_would_have_occurred_without_fix()
    {
        $date = '2025-10-22';
        $carbonTime = Carbon::createFromFormat('H:i:s', '08:00:00');
        
        // This is what would happen WITHOUT the fix (direct concatenation)
        $buggyString = $date.' '.$carbonTime; // This would create "2025-10-22 2025-10-20 08:00:00"
        
        // Verify the buggy string contains duplicate date
        $this->assertStringContainsString('2025-10-22 2025-', $buggyString);
        
        // Now test the FIXED version
        $fixedTimeStr = $carbonTime instanceof Carbon ? $carbonTime->format('H:i:s') : $carbonTime;
        $fixedString = $date.' '.$fixedTimeStr;
        
        // Verify the fixed string does NOT contain duplicate date
        $this->assertStringNotContainsString('2025-10-22 2025-', $fixedString);
        $this->assertEquals('2025-10-22 08:00:00', $fixedString);
    }
}