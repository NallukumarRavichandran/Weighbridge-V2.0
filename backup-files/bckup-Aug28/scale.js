let port = null;
let reader = null;
let lineBuffer = "";
let lastWeight = null;
let sameCount = 0;
var weightFrozen = false;
let isConnecting = false;

/* ============================================================
   UNIVERSAL SCALE BAUD & PARITY PROFILES MATRIX
   ============================================================ */
const UNIVERSAL_SCALE_PROFILES = [
    { name: "9600 8N1 (Modern Standard)", baudRate: 9600, dataBits: 8, parity: "none", stopBits: 1 },
    { name: "2400 7E1 (Legacy Standard)", baudRate: 2400, dataBits: 7, parity: "even", stopBits: 1 },
    { name: "2400 8N1", baudRate: 2400, dataBits: 8, parity: "none", stopBits: 1 },
    { name: "4800 8N1", baudRate: 4800, dataBits: 8, parity: "none", stopBits: 1 },
    { name: "4800 7E1", baudRate: 4800, dataBits: 7, parity: "even", stopBits: 1 },
    { name: "1200 7E1", baudRate: 1200, dataBits: 7, parity: "even", stopBits: 1 },
    { name: "19200 8N1", baudRate: 19200, dataBits: 8, parity: "none", stopBits: 1 }
];

/* STATUS HELPER */
function setStatus(text, color = "#b91c1c") {
    const el = document.getElementById("connStatus");
    if (!el) return;
    el.innerText = text;
    el.style.color = color;
}

/* UPDATE LIVE WEIGHT UI */
function updateLiveWeight(weight) {
    if (weight === lastWeight) {
        sameCount++;
    } else {
        sameCount = 0;
    }

    lastWeight = weight;

    // Update live weight screen element
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
        console.log("🔒 Working Scale Profile Saved:", profile.name || profile.baudRate);
    } catch(e) {}
}

/* PROBE A SINGLE SERIAL PROFILE TO VERIFY VALID WEIGHT STREAM */
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
                accumulatedText += decoder.decode(value);

                // Look for valid readable scale stream containing numbers
                let match = accumulatedText.match(/[-+]?\d*\.?\d+/);
                if (match && !accumulatedText.includes("\uFFFD")) {
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

/* UNIVERSAL AUTO-BAUD DETECTOR & CONNECTION ENGINE */
async function connectScaleDirect() {
    if (port && port.readable) {
        console.log("Scale is already connected and active.");
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

        // Clean any lingering state
        await cleanupSerialHandles();

        setStatus("SCANNING...", "#f59e0b");
        console.log("🔍 Starting Universal Scale Auto-Detection...");

        let workingProfile = null;
        const savedProfile = getSavedScaleProfile();

        // 1. Try saved profile first for instant sub-50ms connection
        if (savedProfile) {
            console.log("Testing saved profile:", savedProfile.name || savedProfile.baudRate);
            const result = await probeSerialProfile(port, savedProfile, 400);
            if (result.success) {
                workingProfile = savedProfile;
            }
        }

        // 2. If saved profile failed or first-time setup, scan the profile matrix
        if (!workingProfile) {
            for (let profile of UNIVERSAL_SCALE_PROFILES) {
                console.log(`Scanning profile: ${profile.name} (${profile.baudRate} ${profile.parity})...`);
                const result = await probeSerialProfile(port, profile, 400);
                
                if (result.success) {
                    workingProfile = profile;
                    saveWorkingScaleProfile(profile);
                    break;
                }
            }
        }

        // 3. Fallback to 9600 8N1 if scale is static / zero
        if (!workingProfile) {
            console.warn("No active stream detected during scan; defaulting to 9600 8N1.");
            workingProfile = UNIVERSAL_SCALE_PROFILES[0];
            await port.open({
                baudRate: workingProfile.baudRate,
                dataBits: workingProfile.dataBits,
                stopBits: workingProfile.stopBits,
                parity: workingProfile.parity,
                flowControl: "none"
            });
        } else if (!port.readable) {
            // Reopen with locked working profile
            await port.open({
                baudRate: workingProfile.baudRate,
                dataBits: workingProfile.dataBits,
                stopBits: workingProfile.stopBits,
                parity: workingProfile.parity,
                flowControl: "none"
            });
        }

        console.log(`✅ LOCKED CONFIGURATION: ${workingProfile.baudRate} Baud (${workingProfile.dataBits || 8} DataBits, Parity: ${workingProfile.parity || 'none'})`);
        setStatus(`CONNECTED (${workingProfile.baudRate})`, "#16a34a");
        closeScaleDialog();

        lineBuffer = "";
        readScale();

    } catch (err) {
        console.error("Connection Error:", err);
        setStatus("DISCONNECTED", "#b91c1c");
        if (!err.message.includes("already in progress") && !err.message.includes("already open")) {
            alert("Scale Connection Error: " + err.message);
        }
    } finally {
        isConnecting = false;
    }
}

/* CONNECT SCALE (MENU BUTTON) */
async function connectScale() {
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
        console.log("Auto-reconnect notice:", e.message);
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

            const text = decoder.decode(value);
            lineBuffer += text;

            let lines = lineBuffer.split(/[\r\n]+/);
            lineBuffer = lines.pop(); // Keep buffer fragment

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

/* UNIVERSAL FLOATING-POINT / INTEGER WEIGHT PARSER */
function processWeightLine(line) {
    if (!line) return;

    // Match numbers with optional decimal point (e.g. "wn00007.5kg", "ST,GS,+0012500kg", "3134")
    let match = line.match(/[-+]?\d*\.?\d+/);
    if (!match) return;

    let weight = parseFloat(match[0]);

    if (!window.weightFrozen && !isNaN(weight)) {
        updateLiveWeight(weight);
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