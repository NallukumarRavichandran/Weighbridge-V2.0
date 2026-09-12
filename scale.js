let port = null;
let reader = null;
let lineBuffer = "";
let lastWeight = null;
let sameCount = 0;
var weightFrozen = false;
let isConnecting = false;
let isReading = false;
let lastPacketTime = Date.now();
let lastLoggedWeight = null;

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

/* UPDATE LIVE WEIGHT ON SCREEN (FLICKER-FREE HIGH-PERFORMANCE DOM RENDERER) */
let pendingWeightAnimFrame = null;
function updateLiveWeight(weight) {
    if (weight === lastWeight) {
        sameCount++;
    } else {
        sameCount = 0;
    }

    lastWeight = weight;

    // Use requestAnimationFrame to batch DOM repaints to monitor refresh rate (60Hz), eliminating DOM layout thrashing & visual flicker
    if (pendingWeightAnimFrame === null) {
        pendingWeightAnimFrame = requestAnimationFrame(() => {
            pendingWeightAnimFrame = null;
            const el = document.getElementById("live_weight");
            if (el) {
                const strVal = String(lastWeight);
                if (el.tagName === "INPUT" || el.tagName === "TEXTAREA") {
                    if (el.value !== strVal) el.value = strVal;
                } else {
                    if (el.textContent !== strVal) el.textContent = strVal;
                }
            }
        });
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
    isReading = false;
}

/* DIRECT SCALE CONNECTION ENGINE (CONNECT DIRECTLY AT 2400 8N1) */
async function connectScaleDirect() {
    if (isReading || (port && port.readable)) {
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
            port = ports[0];
        } else {
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
        lastPacketTime = Date.now();
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
    if (isReading || (port && port.readable)) {
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
            lastPacketTime = Date.now();
            setStatus("CONNECTED (2400)", "#16a34a");
            console.log("Scale Auto-Connected (2400 Baud)");
            readScale();
        }
    } catch (e) {
        console.warn("Auto-reconnect attempt " + (retryCount + 1) + " note:", e.message);
        if (retryCount < 5) {
            isConnecting = false;
            const delay = 600 + (retryCount * 400); // 600ms, 1000ms, 1400ms, 1800ms
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

/* READ SCALE STREAM (CONTINUOUS ASYNC READER WITH SINGLE-OWNERSHIP & AUTO-RECOVERY) */
async function readScale() {
    if (isReading) return; // Prevent concurrent reader collision
    if (!port || !port.readable) {
        autoReconnect(0);
        return;
    }

    isReading = true;
    const decoder = new TextDecoder();

    try {
        reader = port.readable.getReader();
    } catch (err) {
        console.error("Failed to acquire reader, resetting connection:", err);
        isReading = false;
        await cleanupSerialHandles();
        setTimeout(() => autoReconnect(0), 1000);
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

            lastPacketTime = Date.now();
            const text = decoder.decode(value, { stream: true });
            lineBuffer += text;

            // Universal Dynamic Line Framer (100% Identical to HyperTerminal Line Framing):
            // Buffers incoming byte stream and extracts complete lines delimited by CR (\r), LF (\n), or CR+LF.
            // Completely eliminates partial packet slicing, STX sign-stripping, and zero-flickering.
            while (true) {
                // If STX (0x02) exists in buffer, discard any leading corrupted noise bytes before it
                let stxIdx = lineBuffer.indexOf('\x02');
                if (stxIdx > 0) {
                    lineBuffer = lineBuffer.substring(stxIdx);
                }

                // Look for packet termination delimiter: \r, \n, or \x03 (ETX)
                let newlineIdx = lineBuffer.search(/[\r\n\x03]/);
                if (newlineIdx === -1) {
                    // Prevent memory overflow on noisy/non-terminated stream
                    if (lineBuffer.length > 256) {
                        lineBuffer = lineBuffer.substring(lineBuffer.length - 64);
                    }
                    break;
                }

                let packet = lineBuffer.substring(0, newlineIdx);
                // Advance past all contiguous line delimiters (\r, \n, ETX \x03, STX \x02, NUL \x00)
                lineBuffer = lineBuffer.substring(newlineIdx).replace(/^[\r\n\x00\x02\x03]+/, '');

                // Discard first partial packet upon connection so stream syncs cleanly
                if (!isStreamSynced) {
                    isStreamSynced = true;
                    continue;
                }

                if (packet.trim().length >= 2) {
                    processWeightLine(packet);
                }
            }
        }
    } catch (e) {
        console.warn("Scale stream read hiccup (auto-recovering):", e.message || e);
    } finally {
        isReading = false;
        if (reader) {
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

        // Clean auto-recovery with progressive pause to allow driver buffers to flush
        setTimeout(() => autoReconnect(0), 1000);
    }
}

/* UNIVERSAL WEIGHING INDICATOR PARSER (100% FAITHFUL TO PHYSICAL INDICATOR DISPLAY) */
function processWeightLine(line) {
    if (!line) return;
    // Strip unprintable control characters without altering minus sign or digits
    line = line.replace(/[\x00-\x1F\x7F-\x9F]/g, "").trim();
    if (!line || line.length < 2) return;

    // Reject standalone status bytes or isolated 1-2 digit codes (e.g. "00", "01", "40", "0", "ST", "US")
    // A legitimate weighbridge reading ALWAYS has at least 3 digits or a formatted indicator prefix
    if (/^[0-9]{1,2}$/.test(line)) {
        return;
    }

    // Normalize multiple dashes (e.g. "--001445" -> "-001445")
    line = line.replace(/--+/g, "-");

    // 1. D300 / XK3190 Inverted Format (e.g. "=500000" or "=5.43210" or "=540000-")
    if (line.startsWith("=") && line.length >= 6) {
        let isNeg = line.includes("-");
        let rawNum = line.substring(1).replace(/[-+]/g, "").trim();
        let reversed = rawNum.split("").reverse().join("");
        let matchRev = reversed.match(/[-+]?\d+(?:\.\d+)?/);
        if (matchRev) {
            let w = parseFloat(matchRev[0]);
            if (isNeg) w = -w;
            if (!isNaN(w)) {
                if (w === 0) w = 0;
                if (w !== lastLoggedWeight) {
                    console.log("⚖️ Scale Live Weight:", w, "kg | Raw:", JSON.stringify(line));
                    lastLoggedWeight = w;
                }
                updateLiveWeight(w);
                return;
            }
        }
    }

    // 2. Standard & Negative Decimal Formats ("-002210", "-000655", "-  2085", "002085", "ST,GS,+00125.0kg", "wn00007.5kg", "-12500")
    // Prioritize the actual primary weight number (requiring at least 3 digits or decimal, ignoring 1-2 digit channel/status prefixes)
    let match = line.match(/[-+]?\s*\d{3,}(?:\.\d+)?/);
    if (!match) {
        // Fallback for smaller values (e.g. "0", "10", "-50") only if it is the sole numeric content
        match = line.match(/^[^0-9]*([-+]?\s*\d+(?:\.\d+)?)[^0-9]*$/);
        if (match) {
            match[0] = match[1];
        }
    }

    if (match) {
        let cleanNum = match[0].replace(/\s+/g, "");
        let rawWeight = parseFloat(cleanNum);
        if (!isNaN(rawWeight)) {
            if (rawWeight === 0) rawWeight = 0;
            if (rawWeight !== lastLoggedWeight) {
                console.log("⚖️ Scale Live Weight:", rawWeight, "kg | Raw:", JSON.stringify(line));
                lastLoggedWeight = rawWeight;
            }
            // Live weight ALWAYS updates continuously - exactly matching the indicator display
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

/* CLEAN FOREGROUND AUTO-RESUME & STARTUP HOOKS */
document.addEventListener("visibilitychange", function() {
    if (document.visibilityState === "visible") {
        if (!isReading && (!port || !port.readable)) {
            console.log("Tab returned to focus - initiating scale auto-reconnect...");
            autoReconnect(0);
        }
    }
});

// Auto-connect scale immediately on page load/reload
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => {
        autoReconnect(0);
    });
} else {
    autoReconnect(0);
}

window.addEventListener("load", () => {
    if (!isReading && (!port || !port.readable)) {
        autoReconnect(0);
    }
});

// 🛡️ CONTINUOUS SERIAL HEALTH WATCHDOG:
// If connected but no packets arrive for > 4 seconds (e.g. driver stall or USB glitch), auto-recover seamlessly!
setInterval(() => {
    if (isReading && port && port.readable) {
        if (Date.now() - lastPacketTime > 4000) {
            console.warn("⚠️ Scale packet stream paused (>4s). Auto-recovering connection...");
            cleanupSerialHandles().then(() => autoReconnect(0));
        }
    }
}, 3500);