// 🌐 Endpoint to fetch patient dashboard data along with stock thresholds
app.get('/api/patient/dashboard/:id', (req, res) => {
  const patientId = req.params.id;

  // Query to select active prescriptions
  // Assumes you add a 'current_stock' or calculate it from your prescription table
  const sql = `SELECT id, drug_name AS name, dose, frequency, current_stock FROM prescription WHERE patient_id = ?`;

  db.query(sql, [patientId], (err, results) => {
    if (err) {
      console.error("Database fetch failure:", err);
      return res.status(500).json({ error: err.message });
    }

    // Set your stock threshold rule (e.g., alert if stock falls below 10)
    const stockThreshold = 10;
    
    // Check if any medication in the list falls below the safety threshold
    const lowStockAlert = results.some(med => med.current_stock < stockThreshold);

    // Send both the medication list and the alert status to the frontend
    return res.status(200).json({
      medications: results,
      showRefillReminder: lowStockAlert
    });
  });
});