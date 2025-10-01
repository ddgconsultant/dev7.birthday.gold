/**
 * Birthday.Gold Analytics System
 *
 * Lightweight, privacy-focused analytics tracking system.
 * Captures page views, user interactions, and custom events.
 *
 * @version 1.0.0
 * @license Proprietary - Birthday.Gold
 */

(function() {
    'use strict';

    // Configuration
    const config = {
        endpoint: '/api/analytics-track.php',
        debug: window.location.hostname.includes('dev') || window.location.hostname.includes('localhost'),
        sessionKey: 'bg_analytics_session',
        visitKey: 'bg_analytics_visit'
    };

    // Utilities
    const utils = {
        log: function(...args) {
            if (config.debug) console.log('[BG Analytics]', ...args);
        },

        getSessionId: function() {
            let sessionId = sessionStorage.getItem(config.sessionKey);
            if (!sessionId) {
                sessionId = 'sess_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                sessionStorage.setItem(config.sessionKey, sessionId);
            }
            return sessionId;
        },

        getVisitId: function() {
            let visitId = localStorage.getItem(config.visitKey);
            if (!visitId) {
                visitId = 'visit_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                localStorage.setItem(config.visitKey, visitId);
            }
            return visitId;
        },

        getDeviceInfo: function() {
            return {
                screen: window.screen.width + 'x' + window.screen.height,
                viewport: window.innerWidth + 'x' + window.innerHeight,
                userAgent: navigator.userAgent,
                language: navigator.language,
                platform: navigator.platform,
                mobile: /Mobile|Android|iPhone|iPad|iPod/i.test(navigator.userAgent)
            };
        },

        getPageInfo: function() {
            return {
                url: window.location.href,
                path: window.location.pathname,
                title: document.title,
                referrer: document.referrer || 'direct',
                hash: window.location.hash
            };
        }
    };

    // Tracking core
    const tracker = {
        queue: [],
        initialized: false,

        init: function() {
            if (this.initialized) return;

            utils.log('Initializing...');
            this.initialized = true;

            // Track initial pageview
            this.trackPageview();

            // Setup event listeners
            this.setupListeners();

            // Process queue
            this.processQueue();

            utils.log('Initialized successfully');
        },

        track: function(eventName, eventData = {}) {
            const payload = {
                event: eventName,
                timestamp: Date.now(),
                session_id: utils.getSessionId(),
                visit_id: utils.getVisitId(),
                page: utils.getPageInfo(),
                device: utils.getDeviceInfo(),
                data: eventData
            };

            utils.log('Tracking:', eventName, payload);

            // Send immediately if navigator.sendBeacon is available
            if (navigator.sendBeacon) {
                const blob = new Blob([JSON.stringify(payload)], { type: 'application/json' });
                navigator.sendBeacon(config.endpoint, blob);
            } else {
                // Fallback to fetch for older browsers
                fetch(config.endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                    keepalive: true
                }).catch(err => utils.log('Track error:', err));
            }
        },

        trackPageview: function() {
            this.track('pageview', {
                entrypoint: !document.referrer || !document.referrer.includes(window.location.hostname)
            });
        },

        trackEvent: function(category, action, label = null, value = null) {
            this.track('event', {
                category: category,
                action: action,
                label: label,
                value: value
            });
        },

        trackClick: function(element, data = {}) {
            const clickData = {
                tag: element.tagName,
                id: element.id,
                class: element.className,
                text: element.textContent.substring(0, 100),
                href: element.href || null,
                ...data
            };
            this.track('click', clickData);
        },

        setupListeners: function() {
            // Track outbound links
            document.addEventListener('click', (e) => {
                const link = e.target.closest('a');
                if (link && link.href) {
                    const isOutbound = link.hostname && link.hostname !== window.location.hostname;
                    const isDownload = link.download || /\.(pdf|zip|doc|docx|xls|xlsx|ppt|pptx)$/i.test(link.href);

                    if (isOutbound) {
                        this.track('outbound_click', {
                            url: link.href,
                            text: link.textContent.substring(0, 100)
                        });
                    } else if (isDownload) {
                        this.track('download', {
                            url: link.href,
                            filename: link.href.split('/').pop()
                        });
                    }
                }
            }, true);

            // Track form submissions
            document.addEventListener('submit', (e) => {
                const form = e.target;
                if (form.tagName === 'FORM') {
                    this.track('form_submit', {
                        id: form.id,
                        action: form.action,
                        method: form.method
                    });
                }
            }, true);

            // Track page visibility changes
            document.addEventListener('visibilitychange', () => {
                this.track('visibility_change', {
                    hidden: document.hidden
                });
            });

            // Track time on page when leaving
            let startTime = Date.now();
            window.addEventListener('beforeunload', () => {
                const timeOnPage = Math.round((Date.now() - startTime) / 1000);
                this.track('page_exit', {
                    duration_seconds: timeOnPage
                });
            });

            // Track scroll depth (throttled)
            let maxScroll = 0;
            let scrollTimeout;
            window.addEventListener('scroll', () => {
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(() => {
                    const scrollPercent = Math.round(
                        (window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100
                    );
                    if (scrollPercent > maxScroll) {
                        maxScroll = scrollPercent;
                        if (scrollPercent >= 25 && scrollPercent < 50 && maxScroll >= 25) {
                            this.track('scroll_depth', { depth: 25 });
                        } else if (scrollPercent >= 50 && scrollPercent < 75 && maxScroll >= 50) {
                            this.track('scroll_depth', { depth: 50 });
                        } else if (scrollPercent >= 75 && scrollPercent < 90 && maxScroll >= 75) {
                            this.track('scroll_depth', { depth: 75 });
                        } else if (scrollPercent >= 90 && maxScroll >= 90) {
                            this.track('scroll_depth', { depth: 100 });
                        }
                    }
                }, 250);
            });
        },

        processQueue: function() {
            while (this.queue.length > 0) {
                const item = this.queue.shift();
                this.track(item.event, item.data);
            }
        }
    };

    // Public API
    window.bgAnalytics = {
        track: function(event, data) {
            if (tracker.initialized) {
                tracker.track(event, data);
            } else {
                tracker.queue.push({ event, data });
            }
        },

        trackEvent: function(category, action, label, value) {
            tracker.trackEvent(category, action, label, value);
        },

        trackClick: function(element, data) {
            tracker.trackClick(element, data);
        },

        // Allow custom pageview tracking for SPAs
        trackPageview: function() {
            tracker.trackPageview();
        }
    };

    // Auto-initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => tracker.init());
    } else {
        tracker.init();
    }

})();
