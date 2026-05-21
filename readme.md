# 🌐 Aura Facial Recognition Attendance Suite v3.0
## 📖 Comprehensive Bilingual Documentation (English | Urdu)
**A client-ready, feature-complete guide covering every module, setting, workflow, and system-wide impact.**

---

## 📖 Project Overview | 📖 منصوبے کا جائزہ
**English:**  
Aura Facial Recognition Attendance Suite v3.0 is a secure, offline-capable, real-time attendance management system built with facial recognition, motion tracking, advanced analytics, and strict policy enforcement. It operates on a local filesystem core, ensuring data privacy and zero dependency on cloud infrastructure. The suite is divided into three primary interfaces: **Kiosk Scanner**, **Security Monitor**, and **Admin Terminal**, each serving distinct operational roles while sharing a unified data layer.

**اردو:**  
Aura Facial Recognition Attendance Suite v3.0 ایک محفوظ، آف لائن چلنے والا، ریئل ٹائم حاضری مینجمنٹ سسٹم ہے جو فیشل ریکگنیشن، موشن ٹریکنگ، ایڈوانسڈ اینالیٹکس، اور سخت پالیسی نفاذ پر مبنی ہے۔ یہ لوکل فائل سسٹم کور پر کام کرتا ہے، جو ڈیٹا کی رازداری اور کلاؤڈ انفراسٹرکچر سے مکمل آزادی یقینی بناتا ہے۔ سوٹ تین بنیادی انٹرفیسز پر مشتمل ہے: **Kiosk Scanner**، **Security Monitor**، اور **Admin Terminal**، جو ہر ایک الگ آپریشنل کردار ادا کرتے ہوئے ایک مشترکہ ڈیٹا لیئر استعمال کرتے ہیں۔

---

## 🔐 Administrative Access Gate | 🔐 انتظامی رسائی گیٹ
| Feature / خصوصیت | Function / کام | System Impact / نظامی اثر |
|---|---|---|
| Master Passcode Input | Requires authentication to access analytics, directories, and policies. | Blocks unauthorized configuration changes. All admin modules remain locked until verified. |
| Reveal Toggle | Shows/hides passcode characters during entry. | Improves UX without compromising security. |
| Default Access Key | `admin1234` (Pre-configured for first-time setup) | Allows initial system access. **Must be changed** via Security Access Settings for production use. |
| Verify Identity Signature 🔑 | Cryptographic validation of entered credentials. | Grants persistent admin session. Triggers audit trail logging. |

**اردو:**  
- **ماسٹر پاس کوڈ ان پٹ:** اینالیٹکس، ڈائریکٹریز اور پالیسیز تک رسائی کے لیے تصدیق ضروری ہے۔ غیر مجاز تبدیلیوں کو روکتا ہے۔  
- **ریویل ٹوگل:** ٹائپنگ کے دوران پاس ورڈ کے حروف ظاہر/چھپاتا ہے۔  
- **ڈیفالٹ ایکسس کی:** `admin1234` (پہلی بار سیٹ اپ کے لیے)۔ پروڈکشن میں استعمال سے قبل سیکیورٹی سیٹنگز سے تبدیل کرنا لازمی ہے۔  
- **تصدیق 🗝️:** داخل کردہ کریڈنشلز کی کریپٹوگرافک ویلیڈیشن۔ ایڈمن سیشن فعال کرتا ہے اور آڈٹ ٹریل میں لاگ انٹری بناتا ہے۔

---

## 📹 Module 1: Kiosk Scanner & Live Operations | ماڈیول 1: کیوسک اسکینر اور لائیو آپریشنز
| Control / کنٹرول | Function / کام | System Impact / نظامی اثر |
|---|---|---|
| Facial Recognition Scanner Status | Shows `OFFLINE` until webcam is activated. | Prevents accidental check-ins. System awaits hardware initialization. |
| Webcam Inactive / Start Preview | Initializes camera feed for high-precision verification scans. | Activates facial detection pipeline. Live feed enables real-time matching against enrolled profiles. |
| Start / Pause / Stop | Controls scanning lifecycle. | `Start`: Begins continuous face detection. `Pause`: Freezes detection but keeps camera alive. `Stop`: Releases hardware resources and sets status to OFFLINE. |
| Live Check-in Ticker | Real-time scrolling display of successful verifications. | Provides instant visual feedback for employees & HR. Updates lock console stats and attendance logs automatically. |
| 🌓 ThemeCore Active | Dynamic UI theme engine. | Adjusts contrast, accent colors, and dashboard layout without affecting core logic. |
| Initializing Local Filesystem Core | Boot sequence indicator. | Loads encrypted local databases, policy caches, and session states. Essential for offline operation. |

**اردو:**  
- **فیشل اسکینر اسٹیٹس:** ویب کیم ایکٹیویٹ ہونے تک `OFFLINE` رہتا ہے۔ غیر ارادی چیک ان سے بچاتا ہے۔  
- **ویب کیم اسٹارٹ/پریویو:** کیمرہ فیڈ کو ہائی پریسیژن ویریفیکیشن کے لیے تیار کرتا ہے۔ فیشل ڈیٹکشن پائپ لائن فعال ہوتی ہے۔  
- **اسٹارٹ/پاز/اسٹاپ:** اسکیننگ کے مراحل کو کنٹرول کرتا ہے۔ اسٹارٹ: مسلسل ڈیٹکشن، پاز: ڈیٹکشن روک کر کیمرہ فعال رکھتا ہے، اسٹاپ: ہارڈویئر ریسورسز آزاد کرتا ہے۔  
- **لائیو چیک ان ٹکر:** کامیاب ویریفیکیشن کا ریئل ٹائم اسکرولنگ ڈسپلے۔ لاگ اینٹریز اور ڈیش بورڈ اسٹیٹس کو خودکار اپڈیٹ کرتا ہے۔  
- **تھیم کور:** UI کی بصری ترتیب تبدیل کرتا ہے بغیر بیک اینڈ لاجک متاثر کیے۔  
- **لوکل فائل سسٹم کور:** انسکرپٹڈ لوکل ڈیٹا بیس اور پالیسی کیش لوڈ کرتا ہے۔ آف لائن آپریشن کی بنیاد ہے۔

---

## 🛡️ Module 2: Security Monitor & Motion Tracking | ماڈیول 2: سیکیورٹی مانیٹر اور موشن ٹریکنگ
| Setting / سیٹنگ | Function / کام | System Impact / نظامی اثر |
|---|---|---|
| Keep Camera Active & Detect Movement Clips | Continuously monitors frame changes for motion. | Enables security surveillance mode. Clips are stored locally for admin review. |
| Auto-Download Motion Clips | Automatically exports recorded clips to local storage. | Prevents storage overflow. Ensures evidence retention without manual intervention. |
| Motion Aggressiveness (%) | Sensitivity threshold for motion detection (0–100%). | Higher % = triggers on minor movements. Lower % = ignores ambient changes. Directly affects clip frequency and CPU load. |
| Motion Stop Delay (Sec) | Time buffer before marking motion as "ended". | Prevents fragmented clips. Ensures continuous recording of single events. |
| Start/Stop Continuous Recording | Toggles persistent video capture. | Useful for audits or high-security zones. Impacts local disk usage. |
| Enable No-Sleep Mode | Prevents system/browser from sleeping. | Guarantees 24/7 uptime for kiosks. Critical for overnight shifts or remote locations. |
| Save Motion Settings 🔒 | Locks configuration behind admin passcode. | Prevents unauthorized tampering with security parameters. |
| Admin Movement Clips Viewer | Gallery of captured motion segments. | Centralized review hub. Links timestamps to attendance logs for anomaly detection. |

**اردو:**  
- **کیمرہ ایکٹیو رکھیں اور موشن کلپس ڈیٹیکٹ کریں:** فریم چینجز کی مسلسل نگرانی۔ سیکیورٹی سرویلنس موڈ فعال کرتا ہے۔  
- **آٹو ڈاؤن لوڈ:** ریکارڈڈ کلپس خودکار لوکل اسٹوریج میں محفوظ۔ اسٹوریج اوور فلو سے بچاتا ہے۔  
- **موشن ایگریسیونیٹس (%):** حساسیت کی حد۔ زیادہ % = معمولی حرکت پر ٹرگر، کم % = ماحولیاتی تبدیلیوں کو نظر انداز۔ CPU لوڈ اور کلپ فریکوئنسی پر اثر انداز۔  
- **موشن اسٹاپ ڈیلے (سیکنڈ):** موشن ختم ہونے سے پہلے کا ٹائم بفر۔ کلپس کو ٹوٹنے سے بچاتا ہے۔  
- **مسلسل ریکارڈنگ اسٹارٹ/اسٹاپ:** مستقل ویڈیو کیپچر۔ آڈٹ یا ہائی سیکیورٹی زونز کے لیے مفید۔ ڈسک اسپیس متاثر کرتا ہے۔  
- **نو سلپ موڈ:** سسٹم/براؤزر کو سونے سے روکتا ہے۔ 24/7 اپ ٹائم یقینی بناتا ہے۔  
- **سیٹنگز محفوظ کریں 🔒:** ایڈمن پاس کوڈ کے تحت ترتیبات کو لاک کرتا ہے۔ غیر مجاز ترمیم سے بچاتا ہے۔  
- **ایڈمن موشن کلپس ویور:** کیپچرڈ سیگمنٹس کا مرکزی جائزہ ہب۔ غیر معمولی سرگرمیوں کی شناخت کے لیے ٹائم اسٹامپس سے لنک ہوتا ہے۔

---

## 📊 Module 3: Admin Terminal, Analytics & Reporting | ماڈیول 3: ایڈمن ٹرمینل، اینالیٹکس اور رپورٹنگ
### 🔒 Lock Console Profiles (Real-Time Dashboard Stats)
| Metric | Description | Impact |
|---|---|---|
| Enrolled / موجود / آن ٹائم / لیٹ / غیر حاضری / چھٹی / آج کے اوقات / پنڈنگ چیک آؤٹ | Live counters reflecting database state. | Drives executive decision-making. Updates automatically on every check-in/out or manual log entry. |

### 📈 Visual Reporting & Chart Lab
| Component | Function | System Impact |
|---|---|---|
| Weekly Attendance Distribution | Bar/Line graph of daily presence. | Identifies absenteeism trends. |
| Department Distribution | Pie/Doughnut split by division. | Highlights departmental compliance gaps. |
| Monthly Logs Heatmap | Color-coded grid showing activity density. | Visualizes peak vs off-peak hours. |
| Executive KPI Summary | Aggregated metrics (Total Logs, Unique Employees, On-Time/Late Rates, Total Worked Time, Avg Session, Attendance Rate, Pending Checkouts). | Core HR analytics. Used for payroll, performance reviews, and policy adjustments. |
| Chart Lab (Any Type) | Customizable visualizations: Bar, Line, Doughnut, Pie, Radar, Polar Area, Scatter, Bubble. | Flexible data exploration. Exports match selected filters. |
| Infographic Insights | Auto-generated narrative summaries. | Simplifies complex data for management presentations. |

### 🔍 Comprehensive Reporting Hub & Datatable Filters
| Filter / Report Control | Function | Impact |
|---|---|---|
| Date Range (All/Today/Yesterday/7/30 Days) | Narrows log visibility. | Directly affects exported datasets and chart rendering. |
| Department / Employee / Event Type / Policy Status | Multi-dimensional filtering. | Enables targeted audits (e.g., "Late logs for IT dept last 7 days"). |
| Check-Out (-) Before Cutoff Logout | Flags premature exits. | Impacts compliance rate and payroll deductions. |
| Add Retro Log ➕ | Manually inserts missing/historical entries. | Overrides auto-calculated session times. Triggers audit trail. |
| Export CSV / Export Today Summary / Download JSON / Print Summary | Data extraction formats. | CSV for Excel, JSON for system integrations, PDF-ready print. |
| Sort Output (Latest/Oldest/Alpha/Dept/Status) | Reorders datatable rows. | Improves readability without altering underlying data. |
| Compliance Alerts | Real-time notifications for policy breaches. | Immediate HR intervention. Linked to shift rules and grace periods. |

**اردو:**  
- **لاک کنسول اسٹیٹس:** ریئل ٹائم کاؤنٹرز جو ڈیٹا بیس کی حالت عکاسی کرتے ہیں۔ ہر چیک ان/آؤٹ یا مینوئل انٹری پر خودکار اپڈیٹ۔  
- **چارٹس اور رپورٹس:** ہفتہ وار حاضری، ڈیپارٹمنٹل تقسیم، ماہانہ ہیٹ میپ، ایگزیکٹو KPI (کل لاگز، یونیک ایمپلائیز، وقت پر/لیٹ ریٹ، کل ورکڈ ٹائم، اوسط سیشن، حاضری ریٹ، پنڈنگ چیک آؤٹ)۔ چارٹ لیب میں ہر قسم کی گرافکس بنائی جا سکتی ہیں۔  
- **فِلٹرز اور رپورٹنگ ہب:** تاریخ، ڈیپارٹمنٹ، ایمپلائی، ایونٹ ٹائپ، پالیسی اسٹیٹس، کٹ آف سے پہلے چیک آؤٹ فلٹرز۔ ڈیٹا ایکسپورٹ (CSV, JSON, پرنٹ)، ترتیب (تازہ ترین/قدیم ترین/الفا/ڈیپارٹمنٹ/اسٹیٹس)۔  
- **کمپلائنس الرٹس:** پالیسی خلاف ورزیوں کی فوری نوٹیفیکیشن۔ شفٹ رولز اور گریس پیریڈز سے لنکڈ۔  

---

## 👤 Module 4: Employee Management & Enrollment | ماڈیول 4: ایمپلائیز مینجمنٹ اور اندراج
| Feature | Function | Impact |
|---|---|---|
| Enroll New Profile | Form: Name, ID, Dept, Role, Email, Phone, Status (Active/Suspended), Personal Shift Start, Personal Grace Minutes. | Creates core identity record. Determines policy application scope. |
| Facial Recognition Enrollment Preview | 📷 Enrollment Camera: Start/Preview, Capture Via Webcam, Upload Portrait, Save Profile, Update Face, Cancel. | Binds biometric template to employee ID. Enables kiosk verification. `Update` replaces old template without altering logs. |
| Manual Check-in Override | Select Profile → Check Type (In/Out) → Register. | Bypasses facial scan. Used for hardware failure or visitor/guest tracking. Logs as "Manual" in audit trail. |
| Leave & Permission Manager | Employee, Type (Leave/Permission), Dates, Status (Approved/Pending/Rejected), Reason. | Modifies attendance calculations. Approved leaves reduce "Absentee" count and adjust worked hours. |
| Profiles Records Directory | Filter: All Departments. Columns: ID, Name, Dept, Role, Shift, Email, Status, Date Enrolled, Action. | Central employee registry. `Action` buttons allow edit/suspend/deactivate. Drives dropdowns in reporting & leave modules. |

**اردو:**  
- **نیا پروفائل اندراج:** نام، آئی ڈی، ڈیپارٹمنٹ، رول، ای میل، فون، اسٹیٹس (ایکٹیو/سسپینڈڈ)، پرسنل شفٹ اسٹارٹ، پرسنل گریس منٹس۔ بنیادی شناختی ریکارڈ بناتا ہے۔  
- **فیشل انرولمنٹ پریویو:** کیمرہ اسٹارٹ/پریویو، ویب کیم سے کیپچر، فوٹو اپ لوڈ، پروفائل محفوظ، چہرہ اپڈیٹ، منسوخ۔ بائیو میٹرک ٹیمپلیٹ کو آئی ڈی سے لنک کرتا ہے۔  
- **مینوئل چیک ان اوور رائیڈ:** پروفائل منتخب → چیک ٹائپ → رجسٹر۔ فیشل اسکین کو بائی پاس کرتا ہے۔ آڈٹ ٹریل میں "Manual" لاگ ہوتا ہے۔  
- **لیو اور پرمیشن مینیجر:** ایمپلائی، ٹائپ، تاریخیں، اسٹیٹس، وجہ۔ حاضری کیلکولیشنز کو متاثر کرتا ہے۔ منظور شدہ چھٹیاں غیر حاضری کاؤنٹر کم کرتی ہیں۔  
- **پروفائلز ڈائریکٹری:** فلٹر: تمام ڈیپارٹمنٹس۔ کالمز: آئی ڈی، نام، ڈیپارٹمنٹ، رول، شفٹ، ای میل، اسٹیٹس، اندراج تاریخ، ایکشن۔ مرکزی ایمپلائی رجسٹری۔  

---

## ⚙️ Module 5: Policies, Shifts & Compliance Controls | ماڈیول 5: پالیسیز، شفٹس اور کمپلائنس کنٹرولز
| Setting | Function | Impact |
|---|---|---|
| Liveness Challenge Anti-Spoofing | Level 0: Standard Match, Level 1: Passive Eye Blink, Level 2: Interactive Head Turn. | Prevents photo/mask spoofing. Higher levels increase security but require user cooperation during scan. |
| Shift & Late Policy | Shift Start Time, Grace Period (Min), Min Session Minutes, Auto Check-Out Gap (Hrs), Logout Cutoff Time. | Defines "On-Time", "Late", "Exempt", "Before Cutoff". Auto check-out prevents dangling sessions. Cutoff enforces strict end-of-day logging. |
| Enforce Logout Cutoff Rule | Toggle: After cutoff time, second scan logs as Check-Out. | Overrides manual check-in type. Ensures accurate daily totals. |
| Weekend Days Selection | Sun–Sat multi-select. | Excludes weekends from attendance calculations and KPI rates. |
| Apply Shift & Calendar Rules | Commits policy changes. | Triggers recalculation of historical logs if dates align. |
| System Themes & Styling | Accent Color Brand Palette. | Visual customization only. No functional impact on data or policies. |

**اردو:**  
- **لایونیٹس چیلنج اینٹی اسپوفنگ:** لیول 0: اسٹینڈرڈ میچ، لیول 1: پیسو آئی بلنک، لیول 2: انٹرایکٹو ہیڈ ٹرن۔ فوٹو/ماسک اسپوفنگ سے بچاتا ہے۔ ہائی لیولز سیکیورٹی بڑھاتے ہیں مگر صارف کی تعاون طلب کرتے ہیں۔  
- **شفٹ اور لیٹ پالیسی:** شفٹ اسٹارٹ ٹائم، گریس پیریڈ، کم از کم سیشن منٹس، آٹو چیک آؤٹ گیپ، لاگ آؤٹ کٹ آف ٹائم۔ "وقت پر"، "لیٹ"، "مستثنیٰ"، "کٹ آف سے پہلے" کی تعریف کرتا ہے۔  
- **لاگ آؤٹ کٹ آف رول نافذ کریں:** کٹ آف کے بعد دوسرا اسکین خودکار چیک آؤٹ بناتا ہے۔ یومیہ ٹوٹلز کی درستگی یقینی بناتا ہے۔  
- **ویک اینڈ ڈیز سلیکشن:** اتوار تا ہفتہ۔ حاضری کیلکولیشنز اور KPI ریٹس سے ویک اینڈ خارج کرتا ہے۔  
- **پالیسیز اپلائی کریں:** تبدیلیاں کمٹ کرتا ہے۔ اگر تاریخیں ملتی ہوں تو تاریخی لاگز کی دوبارہ کیلکولیشن ٹرگر کرتا ہے۔  
- **تھیمز اور اسٹائلنگ:** ایکسنٹ کلر برانڈ پیلیٹ۔ صرف بصری ترتیب، ڈیٹا یا پالیسیز پر کوئی اثر نہیں۔  

---

## 🗃️ Module 6: Data Management, Backup & Audit | ماڈیول 6: ڈیٹا مینجمنٹ، بیک اپ اور آڈٹ
| Utility | Function | Impact |
|---|---|---|
| Export Backup JSON | Downloads full system state (profiles, logs, policies, settings). | Essential before major changes. Enables disaster recovery. |
| Import Backup JSON | Restores system from saved JSON. | Overwrites current data. **Irreversible** without prior backup. |
| Purge Local Database Indexes | Clears cached logs and temporary records. | Frees storage. Does not delete core profiles unless explicitly selected. |
| System Audit Trail | Logs every admin action, policy change, and manual override. | Provides compliance proof. Exportable as CSV for HR/legal review. |
| Corporate Calendars & Divisions | Manage Departments, Holidays Exclusions, Department Shift Templates, Save Policy. | Enables multi-location/multi-division compliance. Holidays adjust "On Leave" and "Absentee" logic. |
| Security Access Settings | Change Dashboard Password / Update Passcode Key. | Secures admin gate. Invalidates old sessions. Required for role rotation. |
| Retroactive Attendance Log Entry | Select Employee, Date, Time, Event Type, Policy Status, Session Minutes, Save. | Corrects missed scans or system downtime. Updates KPIs, worked hours, and compliance rates retroactively. |

**اردو:**  
- **بیک اپ JSON ایکسپورٹ:** مکمل سسٹم اسٹیٹ ڈاؤن لوڈ۔ بڑی تبدیلیوں سے پہلے لازمی۔ ڈیزاسٹر ریکوری کے لیے۔  
- **بیک اپ JSON امپورٹ:** محفوظ JSON سے سسٹم بحال۔ موجودہ ڈیٹا اوور رائیٹ کرتا ہے۔ بغیر بیکاپ کے ناقابل واپسی۔  
- **لوکل ڈیٹا بیس انڈیکسز صاف کریں:** کیشڈ لاگز اور عارضی ریکارڈز حذف۔ اسٹوریج آزاد کرتا ہے۔  
- **سسٹم آڈٹ ٹریل:** ہر ایڈمن ایکشن، پالیسی تبدیلی، مینوئل اوور رائیڈ کا لاگ۔ کمپلائنس ثبوت فراہم کرتا ہے۔ CSV ایکسپورٹ۔  
- **کارپوریٹ کیلنڈر اور ڈویژنز:** ڈیپارٹمنٹس، چھٹیاں، شفٹ ٹیمپلیٹس، پالیسی محفوظ۔ ملٹی لوکیشن کمپلائنس ممکن بناتا ہے۔  
- **سیکیورٹی ایکسس سیٹنگز:** ڈیش بورڈ پاس ورڈ تبدیل/اپڈیٹ۔ ایڈمن گیٹ کو محفوظ کرتا ہے۔ پرانے سیشنز منسوخ۔  
- **ریٹرو ایکٹیو لاگ انٹری:** ایمپلائی، تاریخ، وقت، ایونٹ ٹائپ، پالیسی اسٹیٹس، سیشن منٹس، محفوظ۔ چھوٹے ہوئے اسکینز یا سسٹم ڈاؤن ٹائم کو درست کرتا ہے۔ KPIs اور ورکڈ آورز کو ریٹرو ایکٹیولی اپڈیٹ کرتا ہے۔  

---

## 📋 Step-by-Step Usage Guide | 📋 قدم بہ قدم استعمال کی رہنمائی
### 🟢 Initial Setup / ابتدائی سیٹ اپ
1. Open `index.html` in a modern browser.  
   `index.html` کو جدید براؤزر میں کھولیں۔  
2. Click **Start Webcam** under Kiosk Scanner. Grant camera permissions.  
   کیوسک اسکینر کے تحت **Start Webcam** پر کلک کریں۔ کیمرہ اجازت دیں۔  
3. Enter default master passcode `admin1234` in **Administrative Access Gate** → Verify.  
   ایڈمن گیٹ میں ڈیفالٹ پاس کوڈ `admin1234` ڈالیں → Verify کریں۔  
4. Navigate to **Security Access Settings** → Update Passcode Key.  
   سیکیورٹی سیٹنگز → پاس کوڈ کی اپڈیٹ کریں۔  

### 👤 Enrolling Employees / ایمپلائیز کا اندراج
1. Go to **Enroll New Employee Profile**. Fill details.  
   نئے پروفائل فارم پر جائیں۔ تفصیلات بھریں۔  
2. Click **Start Preview** → **Capture Via Webcam** (or Upload).  
   پریویو اسٹارٹ → ویب کیم سے کیپچر (یا اپ لوڈ)۔  
3. Click **Save Profile**. Verify in **Profiles Directory**.  
   پروفائل محفوظ کریں۔ ڈائریکٹری میں تصدیق کریں۔  

### 🔄 Daily Operations / روزمرہ آپریشنز
1. Keep Kiosk Scanner **Active** during working hours.  
   کام کے اوقات میں کیوسک اسکینر **Active** رکھیں۔  
2. Employees face camera → System auto-checks in/out.  
   ایمپلائیز کیمرے کی طرف دیکھیں → سسٹم خودکار چیک ان/آؤٹ کرے گا۔  
3. Monitor **Live Check-in Ticker** and **Lock Console** stats.  
   لائیو ٹکر اور کنسول اسٹیٹس مانیٹر کریں۔  
4. Use **Security Monitor** for motion tracking if required.  
   ضرورت ہو تو سیکیورٹی مانیٹر سے موشن ٹریکنگ استعمال کریں۔  

### 📊 Reporting & Audits / رپورٹنگ اور آڈٹ
1. Open **Admin Terminal** → **Analytics & Reports**.  
   ایڈمن ٹرمینل → اینالیٹکس اینڈ رپورٹس کھولیں۔  
2. Apply filters → Click **Apply Filters** → **Download JSON/Export CSV**.  
   فلٹرز لگائیں → Apply → JSON/CSV ڈاؤن لوڈ کریں۔  
3. Review **Compliance Alerts** and **Audit Trail** for anomalies.  
   کمپلائنس الرٹس اور آڈٹ ٹریل کا جائزہ لیں۔  

---

## 🌍 Cross-Module Impact Summary | 🌍 کراس ماڈیول اثرات کا خلاصہ
| Action | Affected Modules | Consequence |
|---|---|---|
| Change Shift Policy | Kiosk, Analytics, Leave Manager, Retro Log | Recalculates Late/On-Time status. Updates KPIs. Alters historical logs if dates match. |
| Update Anti-Spoofing Level | Kiosk Scanner | Higher levels may increase false rejects but prevent spoofing. Requires user compliance. |
| Purge Database | All Modules | Clears logs & clips. Core profiles remain. **Backup first.** |
| Modify Master Passcode | Admin Gate, Security Settings | Invalidates existing admin sessions. Requires re-login. |
| Add Holiday/Weekend | Analytics, Compliance, Leave Manager | Excludes days from attendance rates. Adjusts "On Leave" vs "Absentee" classification. |
| Manual/Retro Log Entry | Analytics, Audit Trail, KPI Dashboard | Overrides auto-calculation. Triggers audit flag. Updates executive metrics instantly. |

**اردو:**  
| عمل | متاثرہ ماڈیولز | نتیجہ |
|---|---|---|
| شفٹ پالیسی تبدیل کریں | کیوسک، اینالیٹکس، لیو مینیجر، ریٹرو لاگ | لیٹ/وقت پر اسٹیٹس دوبارہ حساب۔ KPIs اپڈیٹ۔ تاریخی لاگز متاثر۔ |
| اینٹی اسپوفنگ لیول اپڈیٹ | کیوسک اسکینر | ہائی لیولز سیکیورٹی بڑھاتے ہیں مگر صارف کی ضرورت۔ |
| ڈیٹا بیس صاف کریں | تمام ماڈیولز | لاگز/کلپس حذف۔ پروفائلز محفوظ رہتے ہیں۔ **پہلے بیک اپ لیں۔** |
| ماسٹر پاس کوڈ تبدیل کریں | ایڈمن گیٹ، سیکیورٹی سیٹنگز | موجودہ سیشنز منسوخ۔ دوبارہ لاگ ان ضروری۔ |
| چھٹی/ویک اینڈ شامل کریں | اینالیٹکس، کمپلائنس، لیو مینیجر | حاضری ریٹس سے ایام خارج۔ "چھٹی" vs "غیر حاضری" کی درجہ بندی تبدیل۔ |
| مینوئل/ریٹرو لاگ | اینالیٹکس، آڈٹ ٹریل، KPI ڈیش بورڈ | خودکار حساب کو اوور رائیڈ۔ آڈٹ فلیگ۔ ایگزیکٹو میٹرکس فوری اپڈیٹ۔ |

---

## 🛠️ Technical Setup & Requirements | 🛠️ تکنیکی سیٹ اپ اور تقاضے
- **Browser:** Chrome 90+, Edge 90+, Firefox 88+ (WebRTC & LocalStorage required)  
- **Camera:** 720p+ webcam with autofocus (recommended for Level 1/2 liveness)  
- **Storage:** 500MB+ local disk for logs, motion clips, and JSON backups  
- **Network:** Optional. Fully functional offline. Syncs only when manually exported/imported  
- **Security:** All data encrypted locally. No cloud transmission. Passcode hashed in session storage.  
- **Performance:** Optimized for low-CPU kiosks. Disable browser extensions that block camera access.  

**اردو:**  
- **براؤزر:** کروم 90+، ایج 90+، فائر فاکس 88+ (WebRTC اور لوکل اسٹوریج ضروری)  
- **کیمرہ:** 720p+ ویب کیم، آٹو فوکس (لیول 1/2 کے لیے تجویز کردہ)  
- **اسٹوریج:** 500MB+ لوکل ڈسک (لاگز، موشن کلپس، بیک اپ کے لیے)  
- **نیٹ ورک:** اختیاری۔ مکمل آف لائن کام کرتا ہے۔ صرف مینوئل ایکسپورٹ/امپورٹ پر سنک ہوتا ہے  
- **سیکیورٹی:** تمام ڈیٹا لوکل اینکرپٹڈ۔ کوئی کلاؤڈ ٹرانسمیشن نہیں۔ پاس کوڈ سیشن اسٹوریج میں ہیشڈ۔  
- **کارکردگی:** کم CPU کیوسکس کے لیے آپٹیمائزڈ۔ کیمرہ بلاک کرنے والی ایکسٹینشنز بند کریں۔  

---

## 📞 Support & Maintenance | 📞 سپورٹ اور دیکھ بھال
- 🔄 **Regular Backup:** Export JSON weekly. Store externally.  
- 🧹 **Cache Management:** Purge indexes monthly if storage is constrained.  
- 🔑 **Password Rotation:** Change master passcode quarterly.  
- 📝 **Audit Review:** Export CSV monthly for compliance documentation.  
- 🆘 **Troubleshooting:** If scanner fails → Clear browser cache → Reload → Re-grant camera → Verify passcode.  

**اردو:**  
- 🔄 **باقاعدہ بیک اپ:** ہفتہ وار JSON ایکسپورٹ کریں۔ بیرونی اسٹوریج میں محفوظ رکھیں۔  
- 🧹 **کیش مینجمنٹ:** اسٹوریج کم ہو تو ماہانہ انڈیکسز صاف کریں۔  
- 🔑 **پاس ورڈ روٹیشن:** ہر سہ ماہی ماسٹر پاس کوڈ تبدیل کریں۔  
- 📝 **آڈٹ جائزہ:** کمپلائنس دستاویزات کے لیے ماہانہ CSV ایکسپورٹ کریں۔  
- 🆘 **ٹربل شوٹنگ:** اسکینر فیل ہو تو → براؤزر کیش کلین → ریلوڈ → کیمرہ اجازت دیں → پاس کوڈ تصدیق کریں۔  

---
✅ **Document Version:** 3.0 | **Last Updated:** Current Release | **Client Ready**  
✅ **یہ دستاویز مکمل، تازہ ترین اور کلائنٹ کے لیے تیار ہے۔**  

*For further customization, API integration, or enterprise deployment support, contact your solution provider.*  
*مزید کسٹمائزیشن، API انٹیگریشن یا انٹرپرائز ڈپلائمنٹ سپورٹ کے لیے اپنے سلوشن پرووائیڈر سے رابطہ کریں۔*