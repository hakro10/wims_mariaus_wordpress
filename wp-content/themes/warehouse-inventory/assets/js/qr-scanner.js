/**
 * Mobile-Optimized QR Code Scanner
 * Handles camera access and QR code detection for warehouse management
 */

class WarehouseQRScanner {
    constructor() {
        this.stream = null;
        this.video = null;
        this.canvas = null;
        this.context = null;
        this.scanning = false;
        this.facingMode = 'environment'; // Start with back camera
        this.onScanCallback = null;
        
        this.init();
    }
    
    init() {
        // Create canvas for image processing
        this.canvas = document.createElement('canvas');
        this.context = this.canvas.getContext('2d');
        
        // Check for camera support
        this.checkCameraSupport();
    }
    
    checkCameraSupport() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            console.warn('Camera not supported on this device');
            return false;
        }
        return true;
    }
    
    async startScanner(videoElement, onScanCallback) {
        if (!this.checkCameraSupport()) {
            throw new Error('Camera not supported');
        }
        
        this.video = videoElement;
        this.onScanCallback = onScanCallback;
        
        try {
            const constraints = {
                video: {
                    facingMode: this.facingMode,
                    width: { ideal: 640, max: 1280 },
                    height: { ideal: 480, max: 720 }
                }
            };
            
            this.stream = await navigator.mediaDevices.getUserMedia(constraints);
            this.video.srcObject = this.stream;
            
            // Wait for video to be ready
            await new Promise((resolve) => {
                this.video.onloadedmetadata = () => {
                    this.video.play();
                    resolve();
                };
            });
            
            this.scanning = true;
            this.scanLoop();
            
            return true;
        } catch (error) {
            console.error('Error starting camera:', error);
            throw error;
        }
    }
    
    stopScanner() {
        this.scanning = false;
        
        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
            this.stream = null;
        }
        
        if (this.video) {
            this.video.srcObject = null;
        }
    }
    
    async switchCamera() {
        this.facingMode = this.facingMode === 'environment' ? 'user' : 'environment';
        
        if (this.scanning) {
            const videoElement = this.video;
            const callback = this.onScanCallback;
            
            this.stopScanner();
            
            // Small delay to ensure camera is released
            await new Promise(resolve => setTimeout(resolve, 100));
            
            return this.startScanner(videoElement, callback);
        }
    }
    
    scanLoop() {
        if (!this.scanning || !this.video) {
            return;
        }
        
        if (this.video.readyState === this.video.HAVE_ENOUGH_DATA) {
            this.processFrame();
        }
        
        // Continue scanning
        requestAnimationFrame(() => this.scanLoop());
    }
    
    processFrame() {
        const { videoWidth, videoHeight } = this.video;
        
        if (videoWidth === 0 || videoHeight === 0) {
            return;
        }
        
        // Set canvas size to match video
        this.canvas.width = videoWidth;
        this.canvas.height = videoHeight;
        
        // Draw current video frame to canvas
        this.context.drawImage(this.video, 0, 0, videoWidth, videoHeight);
        
        // Get image data for QR detection
        const imageData = this.context.getImageData(0, 0, videoWidth, videoHeight);
        
        // Try to detect QR code
        try {
            const qrCode = this.detectQRCode(imageData);
            if (qrCode && this.onScanCallback) {
                this.onScanCallback(qrCode);
            }
        } catch (error) {
            // Silently continue - QR detection errors are normal
        }
    }
    
    detectQRCode(imageData) {
        // Use jsQR library for real QR code detection
        if (typeof jsQR !== 'undefined') {
            const code = jsQR(imageData.data, imageData.width, imageData.height, {
                inversionAttempts: "dontInvert",
            });
            
            if (code) {
                console.log('QR Code detected:', code.data);
                return code.data;
            }
        } else {
            console.warn('jsQR library not loaded');
        }
        
        return null;
    }
    
    // Utility method to capture current frame as image
    captureFrame() {
        if (!this.video || this.video.readyState !== this.video.HAVE_ENOUGH_DATA) {
            return null;
        }
        
        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d');
        
        canvas.width = this.video.videoWidth;
        canvas.height = this.video.videoHeight;
        
        context.drawImage(this.video, 0, 0);
        
        return canvas.toDataURL('image/jpeg', 0.8);
    }
    
    // Get available cameras
    async getAvailableCameras() {
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            return devices.filter(device => device.kind === 'videoinput');
        } catch (error) {
            console.error('Error getting cameras:', error);
            return [];
        }
    }
}

// Mobile-specific optimizations
class MobileQROptimizer {
    static optimizeForMobile() {
        // Prevent zoom on input focus (iOS)
        const viewport = document.querySelector('meta[name="viewport"]');
        if (viewport) {
            viewport.setAttribute('content', 
                'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'
            );
        }
        
        // Add touch event handling
        this.addTouchHandlers();
        
        // Optimize for PWA
        this.addPWAOptimizations();
    }
    
    static addTouchHandlers() {
        // Prevent double-tap zoom on buttons
        document.addEventListener('touchend', function(e) {
            if (e.target.classList.contains('btn') || 
                e.target.classList.contains('btn-icon')) {
                e.preventDefault();
            }
        });
        
        // Add haptic feedback for supported devices
        if ('vibrate' in navigator) {
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('btn-primary')) {
                    navigator.vibrate(50); // Short vibration
                }
            });
        }
    }
    
    static addPWAOptimizations() {
        // Add to home screen prompt handling
        let deferredPrompt;
        
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            
            // Show custom install button if desired
            const installBtn = document.getElementById('install-app-btn');
            if (installBtn) {
                installBtn.style.display = 'block';
                installBtn.addEventListener('click', () => {
                    deferredPrompt.prompt();
                    deferredPrompt.userChoice.then((choiceResult) => {
                        if (choiceResult.outcome === 'accepted') {
                            console.log('User accepted the install prompt');
                        }
                        deferredPrompt = null;
                    });
                });
            }
        });
        
        // Handle app installation
        window.addEventListener('appinstalled', (evt) => {
            console.log('App was installed');
        });
    }
    
    // Check if running as PWA
    static isPWA() {
        return window.matchMedia('(display-mode: standalone)').matches ||
               window.navigator.standalone === true;
    }
    
    // Get device orientation
    static getOrientation() {
        if (screen.orientation) {
            return screen.orientation.angle;
        }
        return window.orientation || 0;
    }
    
    // Handle orientation changes
    static handleOrientationChange(callback) {
        const handleChange = () => {
            setTimeout(() => {
                callback(this.getOrientation());
            }, 100); // Small delay for orientation to settle
        };
        
        if (screen.orientation) {
            screen.orientation.addEventListener('change', handleChange);
        } else {
            window.addEventListener('orientationchange', handleChange);
        }
    }
}

// Performance optimizations for mobile
class MobilePerformanceOptimizer {
    static optimizeScanning() {
        // Reduce scan frequency on slower devices
        const isSlowDevice = this.isSlowDevice();
        return isSlowDevice ? 100 : 50; // ms between scans
    }
    
    static isSlowDevice() {
        // Simple device performance detection
        const canvas = document.createElement('canvas');
        const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
        
        if (!gl) return true;
        
        const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
        if (debugInfo) {
            const renderer = gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL);
            // Check for low-end GPU indicators
            return /mali|adreno [1-3]|powervr sgx/i.test(renderer);
        }
        
        // Fallback: check memory
        return navigator.deviceMemory && navigator.deviceMemory < 4;
    }
    
    static optimizeImageProcessing(imageData) {
        // Reduce image size for processing on slow devices
        if (this.isSlowDevice()) {
            return this.downscaleImageData(imageData, 0.5);
        }
        return imageData;
    }
    
    static downscaleImageData(imageData, scale) {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        
        const newWidth = Math.floor(imageData.width * scale);
        const newHeight = Math.floor(imageData.height * scale);
        
        canvas.width = newWidth;
        canvas.height = newHeight;
        
        // Create temporary canvas with original image
        const tempCanvas = document.createElement('canvas');
        const tempCtx = tempCanvas.getContext('2d');
        tempCanvas.width = imageData.width;
        tempCanvas.height = imageData.height;
        tempCtx.putImageData(imageData, 0, 0);
        
        // Draw scaled down
        ctx.drawImage(tempCanvas, 0, 0, newWidth, newHeight);
        
        return ctx.getImageData(0, 0, newWidth, newHeight);
    }
}

// Export for use in other files
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { WarehouseQRScanner, MobileQROptimizer, MobilePerformanceOptimizer };
} else {
    window.WarehouseQRScanner = WarehouseQRScanner;
    window.MobileQROptimizer = MobileQROptimizer;
    window.MobilePerformanceOptimizer = MobilePerformanceOptimizer;
}

// Auto-initialize mobile optimizations when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    MobileQROptimizer.optimizeForMobile();
}); 