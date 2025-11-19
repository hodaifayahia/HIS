<script setup>
import { computed } from 'vue';

const props = defineProps({
  currentDate: {
    type: Date,
    required: true
  },
  selectedDate: {
    type: Date,
    required: true
  },
  doctors: {
    type: Array,
    default: () => []
  },
  displayMode: {
    type: String,
    default: 'working'
  }
});

const emit = defineEmits(['select-date', 'filter-by-doctor']);

const weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

const formatLocalDate = (date) => {
  if (date === null) {
    return "";
  }
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

const getDoctorsWorkingOnDate = (date) => {
  const dateStr = formatLocalDate(date);
  const result = [];

  props.doctors.forEach(doctor => {
    // Check if doctor is working on this date
    const isWorking = doctor.working_dates?.some(workingDate => {
      return formatLocalDate(new Date(workingDate)) === dateStr;
    });

    if (isWorking) {
      result.push({
        name: doctor.name,
        color: doctor.color,
      });
    }
    
    // Check for excluded dates (holidays)
    if (doctor.excludedDates && doctor.excludedDates.length > 0) {
      const isHoliday = doctor.excludedDates.some(exclusion => {
        const startDate = new Date(exclusion.start_date);
        const endDate = exclusion.end_date ? new Date(exclusion.end_date) : startDate;
        const currentDate = new Date(dateStr);
        
        const normalizedStart = new Date(startDate.setHours(0, 0, 0, 0));
        const normalizedEnd = new Date(endDate.setHours(0, 0, 0, 0));
        const normalizedCurrent = new Date(currentDate.setHours(0, 0, 0, 0));
        
        return normalizedCurrent >= normalizedStart && 
               normalizedCurrent <= normalizedEnd && 
               exclusion.exclusionType === 'complete';
      });
      
      if (isHoliday) {
        result.push({
          name: `${doctor.name}: Holiday`,
          color: '#FF0000',
          isHoliday: true
        });
      }
    }
  });

  return result;
};

const filteredDoctors = (doctors) => {
  if (props.displayMode === 'all') {
    return doctors;
  } else if (props.displayMode === 'holidays') {
    return doctors.filter((doctor) => doctor.isHoliday);
  } else {
    return doctors.filter((doctor) => !doctor.isHoliday);
  }
};

const calendarDays = computed(() => {
  const year = props.currentDate.getFullYear();
  const month = props.currentDate.getMonth();
  const firstDay = new Date(year, month, 1);
  const lastDay = new Date(year, month + 1, 0);
  const days = [];

  // Add days from previous month
  const firstDayOfWeek = firstDay.getDay();
  for (let i = firstDayOfWeek - 1; i >= 0; i--) {
    const date = new Date(year, month, -i);
    days.push({
      date,
      isCurrentMonth: false,
      isToday: isSameDate(date, new Date()),
      doctors: getDoctorsWorkingOnDate(date),
    });
  }

  // Add days of current month
  for (let date = new Date(firstDay); date <= lastDay; date.setDate(date.getDate() + 1)) {
    days.push({
      date: new Date(date),
      isCurrentMonth: true,
      isToday: isSameDate(date, new Date()),
      doctors: getDoctorsWorkingOnDate(date),
    });
  }

  // Add days from next month
  const remainingDays = 42 - days.length;
  for (let i = 1; i <= remainingDays; i++) {
    const date = new Date(year, month + 1, i);
    days.push({
      date,
      isCurrentMonth: false,
      isToday: isSameDate(date, new Date()),
      doctors: getDoctorsWorkingOnDate(date),
    });
  }

  return days;
});

const isSameDate = (date1, date2) => {
  return date1.getDate() === date2.getDate() &&
    date1.getMonth() === date2.getMonth() &&
    date1.getFullYear() === date2.getFullYear();
};

const isSelectedDate = (date) => {
  return isSameDate(date, props.selectedDate);
};

const selectDate = (date) => {
  emit('select-date', date);
};

const filterByDoctor = (doctorName) => {
  emit('filter-by-doctor', doctorName);
};
</script>

<template>
  <div class="calendar-grid">
    <!-- Weekday Headers -->
    <div v-for="day in weekDays" :key="day" class="calendar-header">
      {{ day }}
    </div>
    <!-- Calendar Days -->
    <div v-for="{ date, isCurrentMonth, isToday, doctors } in calendarDays" :key="date.toISOString()"
      class="calendar-day" 
      :class="{
        'current-month': isCurrentMonth,
        'other-month': !isCurrentMonth,
        'today': isToday,
        'selected': isSelectedDate(date)
      }" 
      @click="selectDate(date)">
      <div class="date-number">{{ date.getDate() }}</div>
     
      <!-- Display doctors with a vertical stack based on display mode -->
      <div class="doctor-container" v-if="doctors.length > 0">
        <div v-for="doctor in filteredDoctors(doctors)" :key="doctor.name" 
          class="doctor-badge"
          :class="{ 'holiday': doctor.isHoliday }"
          :style="{ borderLeftColor: doctor.color }" 
          @click.stop="filterByDoctor(doctor.isHoliday ? '' : doctor.name.split(':')[0])">
          <span class="doctor-name">{{ doctor.name }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  background-color: #f0f2f5;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  overflow: hidden;
}

.calendar-header {
  background-color: #e9ecef;
  padding: 1rem;
  text-align: center;
  font-weight: bold;
  border-bottom: 1px solid #dee2e6;
}

.calendar-day {
  background-color: white;
  min-height: 200px;
  padding: 0.5rem;
  border-bottom: 1px solid #dee2e6;
  border-right: 1px solid #dee2e6;
  cursor: pointer;
  transition: background-color 0.2s ease;
  position: relative;
}

.calendar-day:hover {
  background-color: #f8f9fa;
}

.calendar-day.current-month {
  background-color: white;
}

.calendar-day.other-month {
  background-color: #f8f9fa;
  color: #6c757d;
}

.calendar-day.today {
  background-color: #e3f2fd;
  border: 2px solid #2196f3;
}

.calendar-day.selected {
  background-color: #bbdefb;
  border: 2px solid #1976d2;
}

.date-number {
  font-weight: bold;
  font-size: 1.1rem;
  margin-bottom: 0.5rem;
}

.doctor-container {
  display: flex;
  flex-direction: column;
  gap: 2px;
  max-height: 150px;
  overflow-y: auto;
}

.doctor-badge {
  background-color: #e3f2fd;
  color: #1565c0;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 0.7rem;
  font-weight: 500;
  border-left: 3px solid #2196f3;
  cursor: pointer;
  transition: all 0.2s ease;
  word-wrap: break-word;
  line-height: 1.2;
}

.doctor-badge:hover {
  background-color: #bbdefb;
  transform: translateX(2px);
}

.doctor-badge.holiday {
  background-color: #ffebee;
  color: #c62828;
  border-left-color: #f44336;
}

.doctor-badge.holiday:hover {
  background-color: #ffcdd2;
}

.doctor-name {
  display: block;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

@media (max-width: 768px) {
  .calendar-day {
    min-height: 120px;
    padding: 0.25rem;
  }
  
  .date-number {
    font-size: 0.9rem;
  }
  
  .doctor-badge {
    font-size: 0.6rem;
    padding: 1px 4px;
  }
}
</style>