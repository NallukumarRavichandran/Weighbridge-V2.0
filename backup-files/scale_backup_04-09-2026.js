let port = null;
let reader = null;
let lineBuffer = "";
let lastWeight = null;
let sameCount = 0;
var weightFrozen = false;
let isConnecting = false;

/* ============================================================
   UNIVERSAL SCALE PROFILES MATRIX (COVERS ALL BRANDS IN INDIA)
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

/* RETRIEVE SAVED WORKING PROFILE */
function getSavedScaleProfile() {
    try {
        const saved = localStorage.getItem("scale_working_profile");
        return saved ? JSON.parse(saved) : null;
    } catch(e) {
        return null;
    }
}

/* SAVE WORKING PROFILE */
function saveWorkingScaleProfile(profile) {
    try {
        localStorage.setItem("scale_working_profile", JSON.stringify(profile));
        console.log("🔒 Locked Working Profile:", profile.name);
    } catch(e) {}
}

/* PROBE A PROFILE TO VERIFY VALID PACKET STREAM */
async function probeSerialProfile(targetPort, profile, timeoutMs = 450) {
    let probeReader = null;
    let accumulatedText = "";
    const decoder = new TextDecoder();

    try {
        await targetPort.open({
            baudRate: profile.baudRate,
            dataBits: profile.dataBits || 8,
            stopBits: profile.stopBits || 1,
            parity: profile.parity || "none",
            flowControl: "none"
        });

        probeReader = targetPort.readable.getReader();

        const readPromise = (async () => {
            while (true) {
                const { value, done } = await probeReader.read();
                if (done) break;
                accumulatedText += decoder.decode(value, { stream: true });

                if (accumulatedText.match(/[-+]?\d*\.?\d+/) && !accumulatedText.includes("\uFFFD")) {
                    return true;
                }
            }
            return false;
        })();

        const timeoutPromise = new Promise(resolve => setTimeout(() => resolve(false), timeoutMs));
        const isValid = await Promise.race([readPromise, timeoutPromise]);

        try {
            await probeReader.cancel();
            probeReader.releaseLock();
        } catch(e) {}

        if (isValid) {
            return { success: true, text: accumulatedText };
        } else {
            await targetPort.close();
            return { success: false };
        }

    } catch (err) {
        if (probeReader) {
            try { await probeReader.cancel(); probeReader.releaseLock(); } catch(e) {}
        }
        try { await targetPort.close(); } catch(e) {}
        return { success: false };
    }
}

/* UNIVERSAL CONNECT SCALE ENGINE */
async function connectScaleDirect() {
    if (port && port.readable) {
        setStatus("CONNECTED", "#16a34a");
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

        setStatus("SCANNING...", "#f59e0b");
        console.log("🔍 Scanning Weighbridge Indicator across Universal Profiles...");

        let workingProfile = null;
        const savedProfile = getSavedScaleProfile();

        // 1. Try saved profile first for instant sub-50ms connect
        if (savedProfile) {
            const result = await probeSerialProfile(port, savedProfile, 400);
            if (result.success) {
                workingProfile = savedProfile;
            }
        }

        // 2. If saved profile fails or new machine, scan matrix
        if (!workingProfile) {
            for (let profile of UNIVERSAL_SCALE_PROFILES) {
                console.log(`Testing: ${profile.name}...`);
                const result = await probeSerialProfile(port, profile, 400);
                
                if (result.success) {
                    workingProfile = profile;
                    saveWorkingScaleProfile(profile);
                    break;
                }
            }
        }

        // 3. Fallback to 2400 8N1 (D300)
        if (!workingProfile) {
            workingProfile = UNIVERSAL_SCALE_PROFILES[0];
            await port.open({
                baudRate: workingProfile.baudRate,
                dataBits: workingProfile.dataBits,
                stopBits: workingProfile.stopBits,
                parity: workingProfile.parity,
                flowControl: "none"
            });
        } else if (!port.readable) {
            await port.open({
                baudRate: workingProfile.baudRate,
                dataBits: workingProfile.dataBits,
                stopBits: workingProfile.stopBits,
                parity: workingProfile.parity,
                flowControl: "none"
            });
        }

        console.log(`✅ LOCKED PROFILE: ${workingProfile.name}`);
        setStatus(`CONNECTED (${workingProfile.baudRate})`, "#16a34a");
        closeScaleDialog();

        lineBuffer = "";
        readScale();

    } catch (err) {
        console.error("Connection Error:", err);
        setStatus("DISCONNECTED", "#b91c1c");
        alert("Notice: Please close any serial monitor (AccessPort) before connecting! (" + err.message + ")");
    } finally {
        isConnecting = false;
    }
}

function connectScale() {
    openScaleDialog();
}

/* AUTO-RECONNECT ON PAGE LOAD */
async function autoReconnect() {
    if (port && port.readable) {
        setStatus("CONNECTED", "#16a34a");
        return;
    }

    if (isConnecting) return;
    isConnecting = true;

    await cleanupSerialHandles();

    try {
        const ports = await navigator.serial.getPorts();
        if (ports.length > 0) {
            port = ports[0];
            const profile = getSavedScaleProfile() || UNIVERSAL_SCALE_PROFILES[0];

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

/* READ SCALE STREAM (UNIVERSAL MULTI-DELIMITER SLICER) */
async function readScale() {
    const decoder = new TextDecoder();
    if (!port || !port.readable) return;
    reader = port.readable.getReader();

    try {
        while (true) {
            const { value, done } = await reader.read();
            if (done) break;

            const text = decoder.decode(value, { stream: true });
            lineBuffer += text;

            // Universal delimiter splitting (CR, LF, STX 0x02, ETX 0x03)
            let lines = lineBuffer.split(/[\r\n\x02\x03\x0D\x0A]+/);
            lineBuffer = lines.pop();

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

/* UNIVERSAL WEIGHING INDICATOR PARSER (PRESERVES MINUS READINGS) */
function processWeightLine(line) {
    if (!line) return;
    line = line.trim();

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