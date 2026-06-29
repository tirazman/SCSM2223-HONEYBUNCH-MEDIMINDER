// src/api/client.js

// 1. Core Fetcher: Grabs the raw JSON from the public folder
async function fetchDatabase() {
  try {
    const response = await fetch('/mock-data.json');
    if (!response.ok) throw new Error('Network response was not ok');
    
    // Optional: Add a tiny artificial delay to simulate real internet speed
    // This looks great during the live presentation!
    await new Promise(resolve => setTimeout(resolve, 400)); 
    
    return await response.json();
  } catch (error) {
    console.error('Database Fetch Failed:', error);
    return null;
  }
}

// 2. Exported Service: These are the specific hooks your team will import
export const ApiService = {
  
  // Hook for Member 2: Patient Dashboard
  async getPatientDashboardData(patientId) {
    const db = await fetchDatabase();
    if (!db) return [];

    // Filter schedules just for this specific patient
    const mySchedules = db.schedules.filter(s => s.patient_id === patientId);

    // Map through schedules to simulate a SQL "JOIN" with Medications and Logs
    return mySchedules.map(schedule => {
      const med = db.medications.find(m => m.id === schedule.medication_id);
      const log = db.adherence_logs.find(l => l.schedule_id === schedule.id);

      // We format it exactly how Patient.vue expects it
      return {
        id: log.id, // We pass the log ID so we can update it when they click "Mark Taken"
        name: med.name,
        form: med.form,
        dose: `${med.strength} (${schedule.dose} ${med.default_unit})`,
        time: schedule.time,
        notes: schedule.notes,
        status: log.status === 'Pending' ? null : log.status.toLowerCase()
      };
    });
  },

  // Hook for Member 3: Clinic Admin Console
  async getAdminDirectory() {
    const db = await fetchDatabase();
    if (!db) return [];

    // The Admin just needs a list of all patients and their compliance rates
    return db.users.filter(user => user.role === 'patient');
  },

  // Hook for Member 1/2: Caregiver Hub
  async getCaregiverDashboardData() {
    const db = await fetchDatabase();
    if (!db) return [];

    // 1. Grab all users who are patients
    const patients = db.users.filter(u => u.role === 'patient');

    // 2. Loop through each patient and attach their specific medications
    return patients.map(patient => {
      // Find this specific patient's schedules
      const theirSchedules = db.schedules.filter(s => s.patient_id === patient.id);

      // Build their medication tracker for today
      const todaysMeds = theirSchedules.map(schedule => {
        const med = db.medications.find(m => m.id === schedule.medication_id);
        const log = db.adherence_logs.find(l => l.schedule_id === schedule.id);

        return {
          name: `${med.name} ${med.strength}`,
          doseTime: schedule.time,
          checkIn: log.check_in || '--', // Use '--' if they haven't checked in
          status: log.status,
          // Assign the badge color based on the DB status
          badgeClass: log.status === 'Taken' ? 'bg-success' : (log.status === 'Pending' ? 'bg-pending' : 'bg-danger')
        };
      });

      // 3. Logic check: If any medication is missed, trigger the critical alert string
      const missedMed = todaysMeds.find(m => m.status === 'Missed');
      const alertMsg = missedMed 
        ? `Critical Alert: Missed ${missedMed.name} dose scheduled for ${missedMed.doseTime} today!` 
        : null;

      // 4. Return the fully assembled object exactly how the Vue template wants it
      return {
        id: patient.id,
        name: patient.name,
        dob: patient.dob,
        email: patient.email,
        rate: `${patient.compliance_rate}% Adherent`,
        rateClass: patient.compliance_rate >= 80 ? 'bg-success' : 'bg-danger',
        borderColor: patient.compliance_rate >= 80 ? 'var(--primary-honey)' : 'var(--status-danger)',
        alert: alertMsg,
        meds: todaysMeds
      };
    });
  }

};