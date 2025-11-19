/**
 * Schedule Validation Utilities
 * Provides comprehensive validation for doctor schedules including conflict detection,
 * business rule validation, and time slot management.
 */

export class ScheduleValidator {
    /**
     * Validate a schedule for conflicts and business rules
     * @param {Object} schedule - The schedule to validate
     * @param {Array} existingSchedules - Array of existing schedules for the doctor
     * @param {Object} options - Validation options
     * @returns {Object} Validation result with isValid and errors
     */
    static validateSchedule(schedule, existingSchedules = [], options = {}) {
        const errors = []
        
        // Basic validation
        const basicValidation = this.validateBasicFields(schedule)
        if (!basicValidation.isValid) {
            errors.push(...basicValidation.errors)
        }
        
        // Time validation
        const timeValidation = this.validateTimes(schedule)
        if (!timeValidation.isValid) {
            errors.push(...timeValidation.errors)
        }
        
        // Conflict validation
        const conflictValidation = this.validateConflicts(schedule, existingSchedules)
        if (!conflictValidation.isValid) {
            errors.push(...conflictValidation.errors)
        }
        
        // Break validation
        const breakValidation = this.validateBreaks(schedule)
        if (!breakValidation.isValid) {
            errors.push(...breakValidation.errors)
        }
        
        // Business rules validation
        const businessValidation = this.validateBusinessRules(schedule, options)
        if (!businessValidation.isValid) {
            errors.push(...businessValidation.errors)
        }
        
        return {
            isValid: errors.length === 0,
            errors: errors
        }
    }
    
    /**
     * Validate basic required fields
     */
    static validateBasicFields(schedule) {
        const errors = []
        
        if (!schedule.doctor_id) {
            errors.push('Doctor ID is required')
        }
        
        if (!schedule.day_of_week && !schedule.date) {
            errors.push('Either day of week or specific date is required')
        }
        
        if (!schedule.shift_period) {
            errors.push('Shift period is required')
        }
        
        if (!['morning', 'afternoon'].includes(schedule.shift_period)) {
            errors.push('Shift period must be either morning or afternoon')
        }
        
        if (!schedule.start_time) {
            errors.push('Start time is required')
        }
        
        if (!schedule.end_time) {
            errors.push('End time is required')
        }
        
        if (!schedule.number_of_patients_per_day || schedule.number_of_patients_per_day < 1) {
            errors.push('Number of patients per day must be at least 1')
        }
        
        return {
            isValid: errors.length === 0,
            errors: errors
        }
    }
    
    /**
     * Validate time fields and logic
     */
    static validateTimes(schedule) {
        const errors = []
        
        // Time format validation
        const timeRegex = /^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/
        
        if (schedule.start_time && !timeRegex.test(schedule.start_time)) {
            errors.push('Start time must be in HH:MM format')
        }
        
        if (schedule.end_time && !timeRegex.test(schedule.end_time)) {
            errors.push('End time must be in HH:MM format')
        }
        
        // Time logic validation
        if (schedule.start_time && schedule.end_time) {
            const startTime = this.parseTime(schedule.start_time)
            const endTime = this.parseTime(schedule.end_time)
            
            if (endTime <= startTime) {
                errors.push('End time must be after start time')
            }
            
            // Minimum duration check (30 minutes)
            const durationMinutes = (endTime - startTime) / (1000 * 60)
            if (durationMinutes < 30) {
                errors.push('Schedule duration must be at least 30 minutes')
            }
            
            // Maximum duration check (12 hours)
            if (durationMinutes > 720) {
                errors.push('Schedule duration cannot exceed 12 hours')
            }
        }
        
        // Shift period time validation
        if (schedule.shift_period && schedule.start_time) {
            const startHour = parseInt(schedule.start_time.split(':')[0])
            
            if (schedule.shift_period === 'morning' && startHour >= 12) {
                errors.push('Morning shift should start before 12:00 PM')
            }
            
            if (schedule.shift_period === 'afternoon' && startHour < 12) {
                errors.push('Afternoon shift should start at or after 12:00 PM')
            }
        }
        
        return {
            isValid: errors.length === 0,
            errors: errors
        }
    }
    
    /**
     * Validate schedule conflicts
     */
    static validateConflicts(schedule, existingSchedules) {
        const errors = []
        
        for (const existing of existingSchedules) {
            // Skip if it's the same schedule (for updates)
            if (schedule.id && existing.id === schedule.id) {
                continue
            }
            
            // Skip inactive schedules
            if (!existing.is_active) {
                continue
            }
            
            // Check for conflicts
            const conflict = this.checkScheduleConflict(schedule, existing)
            if (conflict.hasConflict) {
                errors.push(conflict.message)
            }
        }
        
        return {
            isValid: errors.length === 0,
            errors: errors
        }
    }
    
    /**
     * Check if two schedules conflict
     */
    static checkScheduleConflict(schedule1, schedule2) {
        // Check if schedules are for the same doctor
        if (schedule1.doctor_id !== schedule2.doctor_id) {
            return { hasConflict: false }
        }
        
        // Check if schedules are for the same day/date
        const sameDay = this.isSameDay(schedule1, schedule2)
        if (!sameDay) {
            return { hasConflict: false }
        }
        
        // Check time overlap
        const timeOverlap = this.checkTimeOverlap(
            schedule1.start_time,
            schedule1.end_time,
            schedule2.start_time,
            schedule2.end_time
        )
        
        if (timeOverlap) {
            const dayInfo = schedule1.date ? 
                `on ${schedule1.date}` : 
                `on ${schedule1.day_of_week}s`
            
            return {
                hasConflict: true,
                message: `Schedule conflicts with existing ${schedule2.shift_period} shift ${dayInfo}`
            }
        }
        
        return { hasConflict: false }
    }
    
    /**
     * Validate break configuration
     */
    static validateBreaks(schedule) {
        const errors = []
        
        if (schedule.break_duration && schedule.break_duration > 0) {
            // Parse break times if provided
            let breakTimes = []
            if (schedule.break_times) {
                try {
                    breakTimes = typeof schedule.break_times === 'string' 
                        ? JSON.parse(schedule.break_times) 
                        : schedule.break_times
                } catch (e) {
                    errors.push('Invalid break times format')
                    return { isValid: false, errors }
                }
            }
            
            // Validate break times
            let totalBreakDuration = 0
            for (const breakTime of breakTimes) {
                if (!breakTime.start || !breakTime.end) {
                    errors.push('Break times must have both start and end times')
                    continue
                }
                
                const breakStart = this.parseTime(breakTime.start)
                const breakEnd = this.parseTime(breakTime.end)
                const scheduleStart = this.parseTime(schedule.start_time)
                const scheduleEnd = this.parseTime(schedule.end_time)
                
                // Check if break is within schedule time
                if (breakStart < scheduleStart || breakEnd > scheduleEnd) {
                    errors.push('Break times must be within the schedule time range')
                }
                
                // Check break duration
                const breakDuration = (breakEnd - breakStart) / (1000 * 60)
                if (breakDuration <= 0) {
                    errors.push('Break end time must be after start time')
                }
                
                totalBreakDuration += breakDuration
            }
            
            // Check if total break duration matches specified duration
            if (breakTimes.length > 0 && Math.abs(totalBreakDuration - schedule.break_duration) > 5) {
                errors.push('Total break duration does not match specified break duration')
            }
            
            // Check maximum break duration (should not exceed 50% of shift)
            const shiftDuration = (this.parseTime(schedule.end_time) - this.parseTime(schedule.start_time)) / (1000 * 60)
            if (schedule.break_duration > shiftDuration * 0.5) {
                errors.push('Break duration cannot exceed 50% of shift duration')
            }
        }
        
        return {
            isValid: errors.length === 0,
            errors: errors
        }
    }
    
    /**
     * Validate business rules
     */
    static validateBusinessRules(schedule, options = {}) {
        const errors = []
        
        // Maximum patients per hour validation
        if (options.maxPatientsPerHour) {
            const shiftDuration = (this.parseTime(schedule.end_time) - this.parseTime(schedule.start_time)) / (1000 * 60 * 60)
            const effectiveDuration = shiftDuration - ((schedule.break_duration || 0) / 60)
            const patientsPerHour = schedule.number_of_patients_per_day / effectiveDuration
            
            if (patientsPerHour > options.maxPatientsPerHour) {
                errors.push(`Patients per hour (${patientsPerHour.toFixed(1)}) exceeds maximum allowed (${options.maxPatientsPerHour})`)
            }
        }
        
        // Minimum time per patient validation
        if (options.minMinutesPerPatient) {
            const shiftDuration = (this.parseTime(schedule.end_time) - this.parseTime(schedule.start_time)) / (1000 * 60)
            const effectiveDuration = shiftDuration - (schedule.break_duration || 0)
            const minutesPerPatient = effectiveDuration / schedule.number_of_patients_per_day
            
            if (minutesPerPatient < options.minMinutesPerPatient) {
                errors.push(`Time per patient (${minutesPerPatient.toFixed(1)} min) is below minimum required (${options.minMinutesPerPatient} min)`)
            }
        }
        
        // Weekend work validation
        if (options.restrictWeekends && schedule.day_of_week) {
            const weekendDays = ['saturday', 'sunday']
            if (weekendDays.includes(schedule.day_of_week.toLowerCase())) {
                errors.push('Weekend schedules are not allowed')
            }
        }
        
        // Future date validation for specific dates
        if (schedule.date) {
            const scheduleDate = new Date(schedule.date)
            const today = new Date()
            today.setHours(0, 0, 0, 0)
            
            if (scheduleDate < today) {
                errors.push('Cannot create schedules for past dates')
            }
            
            // Maximum future date validation (e.g., 6 months)
            if (options.maxFutureMonths) {
                const maxDate = new Date()
                maxDate.setMonth(maxDate.getMonth() + options.maxFutureMonths)
                
                if (scheduleDate > maxDate) {
                    errors.push(`Cannot create schedules more than ${options.maxFutureMonths} months in advance`)
                }
            }
        }
        
        return {
            isValid: errors.length === 0,
            errors: errors
        }
    }
    
    /**
     * Helper method to parse time string to Date object
     */
    static parseTime(timeString) {
        const [hours, minutes] = timeString.split(':').map(Number)
        const date = new Date()
        date.setHours(hours, minutes, 0, 0)
        return date
    }
    
    /**
     * Check if two schedules are for the same day
     */
    static isSameDay(schedule1, schedule2) {
        // If both have specific dates
        if (schedule1.date && schedule2.date) {
            return schedule1.date === schedule2.date
        }
        
        // If both have day of week
        if (schedule1.day_of_week && schedule2.day_of_week) {
            return schedule1.day_of_week.toLowerCase() === schedule2.day_of_week.toLowerCase()
        }
        
        // If one has date and other has day of week
        if (schedule1.date && schedule2.day_of_week) {
            const date = new Date(schedule1.date)
            const dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']
            const dayOfWeek = dayNames[date.getDay()]
            return dayOfWeek === schedule2.day_of_week.toLowerCase()
        }
        
        if (schedule2.date && schedule1.day_of_week) {
            const date = new Date(schedule2.date)
            const dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']
            const dayOfWeek = dayNames[date.getDay()]
            return dayOfWeek === schedule1.day_of_week.toLowerCase()
        }
        
        return false
    }
    
    /**
     * Check if two time ranges overlap
     */
    static checkTimeOverlap(start1, end1, start2, end2) {
        const s1 = this.parseTime(start1)
        const e1 = this.parseTime(end1)
        const s2 = this.parseTime(start2)
        const e2 = this.parseTime(end2)
        
        return s1 < e2 && s2 < e1
    }
    
    /**
     * Calculate available time slots for a schedule
     */
    static calculateAvailableSlots(schedule, appointmentDuration = 30) {
        const slots = []
        
        if (!schedule.start_time || !schedule.end_time) {
            return slots
        }
        
        const startTime = this.parseTime(schedule.start_time)
        const endTime = this.parseTime(schedule.end_time)
        
        // Parse break times
        let breakTimes = []
        if (schedule.break_times) {
            try {
                breakTimes = typeof schedule.break_times === 'string' 
                    ? JSON.parse(schedule.break_times) 
                    : schedule.break_times
            } catch (e) {
                breakTimes = []
            }
        }
        
        let currentTime = new Date(startTime)
        
        while (currentTime < endTime) {
            const slotEnd = new Date(currentTime.getTime() + appointmentDuration * 60000)
            
            // Check if slot overlaps with any break
            const overlapsBreak = breakTimes.some(breakTime => {
                const breakStart = this.parseTime(breakTime.start)
                const breakEnd = this.parseTime(breakTime.end)
                return this.checkTimeOverlap(
                    this.formatTime(currentTime),
                    this.formatTime(slotEnd),
                    this.formatTime(breakStart),
                    this.formatTime(breakEnd)
                )
            })
            
            if (!overlapsBreak && slotEnd <= endTime) {
                slots.push({
                    start: this.formatTime(currentTime),
                    end: this.formatTime(slotEnd)
                })
            }
            
            currentTime = new Date(currentTime.getTime() + appointmentDuration * 60000)
        }
        
        return slots
    }
    
    /**
     * Format time object to HH:MM string
     */
    static formatTime(date) {
        return date.toTimeString().slice(0, 5)
    }
    
    /**
     * Validate multiple schedules for a doctor
     */
    static validateMultipleSchedules(schedules, options = {}) {
        const results = []
        
        for (let i = 0; i < schedules.length; i++) {
            const schedule = schedules[i]
            const otherSchedules = schedules.filter((_, index) => index !== i)
            
            const validation = this.validateSchedule(schedule, otherSchedules, options)
            results.push({
                index: i,
                schedule: schedule,
                ...validation
            })
        }
        
        return {
            isValid: results.every(r => r.isValid),
            results: results,
            errors: results.filter(r => !r.isValid).flatMap(r => r.errors)
        }
    }
}

export default ScheduleValidator