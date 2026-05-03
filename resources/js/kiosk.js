import Alpine from 'alpinejs';
import { Html5Qrcode } from 'html5-qrcode';

window.Alpine = Alpine;

window.kioskApp = function () {
    return {
        mode: 'checkout', // 'checkout' | 'checkin'
        step: 'employee',
        manualCode: '',
        employee: null,
        tool: null,
        qty: 1,
        workOrder: '',
        toLocationId: '',
        locations: window.KIOSK_LOCATIONS || [],
        returnDueAt: '',

        // Checkin state
        pendingMovements: [],
        selectedMovement: null,

        sending: false,
        message: '',
        messageType: '',
        scanner: null,
        cameras: [],
        cameraId: null,
        scanning: false,
        scannerError: '',

        defaultReturnDueAt() {
            const d = new Date(Date.now() + 8 * 3600 * 1000);
            const pad = (n) => String(n).padStart(2, '0');
            return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
        },

        get stepTitle() {
            if (this.mode === 'checkin') {
                if (this.pendingMovements.length === 0) return 'Devolver · Escanea el tag o código';
                if (this.pendingMovements.length === 1 || this.selectedMovement) return 'Devolver · Confirma';
                return 'Devolver · Selecciona qué devolución';
            }
            if (this.step === 'employee') return 'Sacar · Paso 1 · Escanea tu gafete';
            if (this.step === 'tool') return 'Sacar · Paso 2 · Escanea la herramienta';
            return 'Sacar · Paso 3 · Confirma la salida';
        },

        switchMode(newMode) {
            this.mode = newMode;
            this.reset();
        },

        async init() {
            try {
                const cams = await Html5Qrcode.getCameras();
                this.cameras = cams || [];
                if (!this.cameras.length) {
                    this.scannerError = 'No se detectó ninguna cámara.';
                    return;
                }
                this.cameraId = this.cameras[0].id;
                this.scanner = new Html5Qrcode('qr-reader');
                await this.startCamera();
            } catch (e) {
                this.scannerError =
                    'No se pudo acceder a la cámara: ' +
                    (e?.message || e) +
                    '. Revisa permisos del navegador (candado 🔒 → Cámara → Permitir).';
            }
        },

        async startCamera() {
            if (!this.scanner || !this.cameraId) return;
            if (this.scanning) {
                try { await this.scanner.stop(); } catch (_) {}
            }
            try {
                await this.scanner.start(
                    this.cameraId,
                    {
                        fps: 15,
                        qrbox: (w, h) => {
                            const min = Math.min(w, h);
                            const size = Math.floor(min * 0.75);
                            return { width: size, height: size };
                        },
                        aspectRatio: 16 / 9,
                        disableFlip: false,
                        videoConstraints: {
                            deviceId: { exact: this.cameraId },
                            width: { ideal: 1280 },
                            height: { ideal: 720 },
                        },
                        experimentalFeatures: { useBarCodeDetectorIfSupported: true },
                    },
                    (decoded) => this.handleScan(decoded),
                    () => {},
                );
                this.scanning = true;
                this.scannerError = '';
            } catch (e) {
                this.scannerError = 'Error al iniciar cámara: ' + (e?.message || e);
                this.scanning = false;
            }
        },

        async switchCamera(event) {
            this.cameraId = event.target.value;
            await this.startCamera();
        },

        async handleScan(code) {
            code = (code || '').toString().trim();
            if (!code || this.sending) return;
            this.manualCode = '';

            if (this.mode === 'checkin') {
                await this.lookupCheckin(code);
                return;
            }

            if (this.step === 'employee') {
                try {
                    const r = await fetch(window.KIOSK_ROUTES.employee + '?code=' + encodeURIComponent(code));
                    if (!r.ok) throw new Error('Gafete no reconocido');
                    this.employee = await r.json();
                    this.step = 'tool';
                    this.flash('ok', 'Empleado: ' + this.employee.name);
                } catch (e) {
                    this.flash('error', e.message);
                }
            } else if (this.step === 'tool') {
                try {
                    const r = await fetch(window.KIOSK_ROUTES.tool + '?code=' + encodeURIComponent(code));
                    if (!r.ok) throw new Error('Herramienta no encontrada');
                    this.tool = await r.json();
                    this.step = 'confirm';
                    if (this.tool.type === 'durable') {
                        this.returnDueAt = this.defaultReturnDueAt();
                    } else {
                        this.returnDueAt = '';
                    }
                    this.flash('ok', 'Herramienta: ' + this.tool.name);
                } catch (e) {
                    this.flash('error', e.message);
                }
            }
        },

        async lookupCheckin(code) {
            try {
                const r = await fetch(window.KIOSK_ROUTES.checkinLookup + '?code=' + encodeURIComponent(code));
                if (!r.ok) {
                    const j = await r.json().catch(() => ({}));
                    throw new Error(j.message || 'No se encontró devolución pendiente');
                }
                const j = await r.json();
                this.pendingMovements = j.movements || [];
                if (this.pendingMovements.length === 1) {
                    this.selectedMovement = this.pendingMovements[0];
                }
                this.flash('ok', `${this.pendingMovements.length} devolución(es) encontrada(s)`);
            } catch (e) {
                this.flash('error', e.message);
            }
        },

        pickCheckin(m) {
            this.selectedMovement = m;
        },

        async commit() {
            if (!this.toLocationId) {
                this.flash('error', 'Selecciona la ubicación destino.');
                return;
            }
            this.sending = true;
            this.message = '';
            try {
                const r = await fetch(window.KIOSK_ROUTES.commit, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        tool_code: this.tool.code,
                        tool_item_id: this.tool.item_id || null,
                        employee_code: this.employee.employee_code,
                        qty: this.tool.tracking_mode === 'serialized' ? 1 : this.qty,
                        work_order: this.workOrder || null,
                        to_location_id: this.toLocationId || null,
                        return_due_at: this.tool.type === 'durable' ? (this.returnDueAt || null) : null,
                    }),
                });
                const j = await r.json();
                if (!r.ok || !j.ok) throw new Error(j.error || 'Error al registrar');
                const label = j.tag ? `${j.tool} [${j.tag}]` : `${j.tool} × ${j.qty}`;
                this.flash('ok', `Salida: ${label} para ${j.customer}`);
                setTimeout(() => this.reset(), 2500);
            } catch (e) {
                this.flash('error', e.message);
            } finally {
                this.sending = false;
            }
        },

        async commitCheckin() {
            if (!this.selectedMovement) return;
            this.sending = true;
            this.message = '';
            try {
                const r = await fetch(window.KIOSK_ROUTES.checkin, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ movement_id: this.selectedMovement.id }),
                });
                const j = await r.json();
                if (!r.ok || !j.ok) throw new Error(j.error || 'Error al devolver');
                const label = j.tag ? `${j.tool} [${j.tag}]` : j.tool;
                this.flash('ok', `Devuelto: ${label} (era de ${j.customer})`);
                setTimeout(() => this.reset(), 2500);
            } catch (e) {
                this.flash('error', e.message);
            } finally {
                this.sending = false;
            }
        },

        reset() {
            this.step = 'employee';
            this.employee = null;
            this.tool = null;
            this.qty = 1;
            this.workOrder = '';
            this.toLocationId = '';
            this.returnDueAt = '';
            this.pendingMovements = [];
            this.selectedMovement = null;
        },

        flash(type, msg) {
            this.messageType = type;
            this.message = msg;
            setTimeout(() => { this.message = ''; }, 5000);
        },
    };
};

Alpine.start();
