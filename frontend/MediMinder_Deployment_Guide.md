# MediMinder Deployment Guide

## Architecture Overview

| Layer | Technology | Platform |
|---|---|---|
| Frontend | Vue 3 + Vite | Vercel |
| Backend | PHP 8.2 + Slim 4 | Render (Docker) |
| Database | MySQL | Railway |
| Mobile | Capacitor (Android) | APK / Play Store |

---

## 1. Database — Railway MySQL

### Setup
1. Go to [railway.app](https://railway.app) → New Project → MySQL
2. Under **Variables**, note these values:
   - `MYSQLHOST` (public: `reseau.proxy.rlwy.net`)
   - `MYSQLPORT` (public port, e.g. `31092`)
   - `MYSQL_DATABASE` (default: `railway`)
   - `MYSQLUSER` (`root`)
   - `MYSQL_ROOT_PASSWORD`

### Initialize Schema
Connect via terminal:
```bash
mysql -h reseau.proxy.rlwy.net -P 31092 -u root -pYOUR_PASSWORD railway
```

Run tables in this order (User must be created before tables that reference it):
```sql
CREATE TABLE User (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('patient', 'caregiver', 'admin') NOT NULL,
    dob DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS PatientCaregiver (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    caregiver_id INT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    FOREIGN KEY (caregiver_id) REFERENCES User(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES User(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Medications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    form VARCHAR(50),
    strength VARCHAR(50),
    default_unit VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Prescription (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    medication_id INT,
    drug_name VARCHAR(100) NOT NULL,
    dose VARCHAR(50) NOT NULL,
    frequency VARCHAR(50) NOT NULL,
    start_date DATE,
    end_date DATE,
    FOREIGN KEY (patient_id) REFERENCES User(id) ON DELETE CASCADE,
    FOREIGN KEY (medication_id) REFERENCES Medications(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS DoseLog (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prescription_id INT,
    scheduled_at DATETIME NOT NULL,
    taken_at DATETIME NULL,
    status ENUM('scheduled', 'taken', 'skipped') DEFAULT 'scheduled',
    FOREIGN KEY (prescription_id) REFERENCES Prescription(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Notification (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    type ENUM('reminder', 'alert', 'info') NOT NULL,
    body TEXT NOT NULL,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES User(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

Verify:
```sql
SHOW TABLES;
```

---

## 2. Backend — Render (Docker)

### Dockerfile
Place this in the **repo root**:
```dockerfile
FROM php:8.2-cli

WORKDIR /var/www

RUN apt-get update && apt-get install -y --no-install-recommends \
    unzip curl git \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pdo_mysql

RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

COPY server/ .

RUN composer install --no-dev --optimize-autoloader

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
```

### .dockerignore
Place in **repo root**:
```
server/node_modules
server/.env
frontend/node_modules
frontend/dist
.git
```

### Render Setup
1. Go to [render.com](https://render.com) → New → Web Service
2. Connect your GitHub repo
3. Set **Environment** to **Docker**
4. Leave Start Command blank (Dockerfile handles it)
5. Add these **Environment Variables**:

| Key | Value |
|---|---|
| `DB_HOST` | `reseau.proxy.rlwy.net` |
| `DB_PORT` | `31092` (your Railway public port) |
| `DB_NAME` | `railway` |
| `DB_USER` | `root` |
| `DB_PASS` | your Railway MySQL root password |
| `JWT_SECRET` | any long random string |

6. Click **Deploy**

### Verify Backend
Visit in browser:
```
https://your-render-url.onrender.com/api/health
```
Expected response:
```json
{ "status": "success", "message": "Slim 4 Backend is working perfectly!" }
```

---

## 3. Frontend — Vercel

### Environment Variable
In `src/stores/auth.js`, ensure:
```js
const API_BASE = 'https://your-render-url.onrender.com'
```

### Deploy to Vercel
1. Go to [vercel.com](https://vercel.com) → New Project
2. Import your GitHub repo
3. Set **Root Directory** to `frontend`
4. Framework: **Vite**
5. Build Command: `npm run build`
6. Output Directory: `dist`
7. Click **Deploy**

### Redeploy after backend changes
Every time you update `auth.js` or any frontend file, push to GitHub — Vercel auto-deploys on commit.

---

## 4. Mobile — Android APK (Capacitor)

### One-time Setup
```bash
cd frontend
npm install @capacitor/core @capacitor/cli @capacitor/android
npx cap init
```
When prompted:
- App name: `MediMinder`
- App ID: `com.mediminder.app`
- Web dir: `dist`

```bash
npx cap add android
```

### Build APK (every release)
```bash
# 1. Build the Vue app
npm run build

# 2. Sync to Android project
npx cap sync

# 3. Open Android Studio
npx cap open android
```

In Android Studio:
- **Debug APK**: Build → Build Bundle(s)/APK(s) → Build APK(s)
  Output: `android/app/build/outputs/apk/debug/app-debug.apk`

- **Release APK**: Build → Generate Signed Bundle / APK → APK
  (Requires keystore — see below)

### Generate Keystore (one time only)
```bash
keytool -genkey -v -keystore mediminder.keystore \
  -alias mediminder -keyalg RSA -keysize 2048 -validity 10000
```
Keep this file safe — you need it for every future release build.

### Install APK on Android Phone
1. Transfer `app-debug.apk` to your phone
2. Settings → Security → Install unknown apps → allow your file manager
3. Open the APK file and install

---

## 5. Redeployment Checklist

### Backend change (PHP)
- [ ] Push to GitHub → Render auto-redeploys
- [ ] Monitor Render logs for errors

### Frontend change (Vue)
- [ ] `npm run build` locally to verify
- [ ] Push to GitHub → Vercel auto-redeploys
- [ ] If mobile: `npm run build` → `npx cap sync` → rebuild APK in Android Studio

### Database schema change
- [ ] Connect via MySQL terminal
- [ ] Run ALTER TABLE or new CREATE TABLE statements
- [ ] Never DROP tables in production without backup

---

## 6. Common Errors & Fixes

| Error | Cause | Fix |
|---|---|---|
| `500` on login | Double `$handler->handle()` in CORS middleware | Remove the duplicate call in `index.php` |
| `404` on all routes | Wrong Render service type (Node instead of Docker) | Set Environment to Docker in Render |
| `could not find driver` | PDO MySQL extension missing | Add `RUN docker-php-ext-install pdo pdo_mysql` to Dockerfile |
| `Table doesn't exist` | Schema not initialized | Run SQL tables via Railway MySQL console |
| `Connection refused` | Using internal Railway hostname from Render | Use public host `reseau.proxy.rlwy.net` with public port |
| `404` in browser on `/api/auth/login` | Visiting via GET in browser | Normal — route only accepts POST |
