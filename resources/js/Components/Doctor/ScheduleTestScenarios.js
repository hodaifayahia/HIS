/**
 * Schedule Test Scenarios
 * Comprehensive test cases for validating the doctor schedule management system
 */

import { ScheduleValidator } from './ScheduleValidation.js'

export class ScheduleTestScenarios {
    /**
     * Run all test scenarios
     */
    static runAllTests() {
        console.log('🧪 Running Schedule Management Test Scenarios...\n')
        
        const results = {
            passed: 0,
            failed: 0,
            total: 0,
            details: []
        }
        
        // Basic validation tests
        this.runBasicValidationTests(results)
        
        // Conflict detection tests
        this.runConflictDetectionTests(results)
        
        // Break validation tests
        this.runBreakValidationTests(results)
        
        // Business rules tests
        this.runBusinessRulesTests(results)
        
        // Time slot calculation tests
        this.runTimeSlotTests(results)
        
        // Multiple schedules validation tests
        this.runMultipleSchedulesTests(results)
        
        // Print summary
        console.log('\n📊 Test Summary:')
        console.log(`✅ Passed: ${results.passed}`)
        console.log(`❌ Failed: ${results.failed}`)
        console.log(`📈 Total: ${results.total}`)
        console.log(`🎯 Success Rate: ${((results.passed / results.total) * 100).toFixed(1)}%`)
        
        return results
    }
    
    /**
     * Test basic field validation
     */
    static runBasicValidationTests(results) {
        console.log('🔍 Testing Basic Validation...')
        
        // Test 1: Valid schedule
        this.runTest(results, 'Valid Schedule', () => {
            const schedule = {
                doctor_id: 1,
                day_of_week: 'monday',
                shift_period: 'morning',
                start_time: '08:00',
                end_time: '12:00',
                number_of_patients_per_day: 16
            }
            
            const validation = ScheduleValidator.validateSchedule(schedule)
            return validation.isValid
        })
        
        // Test 2: Missing required fields
        this.runTest(results, 'Missing Doctor ID', () => {
            const schedule = {
                day_of_week: 'monday',
                shift_period: 'morning',
                start_time: '08:00',
                end_time: '12:00',
                number_of_patients_per_day: 16
            }
            
            const validation = ScheduleValidator.validateSchedule(schedule)
            return !validation.isValid && validation.errors.some(e => e.includes('Doctor ID'))
        })
        
        // Test 3: Invalid shift period
        this.runTest(results, 'Invalid Shift Period', () => {
            const schedule = {
                doctor_id: 1,
                day_of_week: 'monday',
                shift_period: 'evening',
                start_time: '08:00',
                end_time: '12:00',
                number_of_patients_per_day: 16
            }
            
            const validation = ScheduleValidator.validateSchedule(schedule)
            return !validation.isValid && validation.errors.some(e => e.includes('morning or afternoon'))
        })
        
        // Test 4: Invalid time format
        this.runTest(results, 'Invalid Time Format', () => {
            const schedule = {
                doctor_id: 1,
                day_of_week: 'monday',
                shift_period: 'morning',
                start_time: '8:00',
                end_time: '12:00',
                number_of_patients_per_day: 16
            }
            
            const validation = ScheduleValidator.validateSchedule(schedule)
            return !validation.isValid && validation.errors.some(e => e.includes('HH:MM format'))
        })
    }
    
    /**
     * Test conflict detection
     */
    static runConflictDetectionTests(results) {
        console.log('⚔️ Testing Conflict Detection...')
        
        // Test 1: No conflicts
        this.runTest(results, 'No Schedule Conflicts', () => {
            const schedule = {
                doctor_id: 1,
                day_of_week: 'monday',
                shift_period: 'morning',
                start_time: '08:00',
                end_time: '12:00',
                number_of_patients_per_day: 16
            }
            
            const existingSchedules = [{
                doctor_id: 1,
                day_of_week: 'monday',
                shift_period: 'afternoon',
                start_time: '14:00',
                end_time: '18:00',
                number_of_patients_per_day: 16,
                is_active: true
            }]
            
            const validation = ScheduleValidator.validateSchedule(schedule, existingSchedules)
            return validation.isValid
        })
        
        // Test 2: Time overlap conflict
        this.runTest(results, 'Time Overlap Conflict', () => {
            const schedule = {
                doctor_id: 1,
                day_of_week: 'monday',
                shift_period: 'morning',
                start_time: '10:00',
                end_time: '14:00',
                number_of_patients_per_day: 16
            }
            
            const existingSchedules = [{
                doctor_id: 1,
                day_of_week: 'monday',
                shift_period: 'morning',
                start_time: '08:00',
                end_time: '12:00',
                number_of_patients_per_day: 16,
                is_active: true
            }]
            
            const validation = ScheduleValidator.validateSchedule(schedule, existingSchedules)
            return !validation.isValid && validation.errors.some(e => e.includes('conflicts'))
        })
        
        // Test 3: Different doctors, no conflict
        this.runTest(results, 'Different Doctors No Conflict', () => {
            const schedule = {
                doctor_id: 1,
                day_of_week: 'monday',
                shift_period: 'morning',
                start_time: '08:00',
                end_time: '12:00',
                number_of_patients_per_day: 16
            }
            
            const existingSchedules = [{
                doctor_id: 2,
                day_of_week: 'monday',
                shift_period: 'morning',
                start_time: '08:00',
                end_time: '12:00',
                number_of_patients_per_day: 16,
                is_active: true
            }]
            
            const validation = ScheduleValidator.validateSchedule(schedule, existingSchedules)
            return validation.isValid
        })
        
        // Test 4: Inactive schedule, no conflict
        this.runTest(results, 'Inactive Schedule No Conflict', () => {
            const schedule = {
                doctor_id: 1,
                day_of_week: 'monday',
                shift_period: 'morning',
                start_time: '08:00',
                end_time: '12:00',
                number_of_patients_per_day: 16
            }
            
            const existingSchedules = [{
                doctor_id: 1,
                day_of_week: 'monday',
                shift_period: 'morning',
                start_time: '08:00',
                end_time: '12:00',
                number_of_patients_per_day: 16,
                is_active: false
            }]
            
            const validation = ScheduleValidator.validateSchedule(schedule, existingSchedules)
            return validation.isValid
        })
    }
    
    /**
     * Test break validation
     */
    static runBreakValidationTests(results) {
        console.log('☕ Testing Break Validation...')
        
        // Test 1: Valid break configuration
        this.runTest(results, 'Valid Break Configuration', () => {
            const schedule = {
                doctor_id: 1,
                day_of_week: 'monday',
                shift_period: 'morning',
                start_time: '08:00',
                end_time: '12:00',
                number_of_patients_per_day: 16,
                break_duration: 30,
                break_times: JSON.stringify([{
                    start: '10:00',
                    end: '10:30'
                }])
            }
            
            const validation = ScheduleValidator.validateSchedule(schedule)
            return validation.isValid
        })
        
        // Test 2: Break outside schedule time
        this.runTest(results, 'Break Outside Schedule Time', () => {
            const schedule = {
                doctor_id: 1,
                day_of_week: 'monday',
                shift_period: 'morning',
                start_time: '08:00',
                end_time: '12:00',
                number_of_patients_per_day: 16,
                break_duration: 30,
                break_times: JSON.stringify([{
                    start: '13:00',
                    end: '13:30'
                }])
            }
            
            const validation = ScheduleValidator.validateSchedule(schedule)
            return !validation.isValid && validation.errors.some(e => e.includes('within the schedule time'))
        })
        
        // Test 3: Excessive break duration
        this.runTest(results, 'Excessive Break Duration', () => {
            const schedule = {
                doctor_id: 1,
                day_of_week: 'monday',
                shift_period: 'morning',
                start_time: '08:00',
                end_time: '12:00',
                number_of_patients_per_day: 16,
                break_duration: 150 // 2.5 hours out of 4 hour shift
            }
            
            const validation = ScheduleValidator.validateSchedule(schedule)
            return !validation.isValid && validation.errors.some(e => e.includes('50% of shift'))
        })
    }
    
    /**
     * Test business rules
     */
    static runBusinessRulesTests(results) {
        console.log('📋 Testing Business Rules...')
        
        // Test 1: Maximum patients per hour
        this.runTest(results, 'Maximum Patients Per Hour', () => {
            const schedule = {
                doctor_id: 1,
                day_of_week: 'monday',
                shift_period: 'morning',
                start_time: '08:00',
                end_time: '12:00',
                number_of_patients_per_day: 40 // 10 patients per hour
            }
            
            const options = { maxPatientsPerHour: 8 }
            const validation = ScheduleValidator.validateSchedule(schedule, [], options)
            return !validation.isValid && validation.errors.some(e => e.includes('exceeds maximum'))
        })
        
        // Test 2: Minimum time per patient
        this.runTest(results, 'Minimum Time Per Patient', () => {
            const schedule = {
                doctor_id: 1,
                day_of_week: 'monday',
                shift_period: 'morning',
                start_time: '08:00',
                end_time: '12:00',
                number_of_patients_per_day: 48 // 5 minutes per patient
            }
            
            const options = { minMinutesPerPatient: 10 }
            const validation = ScheduleValidator.validateSchedule(schedule, [], options)
            return !validation.isValid && validation.errors.some(e => e.includes('below minimum'))
        })
        
        // Test 3: Weekend restriction
        this.runTest(results, 'Weekend Restriction', () => {
            const schedule = {
                doctor_id: 1,
                day_of_week: 'saturday',
                shift_period: 'morning',
                start_time: '08:00',
                end_time: '12:00',
                number_of_patients_per_day: 16
            }
            
            const options = { restrictWeekends: true }
            const validation = ScheduleValidator.validateSchedule(schedule, [], options)
            return !validation.isValid && validation.errors.some(e => e.includes('Weekend'))
        })
        
        // Test 4: Future date validation
        this.runTest(results, 'Past Date Validation', () => {
            const yesterday = new Date()
            yesterday.setDate(yesterday.getDate() - 1)
            
            const schedule = {
                doctor_id: 1,
                date: yesterday.toISOString().split('T')[0],
                shift_period: 'morning',
                start_time: '08:00',
                end_time: '12:00',
                number_of_patients_per_day: 16
            }
            
            const validation = ScheduleValidator.validateSchedule(schedule)
            return !validation.isValid && validation.errors.some(e => e.includes('past dates'))
        })
    }
    
    /**
     * Test time slot calculations
     */
    static runTimeSlotTests(results) {
        console.log('⏰ Testing Time Slot Calculations...')
        
        // Test 1: Basic time slots
        this.runTest(results, 'Basic Time Slots', () => {
            const schedule = {
                start_time: '08:00',
                end_time: '10:00'
            }
            
            const slots = ScheduleValidator.calculateAvailableSlots(schedule, 30)
            return slots.length === 4 && slots[0].start === '08:00' && slots[3].end === '10:00'
        })
        
        // Test 2: Time slots with breaks
        this.runTest(results, 'Time Slots With Breaks', () => {
            const schedule = {
                start_time: '08:00',
                end_time: '12:00',
                break_times: JSON.stringify([{
                    start: '10:00',
                    end: '10:30'
                }])
            }
            
            const slots = ScheduleValidator.calculateAvailableSlots(schedule, 30)
            // Should have slots before and after break, but not during break
            const hasSlotAt0930 = slots.some(slot => slot.start === '09:30')
            const hasSlotAt1000 = slots.some(slot => slot.start === '10:00')
            const hasSlotAt1030 = slots.some(slot => slot.start === '10:30')
            
            return hasSlotAt0930 && !hasSlotAt1000 && hasSlotAt1030
        })
        
        // Test 3: Different appointment durations
        this.runTest(results, 'Different Appointment Durations', () => {
            const schedule = {
                start_time: '08:00',
                end_time: '10:00'
            }
            
            const slots15min = ScheduleValidator.calculateAvailableSlots(schedule, 15)
            const slots60min = ScheduleValidator.calculateAvailableSlots(schedule, 60)
            
            return slots15min.length === 8 && slots60min.length === 2
        })
    }
    
    /**
     * Test multiple schedules validation
     */
    static runMultipleSchedulesTests(results) {
        console.log('📅 Testing Multiple Schedules...')
        
        // Test 1: Valid multiple schedules
        this.runTest(results, 'Valid Multiple Schedules', () => {
            const schedules = [
                {
                    doctor_id: 1,
                    day_of_week: 'monday',
                    shift_period: 'morning',
                    start_time: '08:00',
                    end_time: '12:00',
                    number_of_patients_per_day: 16
                },
                {
                    doctor_id: 1,
                    day_of_week: 'monday',
                    shift_period: 'afternoon',
                    start_time: '14:00',
                    end_time: '18:00',
                    number_of_patients_per_day: 16
                },
                {
                    doctor_id: 1,
                    day_of_week: 'tuesday',
                    shift_period: 'morning',
                    start_time: '08:00',
                    end_time: '12:00',
                    number_of_patients_per_day: 16
                }
            ]
            
            const validation = ScheduleValidator.validateMultipleSchedules(schedules)
            return validation.isValid
        })
        
        // Test 2: Conflicting multiple schedules
        this.runTest(results, 'Conflicting Multiple Schedules', () => {
            const schedules = [
                {
                    doctor_id: 1,
                    day_of_week: 'monday',
                    shift_period: 'morning',
                    start_time: '08:00',
                    end_time: '12:00',
                    number_of_patients_per_day: 16
                },
                {
                    doctor_id: 1,
                    day_of_week: 'monday',
                    shift_period: 'morning',
                    start_time: '10:00',
                    end_time: '14:00',
                    number_of_patients_per_day: 16
                }
            ]
            
            const validation = ScheduleValidator.validateMultipleSchedules(schedules)
            return !validation.isValid && validation.errors.some(e => e.includes('conflicts'))
        })
    }
    
    /**
     * Helper method to run individual tests
     */
    static runTest(results, testName, testFunction) {
        results.total++
        
        try {
            const passed = testFunction()
            if (passed) {
                results.passed++
                console.log(`  ✅ ${testName}`)
            } else {
                results.failed++
                console.log(`  ❌ ${testName}`)
            }
            
            results.details.push({
                name: testName,
                passed: passed,
                error: null
            })
        } catch (error) {
            results.failed++
            console.log(`  ❌ ${testName} - Error: ${error.message}`)
            
            results.details.push({
                name: testName,
                passed: false,
                error: error.message
            })
        }
    }
    
    /**
     * Generate test data for different doctor scenarios
     */
    static generateTestScenarios() {
        return {
            // Scenario 1: General Practitioner - Full week schedule
            generalPractitioner: {
                doctor_id: 1,
                schedules: [
                    {
                        day_of_week: 'monday',
                        shift_period: 'morning',
                        start_time: '08:00',
                        end_time: '12:00',
                        number_of_patients_per_day: 16,
                        break_duration: 30,
                        break_times: JSON.stringify([{ start: '10:00', end: '10:30' }])
                    },
                    {
                        day_of_week: 'monday',
                        shift_period: 'afternoon',
                        start_time: '14:00',
                        end_time: '18:00',
                        number_of_patients_per_day: 16,
                        break_duration: 30,
                        break_times: JSON.stringify([{ start: '16:00', end: '16:30' }])
                    }
                ]
            },
            
            // Scenario 2: Specialist - Limited hours, higher patient load
            specialist: {
                doctor_id: 2,
                schedules: [
                    {
                        day_of_week: 'tuesday',
                        shift_period: 'morning',
                        start_time: '09:00',
                        end_time: '13:00',
                        number_of_patients_per_day: 12,
                        break_duration: 60,
                        break_times: JSON.stringify([{ start: '11:00', end: '12:00' }])
                    },
                    {
                        day_of_week: 'thursday',
                        shift_period: 'afternoon',
                        start_time: '15:00',
                        end_time: '19:00',
                        number_of_patients_per_day: 12,
                        break_duration: 30,
                        break_times: JSON.stringify([{ start: '17:00', end: '17:30' }])
                    }
                ]
            },
            
            // Scenario 3: Emergency Doctor - Irregular schedule with specific dates
            emergencyDoctor: {
                doctor_id: 3,
                schedules: [
                    {
                        date: '2024-02-15',
                        shift_period: 'morning',
                        start_time: '06:00',
                        end_time: '14:00',
                        number_of_patients_per_day: 24,
                        break_duration: 60,
                        break_times: JSON.stringify([
                            { start: '09:00', end: '09:30' },
                            { start: '12:00', end: '12:30' }
                        ])
                    }
                ]
            },
            
            // Scenario 4: Part-time Doctor - Limited availability
            partTimeDoctor: {
                doctor_id: 4,
                schedules: [
                    {
                        day_of_week: 'wednesday',
                        shift_period: 'morning',
                        start_time: '09:00',
                        end_time: '12:00',
                        number_of_patients_per_day: 9,
                        break_duration: 15,
                        break_times: JSON.stringify([{ start: '10:30', end: '10:45' }])
                    },
                    {
                        day_of_week: 'friday',
                        shift_period: 'afternoon',
                        start_time: '14:00',
                        end_time: '17:00',
                        number_of_patients_per_day: 9,
                        break_duration: 15,
                        break_times: JSON.stringify([{ start: '15:30', end: '15:45' }])
                    }
                ]
            }
        }
    }
    
    /**
     * Test real-world scenarios
     */
    static testRealWorldScenarios() {
        console.log('🌍 Testing Real-World Scenarios...\n')
        
        const scenarios = this.generateTestScenarios()
        const results = {
            passed: 0,
            failed: 0,
            total: 0,
            scenarios: []
        }
        
        Object.entries(scenarios).forEach(([scenarioName, scenario]) => {
            console.log(`Testing ${scenarioName}...`)
            results.total++
            
            const validation = ScheduleValidator.validateMultipleSchedules(scenario.schedules)
            
            if (validation.isValid) {
                results.passed++
                console.log(`  ✅ ${scenarioName} - All schedules valid`)
                
                // Test time slot generation for each schedule
                scenario.schedules.forEach((schedule, index) => {
                    const slots = ScheduleValidator.calculateAvailableSlots(schedule)
                    console.log(`    📅 Schedule ${index + 1}: ${slots.length} available slots`)
                })
            } else {
                results.failed++
                console.log(`  ❌ ${scenarioName} - Validation failed:`)
                validation.errors.forEach(error => {
                    console.log(`    - ${error}`)
                })
            }
            
            results.scenarios.push({
                name: scenarioName,
                passed: validation.isValid,
                errors: validation.errors,
                scheduleCount: scenario.schedules.length
            })
            
            console.log('')
        })
        
        console.log('📊 Real-World Scenario Summary:')
        console.log(`✅ Passed: ${results.passed}`)
        console.log(`❌ Failed: ${results.failed}`)
        console.log(`📈 Total: ${results.total}`)
        
        return results
    }
}

// Export for use in browser console or testing environment
if (typeof window !== 'undefined') {
    window.ScheduleTestScenarios = ScheduleTestScenarios
}

export default ScheduleTestScenarios