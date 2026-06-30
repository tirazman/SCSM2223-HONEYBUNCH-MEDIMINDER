const express = require('express');
const cors = require('cors');
const app = express();

// ── CORS configuration ────────────────────────────────────────────────
app.use(cors({
  origin: [
    'https://scsm-2223-honeybunch-mediminder.vercel.app',  
  ],
  methods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
  allowedHeaders: ['Content-Type', 'Authorization'],
  credentials: true
}));

app.options('*', cors());
app.use(express.json());

// Endpoint to fetch patient dashboard data along with stock thresholds
app.get('/api/patient/dashboard/:id', (req, res) => {
  const patientId = req.params.id;

  const sql = `SELECT id, drug_name AS name, dose, frequency, current_stock FROM prescription WHERE patient_id = ?`;

  db.query(sql, [patientId], (err, results) => {
    if (err) {
      console.error("Database fetch failure:", err);
      return res.status(500).json({ error: err.message });
    }

    const stockThreshold = 10;
    const lowStockAlert = results.some(med => med.current_stock < stockThreshold);

    return res.status(200).json({
      medications: results,
      showRefillReminder: lowStockAlert
    });
  });
});