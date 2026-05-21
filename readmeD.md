```markdown
# Aura Facial Recognition Attendance Suite  
## Comprehensive Documentation | مکمل دستاویز  

![Version](https://img.shields.io/badge/version-3.0-blue) ![License](https://img.shields.io/badge/license-Proprietary-red) ![Face API](https://img.shields.io/badge/Face--API.js-1.7.12-green)

---

## English | انگریزی

### Overview  
Aura is a **browser‑based facial recognition attendance system** that uses state‑of‑the‑art face detection, landmark extraction, and face matching to automate employee check‑in/out. It runs entirely in the browser (no backend server required), storing all data in **IndexedDB**. The application includes three main panels: **Kiosk Scanner** for daily attendance, **Security Monitor** for motion‑based video recording, and **Admin Terminal** for full control over employees, policies, reports, and system configuration.

### Key Features  
- **Real‑time face recognition** with adjustable anti‑spoofing (blink / head‑turn challenge).  
- **Biometric enrollment** via live webcam or photo upload.  
- **Automatic check‑in/out** logic based on shift start, grace period, and logout cutoff.  
- **Manual attendance override** for retroactive logs or exceptions.  
- **Employee directory** with search, suspend/activate, edit, and delete.  
- **Leave & permission manager** with date‑range overlap validation.  
- **Shift policies** per department and per employee (start time, grace minutes).  
- **Global attendance policies** – weekend days, holidays, minimum session length, auto‑checkout gap, logout cutoff rule.  
- **Advanced analytics dashboard** – daily KPI, weekly bar chart, department donut chart, 28‑day heatmap.  
- **Comprehensive reporting hub** – filterable logs, KPI widgets, multiple chart types (line, bar, doughnut, pie, radar, scatter, bubble), Chart Lab, infographic insights, DataTable with full export (Excel, PDF, CSV, Copy, Print).  
- **Audit trail** – every important action is logged.  
- **Backup / Restore** of complete database (employees, logs, departments, holidays, leave requests, shift policies, audit logs) as JSON.  
- **Security Monitor** – motion detection with adjustable sensitivity, automatic or manual video recording, downloadable clips.  
- **No‑sleep (wake lock) mode** to keep camera active during long sessions.  
- **Dark mode & accent colors** (indigo, emerald, rose, amber, blue).  
- **Audio feedback** (beeps + speech synthesis) for successful scans and warnings.  

---

## Installation & Setup | انسٹالیشن اور سیٹ اپ  

1. **Host the files** – Place `index.html` on any web server (or run locally).  
2. **Internet connection** – Face‑API models (~5 MB) are loaded from CDN on first run. After that, the app can be used offline if the models are cached.  
3. **Open the app** – Use **Chrome**, **Edge**, or **Firefox** (camera access required).  
4. **Grant camera permission** when prompted.  
5. **Default admin password** – `admin1234` (change in *Admin Terminal → Controls & Policies*).  

> **Note:** All data is stored in your browser’s IndexedDB. Clearing browser data will erase all employees and logs.

---

## Module 1 – Kiosk Scanner | کیوسک اسکینر  

This is the main interface for employees to mark attendance.

### Interface elements  
- **Video preview** – mirrored live feed.  
- **Start / Pause / Stop** camera buttons.  
- **Live ticker** – shows the last 20 check‑in/out events with timestamps.  
- **System alert banner** – status messages (models loading, camera state, recognition results).  

### How it works  
1. Employee stands in front of the camera.  
2. Face is detected, landmarks are extracted, and a descriptor is compared with stored profiles.  
3. If a match is found and liveness check passes, the system automatically decides **Check‑In** or **Check‑Out** based on:
   - Today’s existing logs for that employee.
   - **Auto‑checkout gap** (default 2 hours) – if last scan was a Check‑In and enough time passed, next scan becomes Check‑Out.
   - **Logout cutoff rule** – after a defined time (e.g., 15:00), the first scan forces a Check‑Out.
   - Manual override via admin.  
4. A **toast notification**, **audio beep** (success/warning/error), and **voice announcement** (“John Doe verified. Check‑In registered.”) confirm the action.  
5. The event appears instantly in the live ticker.

### Liveness anti‑spoofing levels  
- **Level 0 – none** – only face match.  
- **Level 1 – blink** – requires the user to blink (detected via eye‑aspect ratio).  
- **Level 2 – head‑turn challenge** – requires blink + turning head left/right as randomly instructed.  

> Admin can change the level in *Controls & Policies*.

---

## Module 2 – Admin Terminal | ایڈمن ٹرمینل  

Accessible by clicking **Admin Terminal** tab and entering the master password (`admin1234` by default).  
After authentication, four sub‑sections appear.

### 2.1 Analytics & Reports  
A dashboard that gives a quick overview of today’s attendance and historical trends.

| Metric Card | Description |
|-------------|-------------|
| Profiles Enrolled | Total active employees (status ≠ Banned). |
| Present Today | Unique employees who checked‑in today. |
| On‑Time / Late | Breakdown of today’s check‑in status. |
| Absentees | Active employees who neither checked‑in nor are on approved leave (excludes weekends/holidays). |
| On Leave | Employees with an approved leave covering today. |
| Hours Today | Sum of session minutes for all completed check‑out sessions. |
| Pending Check‑Out | Employees who checked‑in but have not yet checked‑out. |

**Charts**  
- **Weekly attendance distribution** – stacked bar (on‑time vs late) for the last 7 days.  
- **Department distribution** – doughnut chart showing check‑in count per department (today).  
- **Monthly logs heatmap** – 28‑day grid, color intensity indicates check‑in volume.  

**Attendance records DataTable**  
- Search by name/ID, filter by date range, department, event type, policy status.  
- Built‑in DataTable buttons: **Column visibility, Copy, Excel, PDF, CSV, Print**.  
- Each row has **Edit** and **Delete** actions.  
- **Add Retro Log** button – create or edit past attendance records with custom date/time, type, status, and session minutes.  

**Compliance Alerts** – displays pending check‑outs and short sessions (session < minimum required minutes).  

### 2.2 Reporting Center  
A powerful, filterable report generator for deeper analysis.

**Filters**  
- Date range (from – to)  
- Department  
- Employee  
- Event type (Check‑In / Check‑Out / All)  
- Policy status (On‑Time, Late, Exempt, Before‑Cutoff, etc.)  
- Sort order (latest first, oldest first, A‑Z name, A‑Z department, A‑Z status)  

**KPIs** (automatically update based on filters)  
- Total logs, unique employees, check‑in/out counts, on‑time rate, late rate, total worked time, average session length, attendance rate (unique employees vs active employees), pending check‑outs.  

**Visual Reporting Deck**  
- Daily activity trend (line chart)  
- Department contribution (bar chart)  
- Status mix (doughnut)  
- Check‑In hour profile (bar chart)  

**Chart Lab** – allows you to switch the main chart type on‑the‑fly: bar, line, doughnut, pie, radar, polarArea, scatter, bubble.  

**Infographic Insights** – dynamically generated text insights (top department, top employee, punctuality, coverage, peak check‑in hour).  

**Detailed Records Explorer** – a full DataTable showing every log matching the filters. Supports same export buttons as the Analytics table.  

**Actions**  
- Apply Filters, Reset Filters  
- Print Summary (opens a print‑friendly window)  
- Download JSON (complete report data + filter metadata)  

### 2.3 Profile Directory (Employee Management)  

**Enroll New Employee** – required fields:  
- Employee Name  
- Employee ID (auto‑suggested)  
- Department (from system list)  
- Role  
- Email / Phone (uniqueness check across profiles)  
- Profile Status (Active / Suspended)  
- **Personal shift start & grace minutes** (optional, overrides department/global)  

**Facial enrollment** can be done via:  
1. **Live webcam** – click *Start Preview*, then *Capture Via Webcam*.  
2. **Upload portrait photo** – click the drop zone and select an image containing a clear face.  

**Profile Directory table** – displays all employees with columns: ID, Name, Department, Role, Shift (personal or inherited), Email, Status, Joined date.  
- **Search** by name/ID/role/email.  
- **Department filter** drop‑down.  
- **Edit** – allows changing all fields except ID (descriptor is kept unless re‑captured).  
- **Suspend / Activate** – suspended employees cannot check‑in.  
- **Delete** – permanently removes the biometric template and profile.  

**Manual Check‑In Override** – select an employee and force a Check‑In or Check‑Out (useful for forgotten scans).  

**Leave & Permission Manager**  
- Select employee, type (Leave / Permission), date range, status (Approved/Pending/Rejected), reason.  
- Overlap validation prevents double‑approved leaves on the same dates.  
- List of all leave requests with inline Approve / Pending / Reject / Delete buttons.  
- Approved leaves automatically exclude employees from “Absent” count in Analytics.  

### 2.4 Controls & Policies  

**System Themes & Styling**  
- 5 accent colors (indigo, emerald, rose, amber, blue).  
- Dark/Light theme toggle (persisted).  

**Liveness Challenge Protocol** – choose anti‑spoofing level (none / blink / head‑turn).  

**Shift & Late Policies** (global)  
- Shift start time (e.g., 09:00)  
- Grace period (minutes, e.g., 15)  
- Minimum session minutes (e.g., 480 → 8 hours) – used to determine “Met” / “Short” checkout compliance.  
- Auto Check‑Out Gap (hours) – if a Check‑In is not followed by a Check‑Out after this gap, the next scan will be treated as Check‑Out.  
- Logout Cutoff Time + Enable/Disable – after that time, any scan becomes a Check‑Out (useful for “day end” rule).  
- Weekend Days – select which days of the week are non‑working (Sunday/Saturday by default).  

**Corporate Calendars & Divisions**  
- **Manage Departments** – add new department, delete existing (caution: deletes associated shift policy).  
- **Holidays Exclusions** – add holidays by date + name; on holidays, employees are not marked absent if they don’t check‑in.  
- **Department Shift Templates** – assign a specific shift start time and grace minutes to a department. If an employee has no personal shift, the department template is used.  

**System Datastore Utilities**  
- **Export Backup JSON** – downloads a complete snapshot of all IndexedDB stores.  
- **Import Backup JSON** – restores from a previously exported file (overwrites current data).  
- **Purge Local Database Indexes** – deletes all employees, logs, holidays, leave requests, shift policies, and audit logs (settings like password and theme are kept).  

**Admin Movement Clips** – lists all motion‑triggered or manually recorded video clips. You can download each clip individually.  

**Security Access Settings** – change the master admin password (min. 4 characters).  

**System Audit Trail** – chronological list of every important action (login, profile changes, policy updates, backup, purge, etc.).  
- **Export Audit CSV** – download the full audit trail.  
- **Refresh** – reload the list.  

---

## Module 3 – Security Monitor | سیکیورٹی مانیٹر  

Provides surveillance capabilities using the same camera stream.

### Controls  
- **Keep camera active and detect movement automatically** – when enabled, the system constantly analyses low‑resolution frames.  
  - **Motion Aggressiveness (%)** – sensitivity (higher = more easily triggered).  
  - **Motion Stop Delay (Sec)** – how long after motion stops before the recording is finalised.  
- **Auto‑download motion clips** – if checked, every motion clip is automatically downloaded to your computer after it ends.  
- **Start Continuous Recording** – manually starts a recording (requires admin password).  
- **Stop Continuous Recording** – stops manual recording.  
- **Enable No‑Sleep Mode** – prevents the device screen from turning off while the monitor is active (uses Wake Lock API).  

All recorded clips appear in *Admin Terminal → Controls & Policies → Admin Movement Clips* and can be downloaded later.

> **Performance note:** Motion detection uses a 96x72 canvas and runs every ~650 ms. It is optimised for low CPU usage, but continuous recording may affect performance on low‑end devices.

---

## Core Functionalities (Cross‑Module) | بنیادی افعال  

### Face Recognition Engine  
- Uses **TinyFaceDetector** (faster than SSD Mobilenet).  
- Descriptors are 128‑dimensional vectors.  
- Matching threshold: **0.48** (balanced between false positives and false negatives).  

### Attendance Logic Details  
When a recognised face is verified:  
1. Fetch all logs for that employee **today**.  
2. If no logs → **Check‑In** with status On‑Time/Late based on shift+grace.  
3. If last log is **Check‑Out** → **Check‑In**.  
4. If last log is **Check‑In**:  
   - If elapsed time ≥ *Auto Check‑Out Gap* → **Check‑Out** (status “-“).  
   - If *Logout Cutoff* is enabled and current time ≥ cutoff → **Check‑Out** (status “Before‑Cutoff”).  
   - Else, prompt the user “Mark logout now anyway?” If confirmed → **Check‑Out**, else nothing.  
5. For **Check‑Out**: session minutes = time difference from the most recent paired Check‑In.  
   - If session minutes < *Minimum Session Minutes* → compliance = “Short”, else “Met”.  

### Policy Evaluation Order (Shift start & grace)  
1. **Employee personal settings** (if filled).  
2. **Department shift template** (if exists).  
3. **Global shift & grace** (set in Controls & Policies).  

### Holiday & Weekend Handling  
- If today is a **weekend day** (user‑defined) OR a **holiday** (manually added), the “Absent” count in Analytics does **not** penalise non‑check‑in.  
- Leave requests marked “Approved” covering today also remove the employee from the absent count.  

### Audio & Speech  
- **Success** – rising two‑tone beep.  
- **Warning** – single triangle wave beep.  
- **Error** – low sawtooth beep.  
- **Speech** – announces “{Name} verified. Check‑In registered.” or “Check‑Out registered.” (uses browser’s `speechSynthesis`).  

---

## Usage Guide – Step by Step | استعمال کرنے کا طریقہ  

### For Employees (Kiosk)  
1. Open the app and allow camera access.  
2. Stand facing the camera in good lighting.  
3. Wait for the green box and your name to appear.  
4. Blink or turn your head if liveness is enabled.  
5. The system will beep and announce your check‑in/out.  
6. You can view the latest events in the right‑hand ticker.  

### For Administrator  
1. Click **Admin Terminal** tab.  
2. Enter master password (default `admin1234`).  
3. **First time setup:**  
   - Go to *Controls & Policies* → set your shift start, grace period, weekend days, etc.  
   - Add departments (if needed).  
   - Optionally add holidays.  
4. **Add employees:**  
   - *Profile Directory* → fill the form → capture face via webcam or upload photo.  
5. **Test the Kiosk** by switching back to *Kiosk Scanner* and scanning your own face.  
6. **Monitor attendance** via *Analytics & Reports* and *Reporting Center*.  
7. **Handle exceptions:**  
   - Use *Manual Check‑In Override* for forgotten scans.  
   - Use *Add Retro Log* to correct past records.  
   - Approve leave requests to exclude employees from absence count.  
8. **Regular maintenance:**  
   - *Export Backup JSON* periodically.  
   - Review *Audit Trail* for any suspicious activity.  
   - Clean up old logs by deleting rows manually (no automatic retention yet).  

---

## Configuration Options (Summary Table)  

| Section | Option | Default | Effect |
|---------|--------|---------|--------|
| Kiosk Scanner | Liveness level | none | Adds blink/head‑turn challenge |
| Controls & Policies | Shift start | 09:00 | Base time for On‑Time/Late |
| Controls & Policies | Grace minutes | 15 | Minutes after shift start still considered On‑Time |
| Controls & Policies | Minimum session | 480 min | If session < this → “Short” compliance |
| Controls & Policies | Auto Check‑Out Gap | 2 hours | After this gap, next scan = Check‑Out |
| Controls & Policies | Logout Cutoff | disabled, 15:00 | Scans after cutoff become Check‑Out |
| Controls & Policies | Weekend days | Sun, Sat | No absent penalty on these days |
| Departments | Shift template | none | Overrides global shift for that department |
| Employee (edit) | Personal shift | none | Overrides department & global |
| Security Monitor | Motion aggressiveness | 3% | Higher = more sensitive |
| Security Monitor | Motion idle seconds | 2 s | Delay before stopping recording |
| Security Monitor | Auto‑download clips | false | Saves clips automatically |

---

## Default Values & Important Notes  

| Item | Value |
|------|-------|
| Admin password | `admin1234` |
| Face matching threshold | 0.48 |
| Face detection input size | 224px (TinyFaceDetector) |
| Inference interval (camera) | 300 ms (normal) / 650 ms (when motion recording active) |
| Motion detection resolution | 96×72 |
| DataTables export libraries | JSZip, pdfMake (included via CDN) |
| Max retroactive logs shown | no limit (uses IndexedDB) |
| Audio context | created on first beep (user interaction not required after camera grant) |

> ⚠️ **Important:** IndexedDB is **not** shared across different browsers or devices. Backups must be exported manually if you change devices.

---

## Troubleshooting | مسائل کا حل  

| Problem | Likely cause | Solution |
|---------|--------------|----------|
| Camera not starting | No permission or another app using camera | Grant permission, close other apps, refresh page. |
| Face not recognised | Poor lighting, no enrolled face, threshold too strict | Improve lighting, re‑enrol, consider adjusting threshold (not exposed in UI – contact developer). |
| Liveness challenge never passes | User not blinking enough or head turn too small | Ask user to blink deliberately or exaggerate head turn. |
| Motion recording not saving | Motion aggressiveness too low or idle delay too short | Increase aggressiveness, decrease idle seconds. |
| Export/Import fails | Invalid JSON structure | Use only backups generated by this app. |
| Employee cannot check‑in | Status = Suspended or overlapping approved leave | Activate employee or adjust leave dates. |
| “Short” compliance appears even after full shift | Minimum session minutes too high | Reduce minimum session in Controls & Policies. |
| Datatable buttons not showing | jQuery / DataTable conflict | Refresh page or check browser console. |

---

## Technical Architecture  

- **Frontend:** HTML5, CSS3, vanilla JavaScript (ES6).  
- **Face recognition:** face‑api.js (TensorFlow.js backend, CPU).  
- **Database:** IndexedDB with version 6 schema (stores: employees, attendance_logs, system_config, departments, holidays, leave_requests, shift_policies, audit_logs).  
- **Recording:** MediaRecorder API with WebM container.  
- **Motion detection:** Pixel‑difference comparison on downscaled canvas.  
- **Wake Lock:** Screen Wake Lock API.  
- **Charts:** Chart.js 4.4.3 (used in Reporting Center) and custom SVG for weekly chart.  
- **Data export:** DataTables buttons + JSZip + pdfMake.  
- **Audio:** Web Audio API (OscillatorNode) + SpeechSynthesis.  

### Database Schema (simplified)  
- `employees` – keyPath: `id` – contains name, descriptor (Float32Array), department, role, email, phone, status, shiftStartTime, shiftGraceMinutes, joined.  
- `attendance_logs` – autoIncrement – empId, name, department, role, timestamp, dateString, timeString, type, status, sessionMinutes, checkoutCompliance.  
- `system_config` – keyPath: `key` – stores settings like master_password, shift_start_time, liveness_level, etc.  
- `departments` – keyPath: `name`.  
- `holidays` – keyPath: `date`.  
- `leave_requests` – autoIncrement – empId, name, department, type, dateFrom, dateTo, status, reason, createdAt.  
- `shift_policies` – keyPath: `department` – shiftStartTime, shiftGraceMinutes.  
- `audit_logs` – autoIncrement – timestamp, action, detail.  

---

## Urdu Section | اردو حصہ  

### تعارف  
**Aura** ایک ویب‑بیسڈ چہرے کی شناخت کا حاضری نظام ہے جو مکمل طور پر براؤزر میں چلتا ہے۔ اسے چلانے کے لیے کسی سرور کی ضرورت نہیں، تمام ڈیٹا **IndexedDB** میں محفوظ ہوتا ہے۔ یہ تین اہم حصوں پر مشتمل ہے: **کیوسک اسکینر** (روزانہ حاضری کے لیے)، **سیکیورٹی مانیٹر** (موشن بیسڈ ویڈیو ریکارڈنگ)، اور **ایڈمن ٹرمینل** (ملازمین، پالیسیوں، رپورٹس اور کنفیگریشن کے لیے)۔  

### نمایاں خصوصیات  
- ریئل ٹائم چہرے کی شناخت + اینٹی سپوفنگ (پلک جھپکنے / سر موڑنے کی چیلنج)  
- ویب کیم یا تصویر اپ لوڈ کے ذریعے بائیو میٹرک اندراج  
- خودکار چیک‑ان/چیک‑آؤٹ منطق (شفٹ، گریس، لاگ آؤٹ کٹ آف کی بنیاد پر)  
- دستی حاضری (ریٹرو ایکٹو لاگ)  
- ملازمین کی ڈائرکٹری (تلاش، معطل/فعال، ترمیم، حذف)  
- چھٹی اور اجازت نامہ (تاریخوں کی اوورلیپ چیک)  
- شفٹ پالیسیاں (محکمہ اور ذاتی سطح پر)  
- عالمی پالیسیاں (ہفتے کے چھٹی والے دن، چھٹیاں، کم سے کم سیشن دورانیہ، خودکار چیک‑آؤٹ گیپ)  
- تجزیاتی ڈیش بورڈ (KPIs، ہفتہ وار چارٹ، محکمہ جاتی ڈونٹ چارٹ، 28 دنوں کا ہیٹ میپ)  
- جامع رپورٹنگ ہب (فلٹرز، KPI ویجیٹس، متعدد چارٹس، چارٹ لیب، ڈیٹا ٹیبل ایکسپورٹس)  
- آڈٹ ٹریل  
- بیک اپ / ریسٹور (مکمل ڈیٹا بیس کی JSON میں برآمد / درآمد)  
- سیکیورٹی مانیٹر (موشن کا پتہ لگانا، خودکار / دستی ریکارڈنگ)  
- ڈارک موڈ اور ایکسنٹ کلرز  
- آڈیو فیڈبیک (بیپس + صوتی اعلانات)  

### انسٹالیشن اور سیٹ اپ  
1. `index.html` کو کسی بھی ویب سرور پر رکھیں یا لوکل طور پر کھولیں۔  
2. پہلی بار چلانے پر Face‑API ماڈلز (تقریباً 5 MB) CDN سے لوڈ ہوں گے۔  
3. **Chrome**، **Edge**، یا **Firefox** استعمال کریں۔  
4. کیمرے کی اجازت دیں۔  
5. **پہلے سے طے شدہ ایڈمن پاس ورڈ:** `admin1234` (بعد میں تبدیل کیا جا سکتا ہے)۔  

### کیوسک اسکینر  
ملازمین حاضری لگانے کے لیے یہ انٹرفیس استعمال کرتے ہیں۔  
- ویڈیو اسکرین، اسٹارٹ/پاز/اسٹاپ بٹن۔  
- لائیو ٹکر (آخری 20 واقعات)۔  
- چہرے کی شناخت کے بعد خودکار طور پر چیک‑ان یا چیک‑آؤٹ فیصلہ ہوتا ہے۔  
- اینٹی سپوفنگ کی تین سطحیں: کوئی نہیں، پلک جھپکنا، سر موڑنے کا چیلنج۔  

### ایڈمن ٹرمینل  
`admin1234` ڈال کر کھولیں۔ چار ذیلی سیکشن:  

#### 2.1 تجزیات اور رپورٹس  
آج کی حاضری کا فوری جائزہ۔ KPIs، ہفتہ وار بار چارٹ، محکمہ جاتی ڈونٹ چارٹ، 28‑دن ہیٹ میپ۔ **حاضری ریکارڈز** کی ڈیٹا ٹیبل (تلاش، فلٹر، ایڈٹ، ڈیلیٹ، کالم منتخب، ایکسل/پی ڈی ایف/سی ایس وی ایکسپورٹ)۔ **کمپلائنس الرٹس** (پینڈنگ چیک‑آؤٹ اور مختصر سیشن)۔ **ریٹرو لاگ** بٹن – ماضی کے کسی بھی دن کے لیے دستی اندراج۔  

#### 2.2 رپورٹنگ سینٹر  
مکمل فلٹرز کے ساتھ جدید رپورٹ جنریٹر۔  
- فلٹرز: تاریخ کی حد، محکمہ، ملازم، ایونٹ کی قسم، پالیسی اسٹیٹس، ترتیب۔  
- KPI ویجیٹس (کل لاگز، یونیک ایمپلائیز، آن ٹائم ریٹ، ورکڈ ٹائم وغیرہ)۔  
- بصری چارٹس (روزانہ ٹرینڈ، محکمہ جاتی، اسٹیٹس مکس، آور پروفائل)۔  
- **چارٹ لیب** – چارٹ کی قسم کو بدلیں (bar, line, doughnut, pie, radar, polarArea, scatter, bubble)۔  
- **انفوگرافک انسائٹس** – خودکار ٹیکسٹ تجزیہ۔  
- **تفصیلی ایکسپلورر** – فلٹر شدہ لاگز کی ڈیٹا ٹیبل، ایکسپورٹ کے ساتھ۔  
- کارروائیاں: فلٹر لگائیں، ری سیٹ کریں، سماری پرنٹ کریں، JSON ڈاؤن لوڈ کریں۔  

#### 2.3 پروفائل ڈائرکٹری (ملازمین کا انتظام)  
- نیا ملازم شامل کریں (نام، آئی ڈی، محکمہ، کردار، ای میل، فون، اسٹیٹس، ذاتی شفٹ)۔  
- چہرے کا اندراج: براہ راست ویب کیم یا تصویر اپ لوڈ کے ذریعے۔  
- ٹیبل میں ترمیم، معطل/فعال، حذف کریں۔  
- **دستی چیک‑ان اوور رائیڈ** – بھولی ہوئی حاضری کے لیے۔  
- **لیو مینیجر** – چھٹی/اجازت کی تاریخوں کے ساتھ درخواستیں، اوورلیپ چیک، منظور/زیر التواء/مسترد۔  

#### 2.4 کنٹرولز اور پالیسیاں  
- تھیمز (ڈارک موڈ + ایکسنٹ کلر)  
- لیونیس لیول (none / blink / چیلنج)  
- عالمی شفٹ پالیسیاں (شفٹ کا وقت، گریس، کم سے کم سیشن منٹ، آٹو چیک‑آؤٹ گیپ، لاگ آؤٹ کٹ آف، ویک اینڈ ڈیز)  
- محکمہ جاتی شفٹ ٹیمپلیٹس  
- ہالیڈیز  
- ڈیٹا اسٹور یوٹیلٹیز (بیک اپ JSON درآمد/برآمد، پرج ڈیٹا بیس)  
- ایڈمن موومنٹ کلپس کی فہرست (ڈاؤن لوڈ کے ساتھ)  
- ایڈمن پاس ورڈ تبدیل کریں  
- سسٹم آڈٹ ٹریل (CSV ایکسپورٹ)  

### سیکیورٹی مانیٹر  
کیمرے کو استعمال کرتے ہوئے موشن کا پتہ لگانا اور ریکارڈنگ۔  
- **خودکار موشن ڈیٹیکشن** – حساسیت اور رکنے کی تاخیر سیٹ کریں۔  
- **آٹو ڈاؤن لوڈ** – آن ہونے پر ہر کلپ خودکار طور پر محفوظ ہو جائے گا۔  
- **مسلسل ریکارڈنگ** (دستی)۔  
- **نو‑سلیپ موڈ** (اسکرین بند ہونے سے روکتا ہے)۔  
تمام ریکارڈ شدہ کلپس ایڈمن ٹرمینل میں دیکھی جا سکتی ہیں۔  

### استعمال کی گائیڈ (مختصر)  
**ملازمین کے لیے:**  
کیوسک اسکینر کھولیں → کیمرے کے سامنے کھڑے ہوں → چہرہ پہچانے جانے پر بیپ سنیں اور اعلان سنیں → حاضری لگ جائے گی۔  

**ایڈمن کے لیے:**  
ایڈمن ٹرمینل میں پاس ورڈ ڈالیں → پہلے شفٹ پالیسیاں اور محکمے سیٹ کریں → ملازمین کو چہرے کے ساتھ رجسٹر کریں → حاضری مانیٹر کریں، رپورٹس نکالیں، وقتاً فوقتاً بیک اپ لیں۔  

### ڈیفالٹ ویلیوز  
- ایڈمن پاس ورڈ: `admin1234`  
- شفٹ ٹائم: 09:00  
- گریس: 15 منٹ  
- کم سے کم سیشن: 480 منٹ  
- آٹو چیک‑آؤٹ گیپ: 2 گھنٹے  
- ویک اینڈ ڈیز: اتوار، ہفتہ  
- چہرے کی مماثلت کی حد: 0.48  

### خرابیوں کا حل (عام مسائل)  

| مسئلہ | ممکنہ وجہ | حل |
|--------|------------|------|
| کیمرہ اسٹارٹ نہیں ہوتا | اجازت نہیں دی | اجازت دیں، براؤزر ریفریش کریں |
| چہرہ شناخت نہیں ہوتا | روشنی کم، اندراج نہیں | روشنی بہتر کریں، دوبارہ اندراج کریں |
| موشن ریکارڈنگ نہیں ہوتی | حساسیت کم | aggressiveness بڑھائیں |
| ایکسپورٹ/امپورٹ فیل | JSON خراب | صرف اس ایپ سے بنایا ہوا بیک اپ استعمال کریں |
| ملازم چیک‑ان نہیں کر سکتا | اسٹیٹس معطل یا چھٹی اوورلیپ | اسٹیٹس ایکٹو کریں یا چھٹی کی تاریخیں بدلیں |

### تکنیکی فن تعمیر  
- فرنٹ اینڈ: HTML5، CSS3، جاوا اسکرپٹ (ES6)  
- چہرے کی شناخت: face‑api.js + TensorFlow.js  
- ڈیٹا بیس: IndexedDB (ورژن 6)  
- ریکارڈنگ: MediaRecorder API (WebM)  
- موشن ڈیٹیکشن: پکسل فرق کا طریقہ  
- چارٹس: Chart.js 4.4.3 + حسب ضرورت SVG  
- آڈیو: Web Audio API + SpeechSynthesis  

---

## Conclusion | اختتام  
Aura provides a complete, serverless facial recognition attendance solution with enterprise‑grade features, all running inside a single HTML file. The bilingual documentation ensures that both English‑speaking and Urdu‑speaking clients can fully understand and operate the system.  

For any further customisation or support, please contact the development team.

**End of Documentation | دستاویز کا اختتام**
```