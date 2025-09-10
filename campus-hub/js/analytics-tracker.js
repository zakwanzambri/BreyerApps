/**
 * Campus Hub Analytics Tracker - Client-side JavaScript
 * Tracks user behavior and sends analytics data to the server
 * 
 * Usage:
 * <script src="js/analytics-tracker.js"></script>
 * <script>
 *   CampusAnalytics.init({
 *     apiUrl: 'php/api/analytics.php',
 *     trackPageViews: true,
 *     trackClicks: true,
 *     trackScrolling: true,
 *     trackFormSubmissions: true
 *   });
 * </script>
 */

(function(window, document) {
    'use strict';
    
    const CampusAnalytics = {
        // Configuration
        config: {
            apiUrl: 'php/api/analytics.php',
            trackPageViews: true,
            trackClicks: true,
            trackScrolling: false,
            trackFormSubmissions: true,
            trackFileDownloads: true,
            trackSearches: true,
            trackVideoEngagement: false,
            sessionTimeout: 30 * 60 * 1000, // 30 minutes
            batchSize: 10,
            flushInterval: 5000, // 5 seconds
            debug: false
        },
        
        // Internal state
        state: {
            initialized: false,
            sessionId: null,
            pageStartTime: null,
            lastActivity: null,
            eventQueue: [],
            scrollDepth: 0,
            maxScrollDepth: 0,
            interactions: 0,
            isVisible: true,
            deviceInfo: {}
        },
        
        /**
         * Initialize the analytics tracker
         */
        init: function(options) {
            if (this.state.initialized) {
                return;
            }
            
            // Merge options with defaults
            this.config = Object.assign(this.config, options || {});
            
            // Generate or retrieve session ID
            this.state.sessionId = this.getSessionId();
            this.state.pageStartTime = Date.now();
            this.state.lastActivity = Date.now();
            
            // Collect device information
            this.collectDeviceInfo();
            
            // Set up event listeners
            this.setupEventListeners();
            
            // Track initial page view
            if (this.config.trackPageViews) {
                this.trackPageView();
            }
            
            // Start periodic flush
            this.startPeriodicFlush();
            
            this.state.initialized = true;
            this.log('Analytics tracker initialized');
        },
        
        /**
         * Track a custom event
         */
        track: function(eventType, data) {
            if (!this.state.initialized) {
                this.log('Analytics not initialized');
                return;
            }
            
            const event = {
                action_type: eventType,
                timestamp: Date.now(),
                page_url: window.location.href,
                page_title: document.title,
                session_id: this.state.sessionId,
                user_agent: navigator.userAgent,
                screen_resolution: `${screen.width}x${screen.height}`,
                viewport_size: `${window.innerWidth}x${window.innerHeight}`,
                ...data
            };
            
            this.queueEvent(event);
        },
        
        /**
         * Track page view
         */
        trackPageView: function(url, title) {
            const pageUrl = url || window.location.href;
            const pageTitle = title || document.title;
            
            this.track('page_view', {
                page_url: pageUrl,
                page_title: pageTitle,
                referrer_url: document.referrer,
                time_spent: null // Will be updated on page unload
            });
            
            this.log('Page view tracked:', pageUrl);
        },
        
        /**
         * Track click events
         */
        trackClick: function(element, customData) {
            const data = {
                element_id: element.id,
                element_type: element.tagName.toLowerCase(),
                element_class: element.className,
                element_text: element.textContent ? element.textContent.substring(0, 100) : '',
                href: element.href || null,
                ...customData
            };
            
            // Detect content interactions
            if (element.dataset.contentId) {
                data.content_id = element.dataset.contentId;
                data.content_type = element.dataset.contentType || 'unknown';
            }
            
            this.track('click', data);
            this.state.interactions++;
        },
        
        /**
         * Track form submissions
         */
        trackFormSubmission: function(form, customData) {
            const data = {
                form_id: form.id,
                form_action: form.action,
                form_method: form.method,
                field_count: form.elements.length,
                ...customData
            };
            
            this.track('form_submission', data);
        },
        
        /**
         * Track file downloads
         */
        trackDownload: function(url, filename, fileType) {
            this.track('download', {
                file_url: url,
                file_name: filename,
                file_type: fileType || this.getFileType(url)
            });
        },
        
        /**
         * Track search queries
         */
        trackSearch: function(query, resultsCount, searchType) {
            const searchData = {
                query: query,
                search_type: searchType || 'global',
                results_count: resultsCount || 0,
                search_time: Date.now()
            };
            
            // Send to search analytics endpoint
            this.sendSearchData(searchData);
            
            this.track('search', searchData);
        },
        
        /**
         * Track search result clicks
         */
        trackSearchClick: function(query, resultPosition, resultId, resultType) {
            this.sendSearchClickData(query, resultPosition, resultId, resultType);
            
            this.track('search_click', {
                query: query,
                result_position: resultPosition,
                result_id: resultId,
                result_type: resultType
            });
        },
        
        /**
         * Track content engagement
         */
        trackContentEngagement: function(contentType, contentId, engagementData) {
            const data = {
                content_type: contentType,
                content_id: contentId,
                action_data: engagementData
            };
            
            this.track('content_engagement', data);
            
            // Send to content analytics endpoint
            this.sendToAPI('track-action', {
                action_type: 'content_engagement',
                data: data
            });
        },
        
        /**
         * Track conversion events
         */
        trackConversion: function(conversionType, conversionGoal, value, additionalData) {
            const conversionData = {
                conversion_type: conversionType,
                conversion_goal: conversionGoal,
                conversion_value: value,
                data: additionalData || {}
            };
            
            this.sendToAPI('track-conversion', conversionData);
            
            this.track('conversion', conversionData);
        },
        
        /**
         * Set up event listeners
         */
        setupEventListeners: function() {
            const self = this;
            
            // Click tracking
            if (this.config.trackClicks) {
                document.addEventListener('click', function(e) {
                    self.trackClick(e.target);
                });
            }
            
            // Form submission tracking
            if (this.config.trackFormSubmissions) {
                document.addEventListener('submit', function(e) {
                    self.trackFormSubmission(e.target);
                });
            }
            
            // Download tracking
            if (this.config.trackFileDownloads) {
                document.addEventListener('click', function(e) {
                    if (e.target.href && self.isDownloadLink(e.target.href)) {
                        const url = e.target.href;
                        const filename = self.getFilenameFromUrl(url);
                        self.trackDownload(url, filename);
                    }
                });
            }
            
            // Scroll tracking
            if (this.config.trackScrolling) {
                let scrollTimeout;
                window.addEventListener('scroll', function() {
                    clearTimeout(scrollTimeout);
                    scrollTimeout = setTimeout(function() {
                        self.updateScrollDepth();
                    }, 100);
                });
            }
            
            // Page visibility
            document.addEventListener('visibilitychange', function() {
                self.state.isVisible = !document.hidden;
                if (!self.state.isVisible) {
                    self.flush(); // Flush events when page becomes hidden
                }
            });
            
            // Page unload tracking
            window.addEventListener('beforeunload', function() {
                self.trackPageExit();
                self.flush();
            });
            
            // Activity tracking
            ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(function(event) {
                document.addEventListener(event, function() {
                    self.state.lastActivity = Date.now();
                }, { passive: true });
            });
        },
        
        /**
         * Track page exit with time spent
         */
        trackPageExit: function() {
            const timeSpent = Math.round((Date.now() - this.state.pageStartTime) / 1000);
            
            this.track('page_exit', {
                time_spent: timeSpent,
                scroll_depth: this.state.maxScrollDepth,
                interactions: this.state.interactions
            });
            
            // Update the page view with time spent
            this.sendToAPI('track-page-view', {
                page_url: window.location.href,
                page_title: document.title,
                time_spent: timeSpent
            });
        },
        
        /**
         * Update scroll depth tracking
         */
        updateScrollDepth: function() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const windowHeight = window.innerHeight;
            const documentHeight = document.documentElement.scrollHeight;
            
            const scrollPercent = Math.round((scrollTop + windowHeight) / documentHeight * 100);
            
            this.state.scrollDepth = scrollPercent;
            this.state.maxScrollDepth = Math.max(this.state.maxScrollDepth, scrollPercent);
            
            // Track milestone scroll depths
            const milestones = [25, 50, 75, 90, 100];
            milestones.forEach(milestone => {
                if (scrollPercent >= milestone && !this.state[`scroll_${milestone}`]) {
                    this.state[`scroll_${milestone}`] = true;
                    this.track('scroll_milestone', {
                        milestone: milestone,
                        scroll_depth: scrollPercent
                    });
                }
            });
        },
        
        /**
         * Collect device and browser information
         */
        collectDeviceInfo: function() {
            this.state.deviceInfo = {
                screen_resolution: `${screen.width}x${screen.height}`,
                viewport_size: `${window.innerWidth}x${window.innerHeight}`,
                color_depth: screen.colorDepth,
                pixel_ratio: window.devicePixelRatio || 1,
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                language: navigator.language,
                languages: navigator.languages ? navigator.languages.join(',') : '',
                platform: navigator.platform,
                touch_support: 'ontouchstart' in window,
                connection_type: this.getConnectionType(),
                user_agent: navigator.userAgent
            };
        },
        
        /**
         * Get connection type if available
         */
        getConnectionType: function() {
            if (navigator.connection) {
                return navigator.connection.effectiveType || navigator.connection.type;
            }
            return 'unknown';
        },
        
        /**
         * Queue event for batch sending
         */
        queueEvent: function(event) {
            this.state.eventQueue.push(event);
            
            if (this.state.eventQueue.length >= this.config.batchSize) {
                this.flush();
            }
        },
        
        /**
         * Flush queued events to server
         */
        flush: function() {
            if (this.state.eventQueue.length === 0) {
                return;
            }
            
            const events = this.state.eventQueue.slice();
            this.state.eventQueue = [];
            
            // Send events to server
            this.sendEvents(events);
        },
        
        /**
         * Send events to analytics API
         */
        sendEvents: function(events) {
            // For now, send each event individually
            // In production, you might want to implement bulk sending
            events.forEach(event => {
                this.sendToAPI('track-action', {
                    action_type: event.action_type,
                    data: event
                });
            });
        },
        
        /**
         * Send data to API endpoint
         */
        sendToAPI: function(action, data) {
            const url = `${this.config.apiUrl}?action=${action}`;
            
            // Use sendBeacon if available for better reliability
            if (navigator.sendBeacon) {
                const formData = new FormData();
                formData.append('data', JSON.stringify(data));
                navigator.sendBeacon(url, formData);
            } else {
                // Fallback to fetch with keepalive
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data),
                    keepalive: true
                }).catch(error => {
                    this.log('Failed to send analytics data:', error);
                });
            }
        },
        
        /**
         * Send search-specific data
         */
        sendSearchData: function(searchData) {
            this.sendToAPI('track-search', searchData);
        },
        
        /**
         * Send search click data
         */
        sendSearchClickData: function(query, position, resultId, resultType) {
            // This would update the existing search record
            fetch(`${this.config.apiUrl}?action=track-search-click`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    query: query,
                    result_position: position,
                    result_id: resultId,
                    result_type: resultType
                })
            }).catch(error => {
                this.log('Failed to send search click data:', error);
            });
        },
        
        /**
         * Start periodic event flushing
         */
        startPeriodicFlush: function() {
            setInterval(() => {
                this.flush();
            }, this.config.flushInterval);
        },
        
        /**
         * Get or create session ID
         */
        getSessionId: function() {
            let sessionId = sessionStorage.getItem('analytics_session_id');
            
            if (!sessionId || this.isSessionExpired()) {
                sessionId = this.generateSessionId();
                sessionStorage.setItem('analytics_session_id', sessionId);
                sessionStorage.setItem('analytics_session_start', Date.now().toString());
            }
            
            return sessionId;
        },
        
        /**
         * Check if session is expired
         */
        isSessionExpired: function() {
            const sessionStart = sessionStorage.getItem('analytics_session_start');
            if (!sessionStart) return true;
            
            return (Date.now() - parseInt(sessionStart)) > this.config.sessionTimeout;
        },
        
        /**
         * Generate unique session ID
         */
        generateSessionId: function() {
            return 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        },
        
        /**
         * Check if URL is a download link
         */
        isDownloadLink: function(url) {
            const downloadExtensions = [
                'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
                'zip', 'rar', '7z', 'tar', 'gz',
                'mp3', 'mp4', 'avi', 'mov', 'wmv',
                'jpg', 'jpeg', 'png', 'gif', 'svg',
                'txt', 'csv', 'xml', 'json'
            ];
            
            const extension = this.getFileType(url);
            return downloadExtensions.includes(extension.toLowerCase());
        },
        
        /**
         * Get file type from URL
         */
        getFileType: function(url) {
            const path = url.split('?')[0]; // Remove query parameters
            const parts = path.split('.');
            return parts.length > 1 ? parts.pop().toLowerCase() : '';
        },
        
        /**
         * Get filename from URL
         */
        getFilenameFromUrl: function(url) {
            const path = url.split('?')[0];
            return path.split('/').pop() || 'unknown';
        },
        
        /**
         * Debug logging
         */
        log: function(...args) {
            if (this.config.debug && console && console.log) {
                console.log('[CampusAnalytics]', ...args);
            }
        }
    };
    
    // Expose to global scope
    window.CampusAnalytics = CampusAnalytics;
    
    // Auto-initialize if configuration is provided
    if (window.CampusAnalyticsConfig) {
        CampusAnalytics.init(window.CampusAnalyticsConfig);
    }
    
})(window, document);

// Enhanced tracking helpers for specific Campus Hub features
window.CampusAnalytics.extend = function(extensions) {
    Object.assign(this, extensions);
};

// Campus Hub specific tracking extensions
window.CampusAnalytics.extend({
    /**
     * Track news article reading
     */
    trackNewsRead: function(newsId, timeSpent, scrollDepth) {
        this.trackContentEngagement('news', newsId, {
            action: 'read',
            time_spent: timeSpent,
            scroll_depth: scrollDepth
        });
    },
    
    /**
     * Track event registration
     */
    trackEventRegistration: function(eventId, registrationType) {
        this.trackConversion('event_registration', `event_${eventId}`, null, {
            event_id: eventId,
            registration_type: registrationType
        });
    },
    
    /**
     * Track user login
     */
    trackLogin: function(userId, loginMethod) {
        this.trackConversion('login', 'user_login', null, {
            user_id: userId,
            login_method: loginMethod
        });
    },
    
    /**
     * Track search with Campus Hub context
     */
    trackCampusSearch: function(query, filters, resultsCount) {
        this.trackSearch(query, resultsCount, 'campus_search');
        
        this.track('campus_search', {
            query: query,
            filters: filters,
            results_count: resultsCount
        });
    },
    
    /**
     * Track social sharing
     */
    trackSocialShare: function(contentType, contentId, platform) {
        this.track('social_share', {
            content_type: contentType,
            content_id: contentId,
            platform: platform
        });
    }
});

// Convenience function for quick initialization
window.initCampusAnalytics = function(options) {
    window.CampusAnalytics.init(options);
};
