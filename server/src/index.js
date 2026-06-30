const express = require('express');
const cors    = require('cors');
const mysql   = require('mysql2');
const jwt     = require('jsonwebtoken');
const bcrypt  = require('bcryptjs');

const app  = express();
const PORT = process.env.PORT || 8000;

// ─── 1. CORS ──────────────────────────────────────────────────────────────────
const corsOptions = {
  origin: (origin, callback) => {
    console.log('[CORS] Request from:', origin);

    const allowed =
      !origin ||
      origin.endsWith('.vercel.app') ||
      origin === 'http://localhost:5173';

    if (allowed) {
      callback(null, true);
    } else {
      console.warn('[CORS] Blocked:', origin);
      callback(new Error(`CORS blocked: ${origin}`));
    }
  },
  methods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
  allowedHeaders: ['Content-Type', 'Authorization'],
  credentials: true,
};

app.use(cors(corsOptions));
app.options('*', cors(corsOptions)); // ✅ same config used for preflight
app.use(express.json());

// ─── 2. DATABASE ──────────────────────────────────────────────────────────────
// All values come from environment variables set in the Render dashboard.
// Never hardcode credentials.
const db = mysql.createPool({
  host:     process.env.DB_HOST,
  port:     process.env.DB_PORT     || 3306,
  user:     process.env.DB_USER,
  password: process.env.DB_PASS,
  database: process.env.DB_NAME,
  waitForConnections: true,
  connectionLimit:    10,
});

// Quick connectivity check on startup
db.getConnection((err, connection) => {
  if (err) {
    console.error('[DB] Connection failed:', err.message);
  } else {
    console.log('[DB] Connected to MySQL successfully.');
    connection.release();
  }
});

// ─── 3. JWT HELPER ────────────────────────────────────────────────────────────
const JWT_SECRET = process.env.JWT_SECRET || 'change-this-secret-in-production';

function signToken(payload) {
  return jwt.sign(payload, JWT_SECRET, { expiresIn: '7d' });
}

// Middleware: extract and verify the Bearer token, then attach user_id to req
function requireAuth(req, res, next) {
  const authHeader = req.headers.authorization || '';
  const token      = authHeader.startsWith('Bearer ') ? authHeader.slice(7) : null;

  if (!token) {
    return res.status(401).json({ error: 'No token provided.' });
  }

  try {
    const decoded = jwt.verify(token, JWT_SECRET);
    req.user_id   = decoded.id;
    req.user_role = decoded.role;
    next();
  } catch {
    return res.status(401).json({ error: 'Invalid or expired token.' });
  }
}

// ─── 4. AUTH ROUTES ───────────────────────────────────────────────────────────

// POST /auth/register
app.post('/auth/register', async (req, res) => {
  const { name, email, password, role, dob } = req.body;

  if (!name || !email || !password || !role) {
    return res.status(400).json({ error: 'Name, email, password and role are required.' });
  }

  try {
    // Check for duplicate email
    const [existing] = await db.promise().query(
      'SELECT id FROM User WHERE email = ? LIMIT 1',
      [email]
    );
    if (existing.length > 0) {
      return res.status(409).json({ error: 'An account with that email already exists.' });
    }

    const passwordHash = await bcrypt.hash(password, 12);

    const [result] = await db.promise().query(
      'INSERT INTO User (name, email, password_hash, role, dob) VALUES (?, ?, ?, ?, ?)',
      [name, email, passwordHash, role, dob || null]
    );

    const userId = result.insertId;
    const token  = signToken({ id: userId, role });

    return res.status(201).json({
      token,
      user: { id: userId, name, email, role, dob: dob || null },
    });
  } catch (err) {
    console.error('[/auth/register]', err.message);
    return res.status(500).json({ error: 'Registration failed. Please try again.' });
  }
});

// POST /auth/login
app.post('/auth/login', async (req, res) => {
  const { email, password, role } = req.body;

  if (!email || !password) {
    return res.status(400).json({ error: 'Email and password are required.' });
  }

  try {
    const [rows] = await db.promise().query(
      'SELECT * FROM User WHERE email = ? LIMIT 1',
      [email]
    );

    if (rows.length === 0) {
      return res.status(401).json({ error: 'Invalid email or password.' });
    }

    const user = rows[0];

    // Optionally enforce role check (e.g. prevent a Patient logging in as Admin)
    if (role && user.role.toLowerCase() !== role.toLowerCase()) {
      return res.status(403).json({ error: `This account is not registered as ${role}.` });
    }

    const passwordMatch = await bcrypt.compare(password, user.password_hash);
    if (!passwordMatch) {
      return res.status(401).json({ error: 'Invalid email or password.' });
    }

    const token = signToken({ id: user.id, role: user.role });

    return res.status(200).json({
      token,
      user: {
        id:    user.id,
        name:  user.name,
        email: user.email,
        role:  user.role,
        dob:   user.dob,
      },
    });
  } catch (err) {
    console.error('[/auth/login]', err.message);
    return res.status(500).json({ error: 'Login failed. Please try again.' });
  }
});

// POST /api/patient-caregiver  (link a caregiver by email)
app.post('/api/patient-caregiver', requireAuth, async (req, res) => {
  const { caregiver_email } = req.body;
  const patientId = req.user_id;

  if (!caregiver_email) {
    return res.status(400).json({ error: 'caregiver_email is required.' });
  }

  try {
    const [caregivers] = await db.promise().query(
      "SELECT id FROM User WHERE email = ? AND role = 'caregiver' LIMIT 1",
      [caregiver_email]
    );

    if (caregivers.length === 0) {
      return res.status(404).json({ error: 'No caregiver account found with that email.' });
    }

    const caregiverId = caregivers[0].id;

    await db.promise().query(
      'INSERT IGNORE INTO PatientCaregiver (patient_id, caregiver_id) VALUES (?, ?)',
      [patientId, caregiverId]
    );

    return res.status(200).json({ success: true });
  } catch (err) {
    console.error('[/api/patient-caregiver]', err.message);
    return res.status(500).json({ error: 'Could not link caregiver.' });
  }
});

// ─── 5. PROTECTED ROUTES ──────────────────────────────────────────────────────

// GET /api/patient/dashboard/:id
app.get('/api/patient/dashboard/:id', requireAuth, (req, res) => {
  const patientId = req.params.id;

  const sql = `
    SELECT id, drug_name AS name, dose, frequency, current_stock
    FROM Prescription
    WHERE patient_id = ?
  `;

  db.query(sql, [patientId], (err, results) => {
    if (err) {
      console.error('[/api/patient/dashboard]', err.message);
      return res.status(500).json({ error: 'Failed to fetch dashboard data.' });
    }

    const stockThreshold = 10;
    const showRefillReminder = results.some(med => med.current_stock < stockThreshold);

    return res.status(200).json({ medications: results, showRefillReminder });
  });
});

// GET /api/notifications/due
app.get('/api/notifications/due', requireAuth, async (req, res) => {
  const userId = req.user_id;

  try {
    const [rows] = await db.promise().query(
      `SELECT dl.id,
              dl.prescription_id,
              dl.scheduled_at  AS scheduled_time,
              dl.status,
              p.drug_name,
              p.dose,
              p.frequency
       FROM DoseLog dl
       JOIN Prescription p ON p.id = dl.prescription_id
       WHERE p.patient_id  = ?
         AND dl.status     = 'scheduled'
         AND dl.scheduled_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 HOUR)
       ORDER BY dl.scheduled_at ASC`,
      [userId]
    );

    return res.status(200).json(rows);
  } catch (err) {
    console.error('[/api/notifications/due]', err.message);
    return res.status(500).json({ error: 'Failed to fetch notifications.' });
  }
});

// ─── 6. HEALTH CHECK ──────────────────────────────────────────────────────────
// Render pings this to confirm the service is alive.
app.get('/health', (_, res) => res.json({ status: 'ok' }));

// ─── 7. START ─────────────────────────────────────────────────────────────────
app.listen(PORT, () => {
  console.log(`[Server] Running on port ${PORT}`);
});