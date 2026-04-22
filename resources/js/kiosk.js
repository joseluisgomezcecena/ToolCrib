import Alpine from 'alpinejs';
import { Html5Qrcode } from 'html5-qrcode';

window.Alpine = Alpine;

window.kioskApp = function () {
    return {
        step: 'employee',
        manualCode: '',
        employee: null,
        tool: null,
        qty: 1,
        workOrder: '',
        machine: '',
        returnDueAt: '',
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
            if (this.step === 'employee') return 'Paso 1 · Escanea tu gafete';
            if (this.step === 'tool') return 'Paso 2 · Escanea la herramienta';
            return 'Paso 3 · Confirma la salida';
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

        async commit() {
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
                        employee_code: this.employee.employee_code,
                        qty: this.qty,
                        work_order: this.workOrder || null,
                        machine: this.machine || null,
                        return_due_at: this.tool.type === 'durable' ? (this.returnDueAt || null) : null,
                    }),
                });
                const j = await r.json();
                if (!r.ok || !j.ok) throw new Error(j.error || 'Error al registrar');
                this.flash('ok', `Registrado: ${j.tool} × ${j.qty} para ${j.customer}`);
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
            this.machine = '';
            this.returnDueAt = '';
        },

        flash(type, msg) {
            this.messageType = type;
            this.message = msg;
            setTimeout(() => { this.message = ''; }, 5000);
        },
    };
};

Alpine.start();
