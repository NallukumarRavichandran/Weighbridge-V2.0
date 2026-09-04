# ⚖️ Weighbridge 2.0 (wb2.0) — Industrial Weighment & Logistics Automation Suite

[![Version](https://img.shields.io/badge/version-2.0.0-blue.svg?style=for-the-badge)](https://github.com)
[![Platform](https://img.shields.io/badge/platform-XAMPP%20%7C%20Windows-orange.svg?style=for-the-badge)](https://www.apachefriends.org/)
[![Hardware](https://img.shields.io/badge/hardware-W3C%20Web%20Serial%20API-green.svg?style=for-the-badge)](https://developer.mozilla.org/en-US/docs/Web/API/Web_Serial_API)
[![Offline](https://img.shields.io/badge/architecture-100%25%20Offline--First-success.svg?style=for-the-badge)](https://github.com)

**Weighbridge 2.0 (`wb2.0`)** is an industrial-grade, offline-first weighment recording, vehicle tracking, and automated slip dispatch system engineered for modern manufacturing plants, logistics hubs, mining yards, and toll infrastructure.

Designed to operate seamlessly on edge workstations without requiring continuous internet or cloud dependency, **wb2.0** combines low-latency browser-hardware interfacing via the **W3C Web Serial API**, multi-channel CCTV snapshot capture, dynamic Entity-Attribute-Value (EAV) reporting, and a robust CSS Paged Media slip printing engine.

---

## 📑 Table of Contents

- [🏛️ Section I: Technical Specification & Architecture](#-section-i-technical-specification--architecture)
  - [1. Runtime Stack & Infrastructure](#1-runtime-stack--infrastructure)
  - [2. System Architecture Flow](#2-system-architecture-flow)
  - [3. Hardware Interfacing & Web Serial Protocol](#3-hardware-interfacing--web-serial-protocol)
  - [4. Multi-Channel CCTV Vision Engine](#4-multi-channel-cctv-vision-engine)
  - [5. Database Topology & EAV Field Engine](#5-database-topology--eav-field-engine)
  - [6. Offline Print Routing & Layout Engine](#6-offline-print-routing--layout-engine)
  - [7. Transaction Lifecycle & ACID Guarantee](#7-transaction-lifecycle--acid-guarantee)
  - [8. Enterprise Security & Resilience](#8-enterprise-security--resilience)
  - [9. Kiosk Bootloader & Supervisor Automation](#9-kiosk-bootloader--supervisor-automation)
- [📘 Section II: Operator & User Support Manual](#-section-ii-operator--user-support-manual)
  - [1. Daily Startup Checklist](#1-daily-startup-checklist)
  - [2. Scale Connection & Status Monitoring](#2-scale-connection--status-monitoring)
  - [3. Performing First Weighment (Gross or Tare)](#3-performing-first-weighment-gross-or-tare)
  - [4. Performing Second Weighment (Net Calculation)](#4-performing-second-weighment-net-calculation)
  - [5. Slip Printing & Re-Print Workflow](#5-slip-printing--re-print-workflow)
  - [6. Camera View & Aspect Ratio Toggling](#6-camera-view--aspect-ratio-toggling)
  - [7. Reports & Data Auditing](#7-reports--data-auditing)
  - [8. Operator Troubleshooting & Self-Service Matrix](#8-operator-troubleshooting--self-service-matrix)
- [🚀 Section III: Installation & Offline Deployment](#-section-iii-installation--offline-deployment)

---

# 🏛️ Section I: Technical Specification & Architecture

## 1. Runtime Stack & Infrastructure

| Subsystem | Technology | Architectural Role |
| :--- | :--- | :--- |
| **Server Host** | Apache HTTP Server 2.4 (XAMPP Suite) | Local HTTP request daemon running on port 80 / localhost |
| **Backend Core** | PHP 8.1+ (Procedural + OOP Services) | Request dispatch, SQL transaction handling, cURL cloud sync |
| **Relational DB** | MariaDB 10.4+ / MySQL 8.0 | ACID-compliant storage (`InnoDB`), `utf8mb4_unicode_ci` |
| **Serial Bus** | W3C Web Serial API (`navigator.serial`) | Direct USB/RS-232 serial indicator communication without plugins |
| **Surveillance** | HTML5 Canvas / HTTP Snapshot Proxies | Video stream polling, multi-cam capture serialization |
| **Print Subsystem**| CSS Paged Media `@page` Engine | Pixel-accurate thermal, dot-matrix, and laser receipt formatting |
| **Desktop Wrapper**| VBScript (`.vbs`) + Shell (`.bat`) | Silent background process manager with Edge/Chrome kiosk mode |

---

## 2. System Architecture Flow

```
┌─────────────────────────┐          RS-232 / USB UART
│ Digital Weight Indicator│ ─────────────────────────────────┐
│  (D300 / XK3190 / etc.) │                                  │
└─────────────────────────┘                                  ▼
                                                ┌───────────────────────────┐
┌─────────────────────────┐                     │  W3C Web Serial Worker    │
│ CCTV Security Cameras   │ ──HTTP MJPEG───►   │       (scale.js)          │
│ (IP Cameras 1 to 4)     │                     └─────────────┬─────────────┘
└─────────────────────────┘                                   │ Live Stream
                                                              ▼
┌───────────────────────────────────────────────────────────────────────────┐
│                     Operator Web Interface (mainform.php)                 │
│  - Real-time Weight Display       - Two-Step Gross/Tare Capture           │
│  - Spacebar Instant Capture       - Live 2x2 Camera Security Grid         │
│  - Dynamic EAV Custom Metadata    - Historical Vehicle Lookup             │
└─────────────────────────────────────┬─────────────────────────────────────┘
                                      │ POST Payload
                                      ▼
                        ┌───────────────────────────┐
                        │ Transaction Controller    │
                        │      (saveform.php)       │
                        └─────────────┬─────────────┘
                                      │
                 ┌────────────────────┴────────────────────┐
                 ▼                                         ▼
   ┌───────────────────────────┐             ┌───────────────────────────┐
   │ Local MariaDB Database    │             │ Cloud Sync Engine         │
   │ (weighments, field_values)│             │ (APACS REST JSON Gateway) │
   └─────────────┬─────────────┘             └───────────────────────────┘
                 │ Fetch Complete Record
                 ▼
   ┌───────────────────────────┐
   │ Print Routing Engine      │
   │     (print_slip.php)      │
   └─────────────┬─────────────┘
                 │
                 ├───────────────────────────────┐
                 ▼ (Default / Offline)           ▼ (Custom)
   ┌───────────────────────────┐   ┌───────────────────────────┐
   │ Static Default Template   │   │ Cached Template Catalog   │
   │ (templates/default_slip)  │   │ (templates/*.php)         │
   └─────────────┬─────────────┘   └─────────────┬─────────────┘
                 │                               │
                 └───────────────┬───────────────┘
                                 ▼
                   ┌───────────────────────────┐
                   │ Physical Print Device     │
                   │ (Dot Matrix / Thermal / A4│
                   └───────────────────────────┘
```

---

## 3. Hardware Interfacing & Web Serial Protocol

### 3.1 Web Serial Architecture (`scale.js`)
Unlike legacy weighbridge software relying on ActiveX, Java NPAPI applets, or fragile background `.exe` daemons, **wb2.0** interfaces directly with physical serial hardware using the W3C Web Serial specification.

```javascript
// Hardware Connection Handshake (scale.js)
port = await navigator.serial.requestPort();
await port.open({
    baudRate: 2400,
    dataBits: 8,
    stopBits: 1,
    parity: "none",
    flowControl: "none"
});
```

### 3.2 Supported Scale Profiles
The serial engine includes built-in configurations for industrial weighbridge indicators across India and global markets:

1. **D300 / XK3190 (Standard Default)** — `2400 8N1` (Inverted/Direct string frame)
2. **Essae / Avery / Eagle** — `9600 8N1`
3. **Legacy Standard** — `2400 7E1`
4. **CAS / Contech** — `4800 8N1`
5. **Slow Continuous Indicators** — `1200 8N1`
6. **High-Speed Digital Load Cells** — `19200 8N1`

### 3.3 Frame Delimiting & Normalization Engine
Indicators stream raw ASCII at 2Hz–10Hz. The stream reader parses multi-byte frame boundaries:
- **Separators:** Strips `\r`, `\n`, `\x02` (STX), `\x03` (ETX).
- **XK3190 / D300 Format:** Slices inverted frames beginning with `=`:
  ```
  Raw Input:   =500000\r\n
  Normalized:  Reversed to 000005 -> 500.0 kg
  ```
- **Minus Sign Preservation:** Correctly detects and preserves live tare deductions (e.g. `-001445` -> `-1445 kg`).

### 3.4 Fault-Tolerant Reconnect Lifecycle
- **Isolated Exception Guards (`cleanupSerialHandles`):** Prevents lock contention by wrapping `reader.cancel()`, `reader.releaseLock()`, and `port.close()` in isolated `try/catch` boundaries.
- **Page Reload Reconnection:** Initiates auto-reconnect within 250ms with an intelligent non-blocking retry loop to accommodate Windows UART handle transitions.

---

## 4. Multi-Channel CCTV Vision Engine

- **Four-Channel Grid:** Supports up to 4 concurrent camera streams positioned at Weighbridge Front, Rear, Top Load, and Driver Window.
- **Snapshot Integration:** When the transaction is saved, visual evidence frames are pulled from live feeds, converted into JPEG artifacts, and bound to the Slip ID in `captures/`.
- **Dynamic CSS Layout:** Auto-adjusts layout (`1x1`, `2x1`, or `2x2`) depending on the number of active cameras.
- **Aspect Ratio Toggling:** Operators can toggle between **Full Aspect** (`object-fit: contain`) and **Crop Fill** (`object-fit: cover`) directly from the UI.

---

## 5. Database Topology & EAV Field Engine

### 5.1 Relational Schema
```sql
weighments             -- Master transaction table (Slip No, Vehicle, Gross, Tare, Net, Dates, Camera Snapshots)
sweighment             -- Pending first-weighment queue (Holds vehicles currently on site)
companies              -- Enterprise entity configuration, address, GSTIN, header parameters
users                  -- Operator authentication, role-based credentials (Admin, Operator)
weighment_fields       -- EAV Dynamic Field definitions (Text, Numeric, Dropdown)
weighment_field_values -- EAV Key-Value data mapped per Slip No
```

### 5.2 Dynamic EAV (Entity-Attribute-Value) Architecture
Enables administrators to dynamically inject industry-specific parameters without modifying database tables or writing code:
- Party Name, Transporter, Material Grade, Moisture Percentage, Mine Code, Delivery Challan No.
- Automatically reflected in input forms, validation pipelines, and print slip templates.

---

## 6. Offline Print Routing & Layout Engine

### 6.1 Dual-Mode Routing Protocol (`print_slip.php`)
```php
// Offline Resilience Protocol
$customTemplate = "templates/" . $active_layout . ".php";
$defaultTemplate = "templates/default_slip.php";

if (!empty($active_layout) && file_exists($customTemplate)) {
    include($customTemplate);
} else {
    include($defaultTemplate); // Guaranteed 100% Offline Fallback
}
```

- **Offline Independence:** `templates/default_slip.php` is permanently anchored inside the repository. The system prints slips even during complete WAN / Cloud disconnection.
- **Template Synchronization:** Integrated cloud catalog sync pulls custom layouts when internet is restored without overwriting local defaults.

---

## 7. Transaction Lifecycle & ACID Guarantee

1. **First Weighment (Gross or Tare):**
   - Saves vehicle identifier, initial weight reading, date/time, and operator ID into `sweighment`.
   - Generates unique transaction Slip Number.
2. **Second Weighment (Inversion & Net Generation):**
   - Matches returning vehicle against open `sweighment` records.
   - Calculates Net Weight:
     $$\text{Net Weight} = |\text{Gross Weight} - \text{Tare Weight}|$$
   - Executes atomic commit: transfers completed record to `weighments` and removes active vehicle from `sweighment`.
3. **Receipt Dispatch:** Triggers browser print spooler directly targeting hardware receipt printers.

---

## 8. Enterprise Security & Resilience

- **SQL Injection Defense:** 100% parameterized queries via `mysqli::prepare()` and `bind_param()`.
- **Context-Aware XSS Sanitization:** All display outputs escape HTML entities (`htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`).
- **Role-Based Access Control (RBAC):** Admin privilege checks protect system configuration, master database backups, and slip resetting.
- **Automated Timestamped Backups:** One-click automated database dump generation (`backups/weighbridge_backup_{timestamp}.sql`).

---

## 9. Kiosk Bootloader & Supervisor Automation

- **`start_weighbridge.bat`:** Validates XAMPP process state (`httpd.exe`, `mysqld.exe`) and boots services if dormant.
- **`Weighbridge_App.vbs`:** Boots the application with `WindowStyle = 0` (hidden window), launching Microsoft Edge in kiosk application mode:
  ```cmd
  msedge.exe --app=http://localhost/weighbridge-printS/ --start-maximized --disable-http-cache
  ```

---

# 📘 Section II: Operator & User Support Manual

## 1. Daily Startup Checklist

1. Turn on the Weighbridge Digital Indicator display unit.
2. Ensure the USB-to-Serial cable is securely connected to the workstation.
3. Power on workstation and double-click the **Weighbridge App** desktop icon.
4. Edge launches directly into the Weighment Recording System.

---

## 2. Scale Connection & Status Monitoring

In the top navigation bar, locate the **Scale ▾** dropdown menu:

| Visual Status | Indicator State | Meaning & Action Required |
| :--- | :--- | :--- |
| 🟢 **CONNECTED (2400)** | Healthy | Scale is actively communicating. Live weight displays in the green box. |
| 🟡 **SCANNING / CONNECTING** | In Progress | Serial handshake is synchronizing. Wait 1–2 seconds. |
| 🔴 **DISCONNECTED** | Offline | Port closed. Click **Scale ▾ -> 🔌 Connect Port** to establish connection. |

### Establishing Connection:
1. Click **Scale ▾ -> 🔌 Connect Port**.
2. A popup window appears: click **CONNECT SCALE**.
3. Select your Weighbridge COM Port (e.g. `USB-SERIAL CH340 (COM3)`) and click **Connect**.
4. The big black box will illuminate with live green digital numerals.

---

## 3. Performing First Weighment (Gross or Tare)

1. Verify the truck is parked fully and centrally on the weighbridge platform.
2. Ensure the live weight reading has stabilized.
3. Enter the **Vehicle Number** (e.g., `TN01AB1234`).
4. Select **GROSS** (for loaded vehicles entering) or **TARE** (for empty vehicles entering).
5. Fill in party name, material, and required custom fields.
6. Press the **SPACEBAR** on your keyboard (or click **RECORD WEIGHT**).
7. Click **SAVE** (or press Alt + S). The slip prints immediately and the first weight is archived.

---

## 4. Performing Second Weighment (Net Calculation)

When the vehicle returns to complete its weighment cycle:
1. Type the vehicle number into the **VEHICLE NO** field.
2. A list of pending vehicles appears. Click on the matching vehicle record.
3. The system automatically:
   - Locks the opposite weight category (TARE if first was GROSS).
   - Fills in the First Weight, Date, and Time into the history fields.
4. Press **SPACEBAR** (or click **SECOND WEIGHMENT**).
5. The **NET Weight** is calculated automatically.
6. Click **SAVE**. The final Weighment Slip prints with Gross, Tare, and Net figures.

---

## 5. Slip Printing & Re-Print Workflow

- **Automatic Printing:** Upon clicking **SAVE**, the print slip modal opens automatically.
- **Re-Print Historical Slip:**
  1. Click **RE-PRINT** on the main screen (or access **Reports -> Pending / Vehiclewise Report**).
  2. Enter the **Slip Number** or **Vehicle Number**.
  3. The print preview opens with exact duplicate copies marked for audit verification.

---

## 6. Camera View & Aspect Ratio Toggling

- Live cameras display in the bottom grid.
- **Aspect Ratio Button:** Click **FULL / CROP** on any camera card:
  - **CROP:** Fills the box completely for quick vehicle alignment.
  - **FULL:** Shows complete camera frame including license plate and weighbridge boundary edges.
- If a camera feed shows **OFFLINE**, verify the CCTV network switch and camera IP power.

---

## 7. Reports & Data Auditing

From the top navigation bar:
- **Reports -> Vehiclewise Report:** Filter by vehicle number and date range.
- **Reports -> Pending Report:** Lists vehicles currently inside the premises that have not completed their second weighment.
- **Reports -> Dynamic Field Report:** Generate itemized reports grouped by Customer, Material, or Transporter.

---

## 8. Operator Troubleshooting & Self-Service Matrix

| Symptom | Root Cause | Operator Solution |
| :--- | :--- | :--- |
| **Weight Box shows blank / 0** | Serial connection closed or physical cable loose | 1. Check cable connection to PC.<br>2. Click **Scale ▾ -> 🔁 Reconnect**.<br>3. If still offline, select **🔌 Connect Port** and reselect the COM port. |
| **"Failed to open serial port" error** | Another program is using the COM port | Close any serial monitor software (AccessPort, PuTTY, Arduino IDE) and click **Reconnect**. |
| **Camera card shows "OFFLINE"** | IP camera unreachable over local network | Check camera PoE switch and Ethernet cable. Open camera IP in browser to verify camera power. |
| **Slip print layout misaligned** | Printer driver paper size mismatch | In the print preview window, verify **Paper Size** is set to **A4** or **Continuous (Roll)** and **Scale** is set to **Default (100%)**. |
| **Vehicle duplicate entry warning** | Vehicle has an incomplete weighment in system | Check **Reports -> Pending Report**. Complete the second weighment for that vehicle or cancel the pending slip. |

---

# 🚀 Section III: Installation & Offline Deployment

### Prerequisites
- **Operating System:** Windows 10 / 11 / Windows Server (64-bit)
- **Web Stack:** XAMPP (Apache 2.4, MariaDB 10.4, PHP 8.1+)
- **Browser:** Microsoft Edge 89+ or Google Chrome 89+ (with Web Serial enabled)

### Step-by-Step Deployment
1. **Clone or Copy Repository:**
   Place the project folder inside XAMPP root:
   ```
   C:\xampp\htdocs\weighbridge-printS
   ```
2. **Database Provisioning:**
   - Launch XAMPP Control Panel and start **Apache** and **MySQL**.
   - Open phpMyAdmin (`http://localhost/phpmyadmin/`).
   - Create database named `weighbridge_db`.
   - Import `BackupAug2026.sql` (or latest database snapshot in `backups/`).
3. **Launch Desktop Kiosk:**
   Double click `Weighbridge_App.vbs` or `start_weighbridge.bat`.
4. **Access in Browser:**
   ```
   http://localhost/weighbridge-printS/
   ```

---

## 📄 License & Attribution
Developed and maintained by **Giri Brothers Weighbridge Systems**. All rights reserved.
For technical support and field maintenance, refer to your local service agreement.
