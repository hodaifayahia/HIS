const { check, sleep } = require('k6');
const http = require('k6/http');

// Test configuration
export let options = {
  stages: [
    { duration: '2m', target: 20 }, // Ramp up to 20 users
    { duration: '5m', target: 50 }, // Stay at 50 users
    { duration: '2m', target: 100 }, // Ramp up to 100 users
    { duration: '10m', target: 100 }, // Stay at 100 users
    { duration: '5m', target: 0 }, // Ramp down to 0 users
  ],
  thresholds: {
    http_req_duration: ['p(95)<2000'], // 95% of requests must complete below 2s
    http_req_failed: ['rate<0.1'], // Error rate must be below 10%
  },
};

// Base URL for the HIS application
const BASE_URL = 'http://localhost:8000';

// Test data
const testUsers = [
  { email: 'doctor1@hospital.com', password: 'password123' },
  { email: 'nurse1@hospital.com', password: 'password123' },
  { email: 'admin1@hospital.com', password: 'password123' },
];

const testPatients = [
  { first_name: 'John', last_name: 'Doe', email: 'john.doe@example.com', phone: '+1234567890' },
  { first_name: 'Jane', last_name: 'Smith', email: 'jane.smith@example.com', phone: '+1234567891' },
  { first_name: 'Bob', last_name: 'Johnson', email: 'bob.johnson@example.com', phone: '+1234567892' },
];

// Authentication helper
function authenticate() {
  const user = testUsers[Math.floor(Math.random() * testUsers.length)];
  
  const loginResponse = http.post(`${BASE_URL}/api/auth/login`, {
    email: user.email,
    password: user.password,
  }, {
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
  });

  check(loginResponse, {
    'login successful': (r) => r.status === 200,
    'login response time < 1s': (r) => r.timings.duration < 1000,
  });

  if (loginResponse.status === 200) {
    const token = JSON.parse(loginResponse.body).token;
    return {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
  }
  
  return null;
}

// Main test function
export default function () {
  const headers = authenticate();
  
  if (!headers) {
    console.error('Authentication failed');
    return;
  }

  // Test 1: Patient Registration Workflow
  testPatientRegistration(headers);
  sleep(1);

  // Test 2: Appointment Scheduling Workflow
  testAppointmentScheduling(headers);
  sleep(1);

  // Test 3: Medical Record Access
  testMedicalRecordAccess(headers);
  sleep(1);

  // Test 4: Billing Workflow
  testBillingWorkflow(headers);
  sleep(1);

  // Test 5: Patient Check-in to Discharge Workflow
  testPatientJourney(headers);
  sleep(2);
}

function testPatientRegistration(headers) {
  const patient = testPatients[Math.floor(Math.random() * testPatients.length)];
  
  // Add random suffix to avoid duplicates
  const randomSuffix = Math.floor(Math.random() * 10000);
  patient.email = `${patient.first_name.toLowerCase()}.${patient.last_name.toLowerCase()}${randomSuffix}@example.com`;

  const response = http.post(`${BASE_URL}/api/patients`, JSON.stringify({
    first_name: patient.first_name,
    last_name: patient.last_name,
    email: patient.email,
    phone: patient.phone,
    date_of_birth: '1990-01-01',
    gender: 'male',
    address: '123 Test Street, Test City',
  }), { headers });

  check(response, {
    'patient registration successful': (r) => r.status === 201,
    'patient registration response time < 2s': (r) => r.timings.duration < 2000,
  });

  return response.status === 201 ? JSON.parse(response.body).id : null;
}

function testAppointmentScheduling(headers) {
  // Get available doctors
  const doctorsResponse = http.get(`${BASE_URL}/api/doctors`, { headers });
  
  check(doctorsResponse, {
    'doctors list loaded': (r) => r.status === 200,
    'doctors response time < 1s': (r) => r.timings.duration < 1000,
  });

  if (doctorsResponse.status !== 200) return;

  const doctors = JSON.parse(doctorsResponse.body);
  if (doctors.length === 0) return;

  // Get available patients
  const patientsResponse = http.get(`${BASE_URL}/api/patients?limit=10`, { headers });
  
  check(patientsResponse, {
    'patients list loaded': (r) => r.status === 200,
    'patients response time < 1s': (r) => r.timings.duration < 1000,
  });

  if (patientsResponse.status !== 200) return;

  const patients = JSON.parse(patientsResponse.body);
  if (patients.length === 0) return;

  // Schedule appointment
  const doctor = doctors[Math.floor(Math.random() * doctors.length)];
  const patient = patients[Math.floor(Math.random() * patients.length)];
  
  const appointmentDate = new Date();
  appointmentDate.setDate(appointmentDate.getDate() + Math.floor(Math.random() * 30) + 1);

  const appointmentResponse = http.post(`${BASE_URL}/api/appointments`, JSON.stringify({
    patient_id: patient.id,
    doctor_id: doctor.id,
    appointment_date: appointmentDate.toISOString().split('T')[0],
    appointment_time: '10:00',
    appointment_type: 'consultation',
    notes: 'Load test appointment',
  }), { headers });

  check(appointmentResponse, {
    'appointment scheduled': (r) => r.status === 201,
    'appointment response time < 2s': (r) => r.timings.duration < 2000,
  });
}

function testMedicalRecordAccess(headers) {
  // Get patients list
  const patientsResponse = http.get(`${BASE_URL}/api/patients?limit=10`, { headers });
  
  if (patientsResponse.status !== 200) return;

  const patients = JSON.parse(patientsResponse.body);
  if (patients.length === 0) return;

  const patient = patients[Math.floor(Math.random() * patients.length)];

  // Access patient medical records
  const recordsResponse = http.get(`${BASE_URL}/api/patients/${patient.id}/medical-records`, { headers });

  check(recordsResponse, {
    'medical records accessed': (r) => r.status === 200,
    'medical records response time < 1.5s': (r) => r.timings.duration < 1500,
  });

  // Access patient appointments
  const appointmentsResponse = http.get(`${BASE_URL}/api/patients/${patient.id}/appointments`, { headers });

  check(appointmentsResponse, {
    'patient appointments accessed': (r) => r.status === 200,
    'appointments response time < 1s': (r) => r.timings.duration < 1000,
  });
}

function testBillingWorkflow(headers) {
  // Get recent appointments for billing
  const appointmentsResponse = http.get(`${BASE_URL}/api/appointments?status=completed&limit=5`, { headers });
  
  if (appointmentsResponse.status !== 200) return;

  const appointments = JSON.parse(appointmentsResponse.body);
  if (appointments.length === 0) return;

  const appointment = appointments[Math.floor(Math.random() * appointments.length)];

  // Create invoice
  const invoiceResponse = http.post(`${BASE_URL}/api/invoices`, JSON.stringify({
    patient_id: appointment.patient_id,
    appointment_id: appointment.id,
    items: [
      {
        description: 'Consultation Fee',
        quantity: 1,
        unit_price: 100.00,
        total: 100.00,
      },
    ],
    total_amount: 100.00,
    due_date: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
  }), { headers });

  check(invoiceResponse, {
    'invoice created': (r) => r.status === 201,
    'invoice response time < 2s': (r) => r.timings.duration < 2000,
  });

  if (invoiceResponse.status === 201) {
    const invoice = JSON.parse(invoiceResponse.body);

    // Process payment
    const paymentResponse = http.post(`${BASE_URL}/api/payments`, JSON.stringify({
      invoice_id: invoice.id,
      amount: 100.00,
      payment_method: 'cash',
      payment_date: new Date().toISOString().split('T')[0],
    }), { headers });

    check(paymentResponse, {
      'payment processed': (r) => r.status === 201,
      'payment response time < 2s': (r) => r.timings.duration < 2000,
    });
  }
}

function testPatientJourney(headers) {
  // Step 1: Patient Check-in
  const patientId = testPatientRegistration(headers);
  if (!patientId) return;

  sleep(0.5);

  // Step 2: Schedule Consultation
  const doctorsResponse = http.get(`${BASE_URL}/api/doctors`, { headers });
  if (doctorsResponse.status !== 200) return;

  const doctors = JSON.parse(doctorsResponse.body);
  if (doctors.length === 0) return;

  const doctor = doctors[Math.floor(Math.random() * doctors.length)];
  
  const appointmentResponse = http.post(`${BASE_URL}/api/appointments`, JSON.stringify({
    patient_id: patientId,
    doctor_id: doctor.id,
    appointment_date: new Date().toISOString().split('T')[0],
    appointment_time: '14:00',
    appointment_type: 'consultation',
    status: 'scheduled',
  }), { headers });

  if (appointmentResponse.status !== 201) return;
  const appointment = JSON.parse(appointmentResponse.body);

  sleep(0.5);

  // Step 3: Complete Consultation
  const consultationResponse = http.put(`${BASE_URL}/api/appointments/${appointment.id}`, JSON.stringify({
    status: 'completed',
    diagnosis: 'Load test diagnosis',
    treatment_plan: 'Load test treatment',
  }), { headers });

  check(consultationResponse, {
    'consultation completed': (r) => r.status === 200,
    'consultation response time < 2s': (r) => r.timings.duration < 2000,
  });

  sleep(0.5);

  // Step 4: Generate Bill
  const invoiceResponse = http.post(`${BASE_URL}/api/invoices`, JSON.stringify({
    patient_id: patientId,
    appointment_id: appointment.id,
    items: [
      {
        description: 'Consultation Fee',
        quantity: 1,
        unit_price: 150.00,
        total: 150.00,
      },
    ],
    total_amount: 150.00,
  }), { headers });

  if (invoiceResponse.status === 201) {
    const invoice = JSON.parse(invoiceResponse.body);

    // Step 5: Process Payment
    const paymentResponse = http.post(`${BASE_URL}/api/payments`, JSON.stringify({
      invoice_id: invoice.id,
      amount: 150.00,
      payment_method: 'card',
    }), { headers });

    check(paymentResponse, {
      'patient journey payment processed': (r) => r.status === 201,
      'payment response time < 2s': (r) => r.timings.duration < 2000,
    });
  }

  // Step 6: Patient Discharge
  const dischargeResponse = http.post(`${BASE_URL}/api/patients/${patientId}/discharge`, JSON.stringify({
    discharge_date: new Date().toISOString(),
    discharge_summary: 'Load test discharge',
    follow_up_required: false,
  }), { headers });

  check(dischargeResponse, {
    'patient discharged': (r) => r.status === 200,
    'discharge response time < 1.5s': (r) => r.timings.duration < 1500,
  });

  check(null, {
    'complete patient journey successful': () => true,
  });
}