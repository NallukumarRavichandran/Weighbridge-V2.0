let port = null;
let reader = null;
let lineBuffer = "";
let lastWeight = null;
let sameCount = 0;
let weightFrozen = false;
let isConnecting = false;

/* STATUS */
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

    const el = document.getElementById("live_weight");
    if (el) {
        if (el.tagName === "INPUT" || el.tagName === "TEXTAREA") {
            el.value = weight;
        } else {
            el.innerText = weight;
        }
    }
}

/* CONNECT SCALE DIRECT (MODAL BUTTON - 2400 7E1) */
async function connectScaleDirect() {
    if (port && port.readable) {
        console.log("Already connected and active");
        setStatus("CONNECTED", "#16a34a");
        closeScaleDialog();
        return;
    }

    if (isConnecting) {
        console.log("Connection already in progress...");
        return;
    }

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

        // Customer's Machine Configuration: 2400 Baud, 7 Data Bits, Even Parity (7E1)
        await port.open({
            baudRate: 2400,
            dataBits: 7,
            stopBits: 1,
            parity: "even",
            flowControl: "none"
        });

        console.log("PORT OPENED AT 2400 7E1");
        setStatus("CONNECTED", "#16a34a");
        closeScaleDialog();

        lineBuffer = "";
        readScale();

    } catch (err) {
        console.error(err);
        setStatus("DISCONNECTED", "#b91c1c");
        if (!err.message.includes("already in progress") && !err.message.includes("already open")) {
            alert("Connection error: " + err.message);
        }
    } finally {
        isConnecting = false;
    }
}

/* CONNECT SCALE (MENU BUTTON) */
async function connectScale() {
    openScaleDialog();
}

/* AUTO RECONNECT (2400 7E1) */
async function autoReconnect() {
    if (port && port.readable) {
        setStatus("CONNECTED", "#16a34a");
        return;
    }

    if (isConnecting) return;

    isConnecting = true;
    console.log("Attempting Reconnect at 2400 7E1...");

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
    } catch (cleanErr) {
        console.log("Cleanup:", cleanErr);
    }

    try {
        const ports = await navigator.serial.getPorts();
        if (ports.length > 0) {
            port = ports[0];
        } else {
            port = await navigator.serial.requestPort();
        }

        if (!port) {
            setStatus("DISCONNECTED", "#b91c1c");
            isConnecting = false;
            return;
        }

        await port.open({
            baudRate: 2400,
            dataBits: 7,
            stopBits: 1,
            parity: "even",
            flowControl: "none"
        });

        lineBuffer = "";
        readScale();
        setStatus("CONNECTED", "#16a34a");
        console.log("RECONNECTED AT 2400 7E1");

    } catch (e) {
        console.error("Reconnect Notice:", e.message);
        if (!e.message.includes("already in progress") && !e.message.includes("already open")) {
            setStatus("DISCONNECTED", "#b91c1c");
        }
    } finally {
        isConnecting = false;
    }
}

/* DISCONNECT */
async function disconnectScale() {
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

        console.log("PORT CLOSED");
    } catch (e) {
        console.error(e);
    }

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
            console.log("RAW:", text);

            lineBuffer += text;

            let lines = lineBuffer.split(/[\r\n]+/);
            lineBuffer = lines.pop();

            for (let line of lines) {
                processWeightLine(line);
            }
        }
    } catch (e) {
        console.error(e);
        setStatus("DISCONNECTED", "#b91c1c");
    } finally {
        if (reader) {
            reader.releaseLock();
            reader = null;
        }
    }
}

/* PARSE WEIGHT (WITH DECIMAL SUPPORT) */
function processWeightLine(line) {
    if (!line) return;

    let match = line.match(/[-+]?\d*\.?\d+/);

    if (!match) return;

    let weight = parseFloat(match[0]);

    if (!weightFrozen && !isNaN(weight)) {
        updateLiveWeight(weight);
    }
}

function openScaleDialog() {
    const dlg = document.getElementById("scaleDialog");
    if (dlg) dlg.style.display = "block";
}

function closeScaleDialog() {
    const dlg = document.getElementById("scaleDialog");
    if (dlg) dlg.style.display = "none";
}