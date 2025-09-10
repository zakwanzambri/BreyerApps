/**
 * Campus Hub Analytics Configuration
 * Include this script after analytics-tracker.js to automatically configure tracking
 */

// Global analytics configuration
window.CampusAnalyticsConfig = {
    apiUrl: 'php/api/analytics.php',
    trackPageViews: true,
    trackClicks: true,
    trackScrolling: true,
    trackFormSubmissions: true,
    trackFileDownloads: true,
    trackSearches: true,
    trackVideoEngagement: false,
    debug: false // Set to true for development
};

// Initialize analytics when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the analytics tracker
    CampusAnalytics.init(CampusAnalyticsConfig);
    
    // Set up additional tracking for Campus Hub specific elements
    setupCampusHubTracking();
});

/**
 * Set up Campus Hub specific analytics tracking
 */
function setupCampusHubTracking() {
    
    // Track news article clicks
    document.addEventListener('click', function(e) {
        if (e.target.closest('.news-card, .news-item')) {
            const newsCard = e.target.closest('.news-card, .news-item');
            const newsId = newsCard.dataset.newsId || newsCard.id;
            const newsTitle = newsCard.querySelector('.news-title, h3, h4')?.textContent || 'Unknown';
            
            CampusAnalytics.track('news_click', {
                news_id: newsId,
                news_title: newsTitle,
                click_source: 'news_listing'
            });
        }
    });
    
    // Track event clicks
    document.addEventListener('click', function(e) {
        if (e.target.closest('.event-card, .event-item')) {
            const eventCard = e.target.closest('.event-card, .event-item');
            const eventId = eventCard.dataset.eventId || eventCard.id;
            const eventTitle = eventCard.querySelector('.event-title, h3, h4')?.textContent || 'Unknown';
            
            CampusAnalytics.track('event_click', {
                event_id: eventId,
                event_title: eventTitle,
                click_source: 'event_listing'
            });
        }
    });
    
    // Track search form submissions
    const searchForms = document.querySelectorAll('form[action*="search"], .search-form, #searchForm');
    searchForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const searchInput = form.querySelector('input[type="search"], input[name*="search"], input[name*="query"], #searchQuery');
            if (searchInput) {
                const query = searchInput.value.trim();
                if (query) {
                    CampusAnalytics.trackCampusSearch(query, {}, 0); // Results count will be updated later
                }
            }
        });
    });
    
    // Track real-time search suggestions
    const searchInputs = document.querySelectorAll('input[type="search"], input[name*="search"], #searchQuery');
    searchInputs.forEach(input => {
        let searchTimeout;
        input.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (this.value.length >= 3) {
                    CampusAnalytics.track('search_suggestion', {
                        query: this.value,
                        query_length: this.value.length
                    });
                }
            }, 500);
        });
    });
    
    // Track navigation menu usage
    document.addEventListener('click', function(e) {
        if (e.target.closest('.navbar-nav, .nav-menu, .main-nav')) {
            const navItem = e.target.closest('a, button');
            if (navItem) {
                CampusAnalytics.track('navigation_click', {
                    nav_item: navItem.textContent.trim(),
                    nav_href: navItem.href || null,
                    nav_section: navItem.closest('.navbar-nav, .nav-menu, .main-nav')?.className || 'main'
                });
            }
        }
    });
    
    // Track social media sharing
    document.addEventListener('click', function(e) {
        if (e.target.closest('.social-share, .share-button, [class*="share"]')) {
            const shareButton = e.target.closest('.social-share, .share-button, [class*="share"]');
            const platform = shareButton.dataset.platform || 
                           shareButton.className.match(/(facebook|twitter|instagram|linkedin|whatsapp)/i)?.[0] || 
                           'unknown';
            
            // Try to get content information
            const contentCard = shareButton.closest('.news-card, .event-card, .content-card');
            let contentType = 'page';
            let contentId = null;
            
            if (contentCard) {
                contentType = contentCard.classList.contains('news-card') ? 'news' : 
                             contentCard.classList.contains('event-card') ? 'event' : 'content';
                contentId = contentCard.dataset.newsId || contentCard.dataset.eventId || contentCard.id;
            }
            
            CampusAnalytics.trackSocialShare(contentType, contentId, platform);
        }
    });
    
    // Track contact form submissions
    const contactForms = document.querySelectorAll('form[action*="contact"], .contact-form, #contactForm');
    contactForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            CampusAnalytics.trackConversion('contact_form', 'contact_submission', null, {
                form_id: form.id || 'contact_form',
                form_fields: form.elements.length
            });
        });
    });
    
    // Track user registration/login forms
    const authForms = document.querySelectorAll('form[action*="login"], form[action*="register"], .login-form, .register-form');
    authForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const isLogin = form.action.includes('login') || form.classList.contains('login-form');
            const isRegister = form.action.includes('register') || form.classList.contains('register-form');
            
            if (isLogin) {
                CampusAnalytics.trackConversion('login_attempt', 'user_login', null, {
                    form_id: form.id || 'login_form'
                });
            } else if (isRegister) {
                CampusAnalytics.trackConversion('registration_attempt', 'user_registration', null, {
                    form_id: form.id || 'register_form'
                });
            }
        });
    });
    
    // Track video interactions (if videos are present)
    const videos = document.querySelectorAll('video');
    videos.forEach(video => {
        video.addEventListener('play', function() {
            CampusAnalytics.track('video_play', {
                video_src: this.src || this.currentSrc,
                video_duration: this.duration
            });
        });
        
        video.addEventListener('ended', function() {
            CampusAnalytics.track('video_complete', {
                video_src: this.src || this.currentSrc,
                video_duration: this.duration
            });
        });
    });
    
    // Track modal/popup interactions
    document.addEventListener('click', function(e) {
        if (e.target.matches('[data-bs-toggle="modal"], [data-toggle="modal"]')) {
            const modalTarget = e.target.dataset.bsTarget || e.target.dataset.target;
            CampusAnalytics.track('modal_open', {
                modal_target: modalTarget,
                trigger_text: e.target.textContent.trim()
            });
        }
    });
    
    // Track error pages (404, 500, etc.)
    if (document.body.classList.contains('error-page') || 
        document.title.includes('404') || 
        document.title.includes('Error')) {
        CampusAnalytics.track('error_page', {
            error_type: document.title.includes('404') ? '404' : 'server_error',
            page_url: window.location.href,
            referrer: document.referrer
        });
    }
    
    // Track file downloads with more context
    document.addEventListener('click', function(e) {
        if (e.target.href && CampusAnalytics.isDownloadLink(e.target.href)) {
            const downloadContext = e.target.closest('.news-card, .event-card, .resource-section');
            let contextType = 'general';
            let contextId = null;
            
            if (downloadContext) {
                if (downloadContext.classList.contains('news-card')) {
                    contextType = 'news';
                    contextId = downloadContext.dataset.newsId;
                } else if (downloadContext.classList.contains('event-card')) {
                    contextType = 'event';
                    contextId = downloadContext.dataset.eventId;
                }
            }
            
            CampusAnalytics.track('download_with_context', {
                file_url: e.target.href,
                file_name: CampusAnalytics.getFilenameFromUrl(e.target.href),
                file_type: CampusAnalytics.getFileType(e.target.href),
                context_type: contextType,
                context_id: contextId,
                link_text: e.target.textContent.trim()
            });
        }
    });
}

/**
 * Track page-specific events based on URL
 */
function trackPageSpecificEvents() {
    const path = window.location.pathname;
    const searchParams = new URLSearchParams(window.location.search);
    
    // Track search results page
    if (path.includes('search') || searchParams.has('search') || searchParams.has('q')) {
        const query = searchParams.get('search') || searchParams.get('q') || '';
        const resultsCount = document.querySelectorAll('.search-result, .result-item').length;
        
        if (query) {
            CampusAnalytics.trackCampusSearch(query, Object.fromEntries(searchParams), resultsCount);
        }
    }
    
    // Track individual content pages
    if (path.includes('news/') && searchParams.has('id')) {
        const newsId = searchParams.get('id');
        CampusAnalytics.trackNewsRead(newsId, 0, 0); // Time and scroll will be updated on exit
    }
    
    if (path.includes('events/') && searchParams.has('id')) {
        const eventId = searchParams.get('id');
        CampusAnalytics.track('event_view', {
            event_id: eventId,
            view_source: 'direct_link'
        });
    }
}

// Call page-specific tracking
document.addEventListener('DOMContentLoaded', trackPageSpecificEvents);

/**
 * Helper function to manually track specific Campus Hub events
 */
window.trackCampusEvent = function(eventType, data) {
    if (window.CampusAnalytics && window.CampusAnalytics.state.initialized) {
        CampusAnalytics.track(eventType, data);
    }
};

/**
 * Helper function to track successful user actions
 */
window.trackUserSuccess = function(actionType, details) {
    if (window.CampusAnalytics && window.CampusAnalytics.state.initialized) {
        CampusAnalytics.trackConversion(actionType, `${actionType}_success`, null, details);
    }
};

/**
 * Helper function for tracking external link clicks
 */
document.addEventListener('click', function(e) {
    if (e.target.tagName === 'A' && e.target.href) {
        const url = new URL(e.target.href, window.location.origin);
        
        // Check if it's an external link
        if (url.hostname !== window.location.hostname) {
            CampusAnalytics.track('external_link_click', {
                external_url: e.target.href,
                external_domain: url.hostname,
                link_text: e.target.textContent.trim(),
                context: e.target.closest('.news-card, .event-card, .content-section')?.className || 'general'
            });
        }
    }
});

console.log('Campus Hub Analytics configuration loaded');
