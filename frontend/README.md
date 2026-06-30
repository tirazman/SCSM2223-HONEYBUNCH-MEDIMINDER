# 💊 MediMinder Patient & Clinic Portal

MediMinder is a web-based clinical management and patient adherence tracking application. The system provides a seamless data pipeline connecting a healthcare clinic administration portal with an interactive, real-time client dashboard for patients to manage their daily prescriptions and track adherence metrics.

---

## 🚀 Key Features

### 👨‍⚕️ Clinic Admin Portal
* **Prescription Registry Management:** Create, validate, and commit medication records directly to the centralized data layer.
* **Clinical Safety Layer:** Automated drug-to-drug interaction engine that triggers warnings when conflicting treatments are prescribed.
* **Database Synchronization:** Secure transmission pipelines built to feed structural tables cleanly without relying on mock data configurations.

### 🧑‍⚕️ Patient Adherence Dashboard
* **Dynamic Daily Schedule:** Displays active medications tailored specifically to the logged-in patient profile.
* **Interactive Adherence Logging:** Smart interface nodes enabling patients to easily register daily doses as **Mark Taken** or **Skip**.
* **Inventory Stock Monitoring:** Real-time counter system that decreases item counts upon consumption tracking.
* **Refill Safety Reminders:** Automated warning banners that dynamically trigger if medication inventory falls below a safe baseline threshold (10 units remaining).

---

## 🛠️ Technology Stack

| Component | Framework / Technology | Purpose |
| :--- | :--- | :--- |
| **Frontend** | Vue 3, Vite, Pinia | High-performance reactive user interface modules |
| **Backend API** | PHP (Custom Repository Pattern) | Cross-origin data transfer pipeline & controller layer |
| **Database** | MySQL (Laragon Environment) | Persistent clinical transaction logging engine |
| **Database GUI** | HeidiSQL | Structural management, schema queries, and data table analysis |

---

## 📦 Directory Architecture

```text
SCSM2223-HONEYBUNCH-MEDIMINDER/
├── frontend/                               # Client UI Application Layer (Vue 3 + Vite)
│   ├── src/
│   │   ├── api/                            # API Communications Client
│   │   │   └── client.js
│   │   ├── components/                     # Reusable UI Elements
│   │   │   ├── MedicationCard.vue
│   │   │   ├── MedicationTable.vue
│   │   │   ├── NavBar.vue
│   │   │   └── NotificationBox.vue
│   │   ├── router/                         # Vue Router Configurations
│   │   │   └── index.js
│   │   ├── stores/                         # Pinia Global State Management
│   │   │   └── auth.js
│   │   ├── views/                          # Dashboard Page Views
│   │   │   ├── AdherenceDashboard.vue
│   │   │   ├── Admin.vue
│   │   │   ├── Caregiver.vue
│   │   │   ├── Login.vue
│   │   │   ├── Patient.vue
│   │   │   ├── Profile.vue
│   │   │   ├── ProfileDashboard.vue
│   │   │   └── Register.vue
│   │   ├── App.vue                         # Main Root Component
│   │   ├── main.js                         # Application Entry Point
│   │   └── style.css                       # Global Stylesheets
│   ├── .gitignore
│   ├── index.html
│   ├── jsconfig.json
│   ├── package-lock.json
│   ├── package.json
│   └── vite.config.js
├── server/                                 # Core Backend Architecture Layer (PHP App)
│   ├── public/                             # Publicly Accessible Virtual Server Assets
│   │   └── index.php
│   ├── src/                                # Core Application Architecture
│   │   ├── Auth/                           # Token Infrastructure & Utilities
│   │   │   └── JWTService.php
│   │   ├── Controller/                     # Request/Route Action Logic Handlers
│   │   │   ├── AuthController.php
│   │   │   ├── DoseLogController.php
│   │   │   ├── MedicationController.php
│   │   │   ├── NotifController.php
│   │   │   ├── PatientCaregiverController.php
│   │   │   └── PrescriptionController.php
│   │   ├── Data/                           # Database Connection Handlers
│   │   │   └── Database.php
│   │   ├── Middleware/                     # Route Request Filtering & Verification
│   │   │   └── JWTMiddleware.php
│   │   ├── Repositories/                   # Direct SQL Query Abstraction Layers
│   │   │   ├── DoseLogRepository.php
│   │   │   ├── MedicationRepository.php
│   │   │   ├── NotifRepository.php
│   │   │   ├── PatientCaregiverRepository.php
│   │   │   ├── PrescriptionRepository.php
│   │   │   └── UserRepository.php
│   │   ├── store/                          # Mock Data Storage Assets
│   │   │   └── prescriptionStories.js
│   │   ├── Validation/                     # Inbound Data Integrity Assertions
│   │   │   └── Validator.php
│   │   └── index.js                        # App Application Orchestration Core
│   └── composer.json                       # PHP Dependencies & Autoload Mapping
└── sql/                                    # Persistent Data Engine Schemas (Laragon / MySQL)
    ├── schema.sql                          # Database Tables & Structural Definitions
    └── user.sql                            # Initial Seed & System Users Configuration