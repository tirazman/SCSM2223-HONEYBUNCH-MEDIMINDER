const express = require('express');
const cors = require('cors');
const app = express();

// ── CORS configuration ────────────────────────────────────────────────
app.use(cors({
  origin: (origin, callback) => {
    console.log('[CORS] Request from:', origin)

    const allowed =
      !origin ||                                    // Postman / mobile / server
      origin.endsWith('.vercel.app') ||             // all Vercel preview + production URLs
      origin === 'http://localhost:5173'            // local dev

    if (allowed) {
      callback(null, true)
    } else {
      console.warn('[CORS] Blocked:', origin)
      callback(new Error(`CORS blocked: ${origin}`))
    }
  },
  methods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
  allowedHeaders: ['Content-Type', 'Authorization'],
  credentials: true
}));

app.options('*', cors());
app.use(express.json());

// ── Your existing routes unchanged ───────────────────────────────────
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