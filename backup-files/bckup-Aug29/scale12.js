let port = null;
let reader = null;
let lineBuffer = "";
let lastWeight = null;
let sameCount = 0;
var weightFrozen = false;
let isConnecting = false;

/* ============================================================
   EXACT D300 & UNIVERSAL SCALE PROFILES (2400 8N1 FIRST)
   ============================================================ */
const UNIVERSAL_SCALE_PROFILES = [
    { name: "2400 8N1 (D300 Exact Match)", baudRate: 2400, dataBits: 8, parity: "none", stopBits: 1 },
    { name: "9600 8N1 (Standard)", baudRate: 9600, dataBits: 8, parity: "none", stopBits: 1 },
    { name: "2400 7E1 (Legacy Even)", baudRate: 2400, dataBits: 7, parity: "even", stopBits: 1 },
    { name: "1200 8N1 (Slow Mode)", baudRate: 1200, dataBits: 8, parity: "none", stopBits: 1 },
    { name: "4800 8N1 (CAS)", baudRate: 4800, dataBits: 8, parity: "none", stopBits: 1 }
];

/* STATUS HELPER */
function setStatus(text, color = "#b91c1c") {
    const el = document.getElementById("connStatus");
    if (!el) return;
    el.innerText = text;
    el.style.color = color;
}

/* UPDATE LIVE WEIGHT ON SCREEN */
function updateLiveWeight(weight) {
    if (weight === lastWeight) {
        sameCount++;
    } else {
        sameCount = 0;
    }

    lastWeight = weight;

    // Display on live weight screen box
    const el = document.getElementById("live_weight");
    if (el) {
        if (el.tagName === "INPUT" || el.tagName === "TEXTAREA") {
            el.value = weight;
        } else {
            el.innerText = weight;
        }
    }
}

/* RETRIEVE WORKING PROFILE */
function getSavedScaleProfile() {
    try {
        const saved = localStorage.getItem("scale_working_profile");
        return saved ? JSON.parse(saved) : UNIVERSAL_SCALE_PROFILES[0]; // Defaults to 2400 8N1
    } catch(e) {
        return UNIVERSAL_SCALE_PROFILES[0];
    }
}

/* SAVE WORKING PROFILE */
function saveWorkingScaleProfile(profile) {
    try {
        localStorage.setItem("scale_working_profile", JSON.stringify(profile));
        console.log("🔒 Scale Locked to Profile:", profile.name);
    } catch(e) {}
}

/* CONNECT SCALE (DIRECT ACCESS TO COM3 AT 2400 8N1) */
async function connectScaleDirect() {
    if (port && port.readable) {
        setStatus("CONNECTED (2400)", "#16a34a");
        closeScaleDialog();
        return;
    }

    if (isConnecting) return;
    isConnecting = true;

    try {
        let ports = await navigator.serial.getPorts();

        if (ports.length > 0) {
            port = ports[0];
        } else {
            port = await navigator.serial.requestPort();
        }

        if (!port) {
            isConnecting = false;
            return;
        }

        await cleanupSerialHandles();

        // Exact match with AccessPort: 2400 Baud, 8 Data Bits, None Parity, 1 Stop Bit
        const profile = UNIVERSAL_SCALE_PROFILES[0];

        await port.open({
            baudRate: profile.baudRate,
            dataBits: profile.dataBits,
            stopBits: profile.stopBits,
            parity: profile.parity,
            flowControl: "none"
        });

        saveWorkingScaleProfile(profile);
        console.log("✅ D300 SCALE CONNECTED ON COM3 AT 2400 BAUD (8N1)");
        setStatus("CONNECTED (2400)", "#16a34a");
        closeScaleDialog();

        lineBuffer = "";
        readScale();

    } catch (err) {
        console.error("Connection Error:", err);
        setStatus("DISCONNECTED", "#b91c1c");
        alert("COM Port Notice: Make sure AccessPort is completely CLOSED! (" + err.message + ")");
    } finally {
        isConnecting = false;
    }
}

function connectScale() {
    openScaleDialog();
}

/* AUTO-RECONNECT ON PAGE RELOAD */
async function autoReconnect() {
    if (port && port.readable) {
        setStatus("CONNECTED (2400)", "#16a34a");
        return;
    }

    if (isConnecting) return;
    isConnecting = true;

    await cleanupSerialHandles();

    try {
        const ports = await navigator.serial.getPorts();
        if (ports.length > 0) {
            port = ports[0];
            const profile = UNIVERSAL_SCALE_PROFILES[0]; // 2400 8N1

            await port.open({
                baudRate: profile.baudRate,
                dataBits: profile.dataBits,
                stopBits: profile.stopBits,
                parity: profile.parity,
                flowControl: "none"
            });

            lineBuffer = "";
            readScale();
            setStatus("CONNECTED (2400)", "#16a34a");
            console.log("Scale Auto-Connected on COM3 (2400 Baud)");
        }
    } catch (e) {
        console.log("Auto-reconnect Notice:", e.message);
        if (!e.message.includes("already in progress") && !e.message.includes("already open")) {
            setStatus("DISCONNECTED", "#b91c1c");
        }
    } finally {
        isConnecting = false;
    }
}

/* CLEANUP HANDLES */
async function cleanupSerialHandles() {
    try {
        if (reader) {
            await reader.cancel();
            reader.releaseLock();
            reader = null;
        }
        if (port) {
            await port.close();
            port = null;
        }
    } catch(e) {}
}

/* DISCONNECT SCALE */
async function disconnectScale() {
    await cleanupSerialHandles();
    console.log("SCALE DISCONNECTED");
    setStatus("DISCONNECTED", "#b91c1c");
}

/* READ SCALE STREAM */
async function readScale() {
    const decoder = new TextDecoder();
    if (!port || !port.readable) return;
    reader = port.readable.getReader();

    try {
        while (true) {
            const { value, done } = await reader.read();
            if (done) break;

            const text = decoder.decode(value, { stream: true });
            console.log("SCALE STREAM:", text);

            lineBuffer += text;

            // Split across line breaks and D300 frame boundaries
            let lines = lineBuffer.split(/[\r\n\x02\x03\x0D\x0A]+/);
            lineBuffer = lines.pop(); // Keep partial packet

            for (let line of lines) {
                processWeightLine(line);
            }
        }
    } catch (e) {
        console.error("Read Exception:", e);
        setStatus("DISCONNECTED", "#b91c1c");
    } finally {
        if (reader) {
            reader.releaseLock();
            reader = null;
        }
    }
}

/* EXACT D300 WEIGHT PARSER */
function processWeightLine(line) {
    if (!line) return;
    line = line.trim();

    // Matches AccessPort stream: "-001445", "001445", "+001445", "1445.0"
    let match = line.match(/[-+]?\d*\.?\d+/);
    if (match) {
        let rawWeight = parseFloat(match[0]);
        
        // Convert to clean weight (handles negative tare offset or standard reading)
        if (!window.weightFrozen && !isNaN(rawWeight)) {
            let cleanWeight = Math.abs(rawWeight); // Display positive weight
            updateLiveWeight(cleanWeight);
        }
    }
}

/* DIALOG CONTROLS */
function openScaleDialog() {
    const dlg = document.getElementById("scaleDialog");
    if (dlg) dlg.style.display = "block";
}

function closeScaleDialog() {
    const dlg = document.getElementById("scaleDialog");
    if (dlg) dlg.style.display = "none";
}