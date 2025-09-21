// checkout-tracking.js
class CheckoutTracker {
    constructor(options = {}) {
        this.points = [];
        this.lastBatch = Date.now();
        this.startTime = Date.now();
        this.options = {
            batchInterval: options.batchInterval || 5000, // 5 seconds
            endpoint: options.endpoint || '/helper_mousetrack',
            sessionId: this.generateSessionId(),
            maxSessionDuration: options.maxSessionDuration || 1800000, // 30 minutes in milliseconds
            maxPoints: options.maxPoints || 10000, // Maximum number of points to record
            inactivityTimeout: options.inactivityTimeout || 600000, // 10 minutes in milliseconds
        };
        
        this.lastActivity = Date.now();

        // Capture screen and viewport information
        this.metadata = {
            screenWidth: window.screen.width,
            screenHeight: window.screen.height,
            viewportWidth: window.innerWidth,
            viewportHeight: window.innerHeight,
            userAgent: navigator.userAgent,
            timestamp: new Date().toISOString(),
            url: window.location.href,
            referrer: document.referrer
        };

        this.initializeTracking();
    }

    generateSessionId() {
        return 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    initializeTracking() {
        // Throttled mouse move handler
        let lastMove = 0;
        document.addEventListener('mousemove', (e) => {
            const now = Date.now();
            if (now - lastMove > 50) { // Throttle to every 50ms
                this.trackPoint(e);
                lastMove = now;
            }
        });

        // Track clicks
        document.addEventListener('click', (e) => {
            this.trackPoint(e, 'click');
        });

        // Set up batch sending
        setInterval(() => this.sendBatch(), this.options.batchInterval);

        // Send data before page unload
        window.addEventListener('beforeunload', () => {
            this.sendBatch(true);
        });

        // Track form interactions
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', () => {
                this.trackEvent('form_submit', { formId: form.id });
            });
        });

        // Track form field interactions
        document.querySelectorAll('input, select, textarea').forEach(field => {
            field.addEventListener('focus', () => {
                this.trackEvent('field_focus', { 
                    fieldId: field.id,
                    fieldName: field.name,
                    fieldType: field.type 
                });
            });
        });
    }

    trackPoint(e, type = 'move') {
        const now = Date.now();
        
        // Check if we've exceeded session duration
        if (now - this.startTime > this.options.maxSessionDuration) {
            this.sendBatch(true);
            this.stopTracking();
            return;
        }

        // Check for inactivity
        if (now - this.lastActivity > this.options.inactivityTimeout) {
            this.sendBatch(true);
            this.stopTracking();
            return;
        }

        // Check if we've reached max points
        if (this.points.length >= this.options.maxPoints) {
            this.sendBatch(true);
            this.stopTracking();
            return;
        }

        const point = {
            x: e.clientX,
            y: e.clientY,
            timestamp: now,
            type: type,
            targetElement: this.getElementPath(e.target)
        };
        
        this.points.push(point);
        this.lastActivity = now;
    }

    trackEvent(eventType, data) {
        const event = {
            type: eventType,
            timestamp: Date.now(),
            data: data
        };
        this.points.push(event);
    }

    getElementPath(element) {
        const path = [];
        while (element && element.tagName) {
            let identifier = element.tagName.toLowerCase();
            if (element.id) {
                identifier += `#${element.id}`;
            } else if (element.className) {
                identifier += `.${element.className.replace(/\s+/g, '.')}`;
            }
            path.unshift(identifier);
            element = element.parentElement;
        }
        return path.join(' > ');
    }

    stopTracking() {
        // Remove event listeners
        document.removeEventListener('mousemove', this.handleMouseMove);
        document.removeEventListener('click', this.handleClick);
        window.removeEventListener('beforeunload', this.handleUnload);
        // Clear any running intervals
        if (this.batchInterval) {
            clearInterval(this.batchInterval);
        }
    }

    async sendBatch(isUnload = false) {
        if (this.points.length === 0) return;

        const batchData = {
            sessionId: this.options.sessionId,
            metadata: this.metadata,
            points: this.points,
            isUnload: isUnload
        };

        // Use sendBeacon for unload events to ensure data is sent
        if (isUnload && navigator.sendBeacon) {
            navigator.sendBeacon(this.options.endpoint, JSON.stringify(batchData));
        } else {
            try {
                await fetch(this.options.endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(batchData)
                });
            } catch (error) {
                console.error('Error sending tracking data:', error);
            }
        }

        this.points = [];
        this.lastBatch = Date.now();
    }
}

// Usage
const tracker = new CheckoutTracker();