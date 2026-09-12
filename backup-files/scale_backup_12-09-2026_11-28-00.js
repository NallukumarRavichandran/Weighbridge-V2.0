let port = null;
let reader = null;
let lineBuffer = "";
let lastWeight = null;
let sameCount = 0;
var weightFrozen = false;
let isConnecting = false;

/* ============================================================
   SCALE PROFILE (LOCKED TO 2400 8N1 AS PER HARDWARE INDICATOR)
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

/* UPDATE LIVE WEIGHT ON SCREEN (PRESERVES LIVE CONTINUOUS STREAM) */
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

/* RETRIEVE WORKING PROFILE (LOCKED TO 2400 8N1) */
function getSavedScaleProfile() {
    return UNIVERSAL_SCALE_PROFILES[0];
}

/* SAVE WORKING PROFILE */
function saveWorkingScaleProfile(profile) {
    try {
        localStorage.setItem("scale_working_profile", JSON.stringify(profile));
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

/* DIRECT SCALE CONNECTION ENGINE (CONNECT DIRECTLY AT 2400 8N1) */
async function connectScaleDirect() {
    if (port && port.readable) {
        setStatus("CONNECTED (2400)", "#16a34a");
        closeScaleDialog();
        return;
    }

    if (isConnecting) return;
    isConnecting = true;

    try {
        await cleanupSerialHandles();

        let ports = await navigator.serial.getPorts();
        if (ports.length > 0) {
            // Connect directly to the already-paired device without prompting
            port = ports[0];
        } else {
            // First time pairing: prompt user to select port
            try {
                port = await navigator.serial.requestPort();
            } catch (pickErr) {
                if (pickErr.name === "NotFoundError") {
                    isConnecting = false;
                    return;
                }
                throw pickErr;
            }
        }

        if (!port) {
            isConnecting = false;
            return;
        }

        console.log("🔌 Opening Port at 2400 8N1...");

        await port.open({
            baudRate: 2400,
            dataBits: 8,
            stopBits: 1,
            parity: "none",
            flowControl: "none"
        });

        setStatus("CONNECTED (2400)", "#16a34a");
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

/* AUTO-RECONNECT ON PAGE LOAD / RELOAD WITH PROGRESSIVE RETRIES (UP TO 5 ATTEMPTS) */
async function autoReconnect(retryCount = 0) {
    if (port && port.readable) {
        setStatus("CONNECTED (2400)", "#16a34a");
        return;
    }

    if (isConnecting) return;
    isConnecting = true;

    try {
        await cleanupSerialHandles();

        const ports = await navigator.serial.getPorts();
        if (ports.length > 0) {
            port = ports[0];

            await port.open({
                baudRate: 2400,
                dataBits: 8,
                stopBits: 1,
                parity: "none",
                flowControl: "none"
            });

            lineBuffer = "";
            readScale();
            setStatus("CONNECTED (2400)", "#16a34a");
            console.log("Scale Auto-Connected (2400 Baud)");
        }
    } catch (e) {
        console.warn("Auto-reconnect attempt " + (retryCount + 1) + " note:", e.message);
        // After save / page reload, progressively retry up to 5 times while driver frees handle
        if (retryCount < 5) {
            isConnecting = false;
            const delay = 400 + (retryCount * 350); // 400ms, 750ms, 1100ms, 1450ms, 1800ms
            setTimeout(() => autoReconnect(retryCount + 1), delay);
            return;
        }
        if (!e.message.includes("already in progress") && !e.message.includes("already open")) {
            setStatus("DISCONNECTED", "#b91c1c");
        }
    } finally {
        isConnecting = false;
    }
}

/* READ SCALE STREAM (CONTINUOUS ASYNC READER WITH AUTO-RECOVERY) */
async function readScale() {
    const decoder = new TextDecoder();
    if (!port || !port.readable) return;

    try {
        reader = port.readable.getReader();
    } catch (err) {
        console.error("Failed to acquire reader:", err);
        return;
    }

    let isStreamSynced = false;

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

            // Dynamic Synchronization Guard:
            // When stream starts, the very first segment before a delimiter is a partial fragment from mid-transmission.
            // Discard that initial fragment dynamically so only 100% complete packets are parsed.
            if (!isStreamSynced) {
                if (lines.length > 0) {
                    lines.shift(); // Discard the initial fragmented tail
                    isStreamSynced = true;
                } else {
                    continue;
                }
            }

            for (let line of lines) {
                processWeightLine(line);
            }
        }
    } catch (e) {
        console.warn("Scale stream read hiccup (auto-recovering):", e);
    } finally {
        if (reader) {
            try {
                reader.releaseLock();
            } catch(e) {}
            reader = null;
        }

        // Keep stream alive: if port is readable, immediately re-acquire reader; otherwise auto-reconnect
        if (port && port.readable) {
            setTimeout(readScale, 200);
        } else {
            setTimeout(() => autoReconnect(0), 500);
        }
    }
}

/* UNIVERSAL WEIGHING INDICATOR PARSER (PRESERVES MINUS READINGS & NEVER FREEZES LIVE WEIGHT) */
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
        let matchRev = reversed.match(/[-+]?\d+(?:\.\d+)?/);
        if (matchRev) {
            let w = parseFloat(matchRev[0]);
            if (isNeg) w = -w;
            if (!isNaN(w)) {
                updateLiveWeight(w);
                return;
            }
        }
    }

    // 2. Standard & Negative Decimal Formats ("-002210", "-000655", "000655", "ST,GS,+00125.0kg", "wn00007.5kg", "-12500")
    let match = line.match(/[-+]?\d+(?:\.\d+)?/);
    if (match) {
        let rawWeight = parseFloat(match[0]);
        if (!isNaN(rawWeight)) {
            // Live weight ALWAYS updates continuously - never frozen
            updateLiveWeight(rawWeight);
        }
    }
}

/* DIALOG CONTROLS (CLEAN DIRECT MODAL WITHOUT DROPDOWNS) */
function openScaleDialog() {
    const dlg = document.getElementById("scaleDialog");
    if (dlg) dlg.style.display = "block";
}

function closeScaleDialog() {
    const dlg = document.getElementById("scaleDialog");
    if (dlg) dlg.style.display = "none";
}