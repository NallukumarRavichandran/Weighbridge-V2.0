let port = null;
let reader = null;
let lineBuffer = "";
let lastWeight = null;
let sameCount = 0;
var weightFrozen = false;
let isConnecting = false;

/* ============================================================
   UNIVERSAL SCALE PROFILES MATRIX (2400 8N1 D300 FIRST)
   ============================================================ */
const UNIVERSAL_SCALE_PROFILES = [
    { name: "2400 8N1 (D300 / Standard)", baudRate: 2400, dataBits: 8, parity: "none", stopBits: 1 },
    { name: "9600 8N1 (Essae / Avery / Eagle)", baudRate: 9600, dataBits: 8, parity: "none", stopBits: 1 },
    { name: "2400 7E1 (Legacy Standard)", baudRate: 2400, dataBits: 7, parity: "even", stopBits: 1 },
    { name: "4800 8N1 (CAS / Contech)", baudRate: 4800, dataBits: 8, parity: "none", stopBits: 1 },
    { name: "1200 8N1 (Slow Continuous)", baudRate: 1200, dataBits: 8, parity: "none", stopBits: 1 },
    { name: "19200 8N1 (High Speed Digital)", baudRate: 19200, dataBits: 8, parity: "none", stopBits: 1 }
];

/* STATUS HELPER */
function setStatus(text, color = "#b91c1c") {
    const el = document.getElementById("connStatus");
    if (!el) return;
    el.innerText = text;
    el.style.color = color;
}

/* UPDATE LIVE WEIGHT ON SCREEN (PRESERVES MINUS SIGN) */
function updateLiveWeight(weight) {
    if (weight === lastWeight) {
        sameCount++;
    } else {
        sameCount = 0;
    }

    lastWeight = weight;

    const el = document.getElementById("live_weight");
    if (el) {
        if (el.tagName === "INPUT" || el.tagName === "TEXTAREA") {
            el.value = weight;
        } else {
            el.innerText = weight;
        }
    }
}

/* RETRIEVE SAVED WORKING PROFILE (DEFAULTS TO 2400 8N1 D300) */
function getSavedScaleProfile() {
    try {
        const saved = localStorage.getItem("scale_working_profile");
        return saved ? JSON.parse(saved) : UNIVERSAL_SCALE_PROFILES[0];
    } catch(e) {
        return UNIVERSAL_SCALE_PROFILES[0];
    }
}

/* SAVE WORKING PROFILE */
function saveWorkingScaleProfile(profile) {
    try {
        localStorage.setItem("scale_working_profile", JSON.stringify(profile));
        console.log("🔒 Locked Working Profile:", profile.name);
    } catch(e) {}
}

/* ROBUST CLEANUP OF SERIAL HANDLES WITH INDEPENDENT GUARDS */
async function cleanupSerialHandles() {
    if (reader) {
        try {
            await reader.cancel();
        } catch(e) {}
        try {
            reader.releaseLock();
        } catch(e) {}
        reader = null;
    }
    if (port) {
        try {
            await port.close();
        } catch(e) {}
        port = null;
    }
}

/* DIRECT SCALE CONNECTION ENGINE (INSTANT D300 BINDING) */
async function connectScaleDirect() {
    if (port && port.readable) {
        const profile = getSavedScaleProfile();
        setStatus(`CONNECTED (${profile.baudRate})`, "#16a34a");
        closeScaleDialog();
        return;
    }

    if (isConnecting) return;
    isConnecting = true;

    try {
        await cleanupSerialHandles();

        // Always allow the user to select/confirm the active COM Port
        try {
            port = await navigator.serial.requestPort();
        } catch (pickErr) {
            if (pickErr.name === "NotFoundError") {
                isConnecting = false;
                return;
            }
            throw pickErr;
        }

        if (!port) {
            isConnecting = false;
            return;
        }

        const profile = getSavedScaleProfile();
        console.log(`🔌 Opening Port at ${profile.baudRate} 8N1 (${profile.name})...`);

        await port.open({
            baudRate: profile.baudRate,
            dataBits: profile.dataBits || 8,
            stopBits: profile.stopBits || 1,
            parity: profile.parity || "none",
            flowControl: "none"
        });

        saveWorkingScaleProfile(profile);
        console.log(`✅ LOCKED PROFILE: ${profile.name}`);
        setStatus(`CONNECTED (${profile.baudRate})`, "#16a34a");
        closeScaleDialog();

        lineBuffer = "";
        readScale();

    } catch (err) {
        console.error("Connection Error:", err);
        setStatus("DISCONNECTED", "#b91c1c");
        let errMsg = err.message || "";
        if (errMsg.toLowerCase().includes("failed to open") || errMsg.toLowerCase().includes("access denied") || err.name === "NetworkError") {
            alert("⚠️ COM Port is Locked by Windows!\n\nPlease CLOSE or DISCONNECT HyperTerminal, then click CONNECT SCALE again.");
        } else {
            alert("Notice: Could not connect to scale (" + err.message + "). Please close any other serial software.");
        }
    } finally {
        isConnecting = false;
    }
}

function connectScale() {
    openScaleDialog();
}

/* DISCONNECT SCALE */
async function disconnectScale() {
    await cleanupSerialHandles();
    console.log("SCALE DISCONNECTED");
    setStatus("DISCONNECTED", "#b91c1c");
}

/* AUTO-RECONNECT ON PAGE LOAD / RELOAD WITH QUICK NON-BLOCKING RETRY */
async function autoReconnect(retryCount = 0) {
    if (port && port.readable) {
        const profile = getSavedScaleProfile();
        setStatus(`CONNECTED (${profile.baudRate})`, "#16a34a");
        return;
    }

    if (isConnecting) return;
    isConnecting = true;

    try {
        await cleanupSerialHandles();

        const ports = await navigator.serial.getPorts();
        if (ports.length > 0) {
            port = ports[0];
            const profile = getSavedScaleProfile();

            await port.open({
                baudRate: profile.baudRate,
                dataBits: profile.dataBits || 8,
                stopBits: profile.stopBits || 1,
                parity: profile.parity || "none",
                flowControl: "none"
            });

            lineBuffer = "";
            readScale();
            setStatus(`CONNECTED (${profile.baudRate})`, "#16a34a");
            console.log(`Scale Auto-Connected (${profile.baudRate} Baud)`);
        }
    } catch (e) {
        console.warn("Auto-reconnect attempt " + (retryCount + 1) + " note:", e.message);
        // Fast retry after 300ms if Windows serial driver was momentarily releasing handle
        if (retryCount < 2 && (e.message.includes("already") || e.message.includes("Failed to open") || e.name === "NetworkError")) {
            isConnecting = false;
            setTimeout(() => autoReconnect(retryCount + 1), 300);
            return;
        }
        if (!e.message.includes("already in progress") && !e.message.includes("already open")) {
            setStatus("DISCONNECTED", "#b91c1c");
        }
    } finally {
        isConnecting = false;
    }
}

/* READ SCALE STREAM (CONTINUOUS ASYNC READER) */
async function readScale() {
    const decoder = new TextDecoder();
    if (!port || !port.readable) return;

    try {
        reader = port.readable.getReader();
    } catch (err) {
        console.error("Failed to acquire reader:", err);
        return;
    }

    try {
        while (true) {
            const { value, done } = await reader.read();
            if (done) {
                console.log("Scale stream closed by host/port.");
                break;
            }

            const text = decoder.decode(value, { stream: true });
            lineBuffer += text;

            // Universal delimiter splitting (CR, LF, STX 0x02, ETX 0x03)
            let lines = lineBuffer.split(/[\r\n\x02\x03\x0D\x0A]+/);
            lineBuffer = lines.pop(); // Retain partial packet

            for (let line of lines) {
                processWeightLine(line);
            }
        }
    } catch (e) {
        console.error("Read Exception:", e);
        setStatus("DISCONNECTED", "#b91c1c");
    } finally {
        if (reader) {
            try {
                reader.releaseLock();
            } catch(e) {}
            reader = null;
        }
    }
}

/* UNIVERSAL WEIGHING INDICATOR PARSER (PRESERVES MINUS READINGS) */
function processWeightLine(line) {
    if (!line) return;
    // Strip unprintable control characters (STX 0x02, ETX 0x03, NUL, etc.)
    line = line.replace(/[\x00-\x1F\x7F-\x9F]/g, "").trim();
    if (!line) return;

    // Normalize multiple dashes (e.g. "--001445" -> "-001445")
    line = line.replace(/--+/g, "-");

    // 1. D300 / XK3190 Inverted Format (e.g. "=500000" or "=5.43210")
    if (line.startsWith("=") && line.length >= 6) {
        let isNeg = line.includes("-");
        let rawNum = line.substring(1).replace(/[-+]/g, "").trim();
        let reversed = rawNum.split("").reverse().join("");
        let matchRev = reversed.match(/\d*\.?\d+/);
        if (matchRev) {
            let w = parseFloat(matchRev[0]);
            if (isNeg) w = -w;
            if (!isNaN(w) && !window.weightFrozen) {
                updateLiveWeight(w);
                return;
            }
        }
    }

    // 2. Standard & Negative Decimal Formats ("-001445", "ST,GS,+00125.0kg", "wn00007.5kg", "-12500")
    let match = line.match(/[-+]?\d*\.?\d+/);
    if (match) {
        let rawWeight = parseFloat(match[0]);
        if (!window.weightFrozen && !isNaN(rawWeight)) {
            // Live minus sign preserved exactly as sent by the scale
            updateLiveWeight(rawWeight);
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