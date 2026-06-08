import jsQR from 'jsqr';

export function registerBarcodeScanner() {
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('barcodeScanner', (targetField = null) => ({
            open: false,
            stream: null,
            scanning: false,
            animationId: null,
            error: null,
            targetField,

            async openScanner() {
                this.error = null;
                this.open = true;
                await this.$nextTick();
                await this.startCamera();
            },

            closeScanner() {
                this.stopCamera();
                this.open = false;
            },

            async startCamera() {
                if (!navigator.mediaDevices?.getUserMedia) {
                    this.error = 'Fotocamera non supportata su questo dispositivo.';
                    return;
                }

                try {
                    this.stream = await navigator.mediaDevices.getUserMedia({
                        video: { facingMode: { ideal: 'environment' } },
                        audio: false,
                    });

                    const video = this.$refs.video;
                    if (!video) {
                        return;
                    }

                    video.srcObject = this.stream;
                    await video.play();
                    this.scanning = true;
                    this.scanFrame();
                } catch (err) {
                    this.error = 'Impossibile accedere alla fotocamera. Verifica i permessi.';
                    console.error('barcodeScanner camera error', err);
                }
            },

            stopCamera() {
                this.scanning = false;

                if (this.animationId) {
                    cancelAnimationFrame(this.animationId);
                    this.animationId = null;
                }

                if (this.stream) {
                    this.stream.getTracks().forEach((track) => track.stop());
                    this.stream = null;
                }

                const video = this.$refs.video;
                if (video) {
                    video.srcObject = null;
                }
            },

            scanFrame() {
                if (!this.scanning) {
                    return;
                }

                const video = this.$refs.video;
                const canvas = this.$refs.canvas;

                if (!video || !canvas || video.readyState !== video.HAVE_ENOUGH_DATA) {
                    this.animationId = requestAnimationFrame(() => this.scanFrame());
                    return;
                }

                const ctx = canvas.getContext('2d', { willReadFrequently: true });
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const code = jsQR(imageData.data, imageData.width, imageData.height, {
                    inversionAttempts: 'dontInvert',
                });

                if (code?.data) {
                    this.handleDetection(code.data);
                    return;
                }

                this.animationId = requestAnimationFrame(() => this.scanFrame());
            },

            handleDetection(value) {
                const trimmed = String(value).trim();
                this.$dispatch('barcode-detected', { value: trimmed, target: this.targetField });
                this.closeScanner();
            },

            destroy() {
                this.stopCamera();
            },
        }));
    });
}
