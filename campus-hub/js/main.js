// Campus Hub - Main JavaScript File
// Author: Campus Hub Development Team
// Description: Interactive functionality for the Campus Hub portal

document.addEventListener('DOMContentLoaded', function() {
    // Initialize the app
    initializeApp();
    
    // Set up event listeners
    setupEventListeners();
    
    // Load user preferences
    loadUserPreferences();
    
    // Update dynamic content
    updateDynamicContent();
});

// Initialize application
function initializeApp() {
    console.log('Campus Hub initialized');
    
    // Set current date and time
    updateDateTime();
    setInterval(updateDateTime, 60000); // Update every minute
    
    // Load notifications
    loadNotifications();
    
    // Initialize search
    initializeSearch();
    
    // Check for updates
    checkForUpdates();
}

// Set up all event listeners
function setupEventListeners() {
    // Mobile menu toggle
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', toggleMobileMenu);
    }
    
    // Navigation links
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', handleNavigation);
    });
    
    // Global search
    const searchInput = document.getElementById('globalSearch');
    if (searchInput) {
        searchInput.addEventListener('input', handleGlobalSearch);
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch(this.value);
            }
        });
    }
    
    // Profile menu
    const profileMenu = document.querySelector('.profile-menu');
    if (profileMenu) {
        profileMenu.addEventListener('click', toggleProfileMenu);
    }
    
    // Notifications
    const notificationsBtn = document.querySelector('.notifications-btn');
    if (notificationsBtn) {
        notificationsBtn.addEventListener('click', toggleNotifications);
    }
    
    // Card actions
    const cardActions = document.querySelectorAll('.card-action');
    cardActions.forEach(action => {
        action.addEventListener('click', handleCardAction);
    });
    
    // Quick links
    const quickLinks = document.querySelectorAll('.quick-link');
    quickLinks.forEach(link => {
        link.addEventListener('click', handleQuickLink);
    });
    
    // Service items
    const serviceItems = document.querySelectorAll('.service-item');
    serviceItems.forEach(item => {
        item.addEventListener('click', handleServiceClick);
    });
    
    // Course actions
    const courseActions = document.querySelectorAll('.action-link');
    courseActions.forEach(action => {
        action.addEventListener('click', handleCourseAction);
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', closeDropdowns);
    
    // Keyboard shortcuts
    document.addEventListener('keydown', handleKeyboardShortcuts);
}

// Handle mobile menu toggle
function toggleMobileMenu() {
    const header = document.querySelector('.header');
    const navMenu = document.querySelector('.nav-menu');
    
    header.classList.toggle('mobile-menu-open');
    navMenu.classList.toggle('mobile-menu-visible');
    
    // Update icon
    const icon = this.querySelector('i');
    if (header.classList.contains('mobile-menu-open')) {
        icon.className = 'fas fa-times';
    } else {
        icon.className = 'fas fa-bars';
    }
}

// Handle navigation
function handleNavigation(e) {
    e.preventDefault();
    
    // Remove active class from all nav links
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
    });
    
    // Add active class to clicked link
    this.classList.add('active');
    
    // Get target section
    const target = this.getAttribute('href').substring(1);
    
    // Smooth scroll to section or load content
    scrollToSection(target);
    
    // Close mobile menu if open
    const header = document.querySelector('.header');
    if (header.classList.contains('mobile-menu-open')) {
        toggleMobileMenu();
    }
}

// Handle global search
function handleGlobalSearch(e) {
    const query = e.target.value.toLowerCase().trim();
    
    if (query.length >= 2) {
        // Show search suggestions
        showSearchSuggestions(query);
    } else {
        // Hide search suggestions
        hideSearchSuggestions();
    }
}

// Perform search
function performSearch(query) {
    if (!query.trim()) return;
    
    console.log('Searching for:', query);
    
    // Add to search history
    addToSearchHistory(query);
    
    // Show search results (in a real app, this would make an API call)
    showSearchResults(query);
    
    // Clear search input
    document.getElementById('globalSearch').value = '';
    hideSearchSuggestions();
}

// Show search suggestions
function showSearchSuggestions(query) {
    // Sample suggestions based on query
    const suggestions = [
        'Academic Calendar',
        'Course Materials',
        'Campus Services',
        'Library Hours',
        'Dining Menu',
        'Shuttle Schedule',
        'Grade Portal',
        'Financial Aid',
        'Student Email',
        'Campus Map'
    ].filter(item => item.toLowerCase().includes(query));
    
    // Create suggestions dropdown (simplified)
    let suggestionsContainer = document.querySelector('.search-suggestions');
    if (!suggestionsContainer) {
        suggestionsContainer = document.createElement('div');
        suggestionsContainer.className = 'search-suggestions';
        document.querySelector('.search-container').appendChild(suggestionsContainer);
    }
    
    if (suggestions.length > 0) {
        suggestionsContainer.innerHTML = suggestions.slice(0, 5).map(suggestion => 
            `<div class="suggestion-item" data-suggestion="${suggestion}">${suggestion}</div>`
        ).join('');
        
        suggestionsContainer.style.display = 'block';
        
        // Add click listeners to suggestions
        suggestionsContainer.querySelectorAll('.suggestion-item').forEach(item => {
            item.addEventListener('click', function() {
                performSearch(this.dataset.suggestion);
            });
        });
    } else {
        suggestionsContainer.style.display = 'none';
    }
}

// Hide search suggestions
function hideSearchSuggestions() {
    const suggestionsContainer = document.querySelector('.search-suggestions');
    if (suggestionsContainer) {
        suggestionsContainer.style.display = 'none';
    }
}

// Toggle profile menu
function toggleProfileMenu() {
    // Create profile dropdown if it doesn't exist
    let profileDropdown = document.querySelector('.profile-dropdown');
    if (!profileDropdown) {
        profileDropdown = document.createElement('div');
        profileDropdown.className = 'profile-dropdown';
        profileDropdown.innerHTML = `
            <div class="profile-dropdown-item">
                <i class="fas fa-user"></i>
                <span>My Profile</span>
            </div>
            <div class="profile-dropdown-item">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </div>
            <div class="profile-dropdown-item">
                <i class="fas fa-question-circle"></i>
                <span>Help</span>
            </div>
            <div class="profile-dropdown-divider"></div>
            <div class="profile-dropdown-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Sign Out</span>
            </div>
        `;
        this.appendChild(profileDropdown);
        
        // Add click listeners
        profileDropdown.querySelectorAll('.profile-dropdown-item').forEach(item => {
            item.addEventListener('click', handleProfileAction);
        });
    }
    
    profileDropdown.classList.toggle('visible');
}

// Toggle notifications
function toggleNotifications() {
    // Create notifications dropdown if it doesn't exist
    let notificationsDropdown = document.querySelector('.notifications-dropdown');
    if (!notificationsDropdown) {
        notificationsDropdown = document.createElement('div');
        notificationsDropdown.className = 'notifications-dropdown';
        notificationsDropdown.innerHTML = `
            <div class="notifications-header">
                <h4>Notifications</h4>
                <button class="mark-all-read">Mark all read</button>
            </div>
            <div class="notifications-list">
                <div class="notification-item unread">
                    <i class="fas fa-calendar text-primary"></i>
                    <div class="notification-content">
                        <p><strong>Math Quiz</strong> reminder</p>
                        <span class="notification-time">2 hours ago</span>
                    </div>
                </div>
                <div class="notification-item unread">
                    <i class="fas fa-book text-success"></i>
                    <div class="notification-content">
                        <p><strong>New assignment</strong> posted in History</p>
                        <span class="notification-time">4 hours ago</span>
                    </div>
                </div>
                <div class="notification-item">
                    <i class="fas fa-bullhorn text-warning"></i>
                    <div class="notification-content">
                        <p><strong>Career Fair</strong> this Friday</p>
                        <span class="notification-time">1 day ago</span>
                    </div>
                </div>
            </div>
            <div class="notifications-footer">
                <a href="#">View all notifications</a>
            </div>
        `;
        document.body.appendChild(notificationsDropdown);
        
        // Position the dropdown
        const rect = this.getBoundingClientRect();
        notificationsDropdown.style.position = 'fixed';
        notificationsDropdown.style.top = (rect.bottom + 10) + 'px';
        notificationsDropdown.style.right = (window.innerWidth - rect.right) + 'px';
    }
    
    notificationsDropdown.classList.toggle('visible');
}

// Handle card actions
function handleCardAction(e) {
    e.preventDefault();
    const cardTitle = this.closest('.dashboard-card').querySelector('h3').textContent;
    console.log('Card action clicked:', cardTitle);
    
    // Show modal or navigate to detailed view
    showDetailedView(cardTitle);
}

// Handle quick link clicks
function handleQuickLink(e) {
    e.preventDefault();
    const linkText = this.querySelector('span').textContent;
    console.log('Quick link clicked:', linkText);
    
    // Add click animation
    this.style.transform = 'scale(0.95)';
    setTimeout(() => {
        this.style.transform = 'scale(1)';
    }, 150);
    
    // In a real app, this would navigate to the actual service
    showToast(`Opening ${linkText}...`);
}

// Handle service clicks
function handleServiceClick() {
    const serviceName = this.querySelector('h4').textContent;
    console.log('Service clicked:', serviceName);
    
    // Add click effect
    this.style.transform = 'translateY(-2px)';
    setTimeout(() => {
        this.style.transform = 'translateY(0)';
    }, 200);
    
    showServiceDetails(serviceName);
}

// Handle course actions
function handleCourseAction(e) {
    e.preventDefault();
    const actionText = this.textContent;
    const courseName = this.closest('.course-item').querySelector('h4').textContent;
    
    console.log('Course action:', actionText, 'for', courseName);
    showToast(`Opening ${actionText} for ${courseName}`);
}

// Handle profile actions
function handleProfileAction(e) {
    e.stopPropagation();
    const actionText = this.querySelector('span').textContent;
    console.log('Profile action:', actionText);
    
    // Close dropdown
    document.querySelector('.profile-dropdown').classList.remove('visible');
    
    // Handle action
    switch(actionText) {
        case 'Sign Out':
            handleSignOut();
            break;
        case 'Settings':
            showSettings();
            break;
        case 'My Profile':
            showProfile();
            break;
        case 'Help':
            showHelp();
            break;
    }
}

// Close dropdowns when clicking outside
function closeDropdowns(e) {
    if (!e.target.closest('.profile-menu')) {
        const profileDropdown = document.querySelector('.profile-dropdown');
        if (profileDropdown) {
            profileDropdown.classList.remove('visible');
        }
    }
    
    if (!e.target.closest('.notifications-btn')) {
        const notificationsDropdown = document.querySelector('.notifications-dropdown');
        if (notificationsDropdown) {
            notificationsDropdown.classList.remove('visible');
        }
    }
    
    if (!e.target.closest('.search-container')) {
        hideSearchSuggestions();
    }
}

// Handle keyboard shortcuts
function handleKeyboardShortcuts(e) {
    // Ctrl/Cmd + K for search
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        document.getElementById('globalSearch').focus();
    }
    
    // Escape to close dropdowns
    if (e.key === 'Escape') {
        closeDropdowns({target: document.body});
    }
}

// Utility functions
function updateDateTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    
    // Update any time displays
    const timeElements = document.querySelectorAll('.current-time');
    timeElements.forEach(element => {
        element.textContent = timeString;
    });
}

function loadNotifications() {
    // Simulate loading notifications
    const notificationBadge = document.querySelector('.notification-badge');
    if (notificationBadge) {
        // In a real app, this would fetch from an API
        const unreadCount = 3;
        notificationBadge.textContent = unreadCount;
        notificationBadge.style.display = unreadCount > 0 ? 'block' : 'none';
    }
}

function initializeSearch() {
    // Set up search functionality
    const searchInput = document.getElementById('globalSearch');
    if (searchInput) {
        searchInput.setAttribute('placeholder', 'Search campus resources...');
    }
}

function checkForUpdates() {
    // Check for app updates
    console.log('Checking for updates...');
}

function loadUserPreferences() {
    // Load user preferences from localStorage
    const preferences = JSON.parse(localStorage.getItem('campusHubPreferences')) || {};
    
    // Apply theme
    if (preferences.theme) {
        document.body.classList.add(`theme-${preferences.theme}`);
    }
    
    // Apply other preferences
    if (preferences.compactMode) {
        document.body.classList.add('compact-mode');
    }
}

function saveUserPreferences(preferences) {
    localStorage.setItem('campusHubPreferences', JSON.stringify(preferences));
}

function updateDynamicContent() {
    // Update weather
    updateWeather();
    
    // Update campus status
    updateCampusStatus();
    
    // Update dining hours
    updateDiningHours();
}

function updateWeather() {
    // In a real app, this would fetch from a weather API
    const weatherInfo = {
        temperature: 72,
        condition: 'Sunny',
        humidity: 45,
        wind: 8
    };
    
    // Update weather display
    const tempElement = document.querySelector('.temp');
    const conditionElement = document.querySelector('.condition');
    
    if (tempElement) tempElement.textContent = `${weatherInfo.temperature}°F`;
    if (conditionElement) conditionElement.textContent = weatherInfo.condition;
}

function updateCampusStatus() {
    // Update campus operational status
    const statusItem = document.querySelector('.status-item');
    if (statusItem) {
        statusItem.innerHTML = `
            <i class="fas fa-check-circle text-success"></i>
            <span>All systems operational</span>
        `;
    }
}

function updateDiningHours() {
    // Update dining service hours
    const diningService = document.querySelector('.service-item:has(.fa-utensils) p');
    if (diningService) {
        const now = new Date();
        const currentHour = now.getHours();
        
        if (currentHour < 21) {
            diningService.textContent = 'Open until 9 PM';
        } else {
            diningService.textContent = 'Closed - Opens 7 AM';
        }
    }
}

// Helper functions
function scrollToSection(sectionId) {
    const section = document.getElementById(sectionId);
    if (section) {
        section.scrollIntoView({ behavior: 'smooth' });
    }
}

function showToast(message, type = 'info') {
    // Create toast notification
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <i class="fas fa-info-circle"></i>
        <span>${message}</span>
        <button class="toast-close">&times;</button>
    `;
    
    // Add to page
    document.body.appendChild(toast);
    
    // Show toast
    setTimeout(() => toast.classList.add('show'), 100);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
    
    // Close button
    toast.querySelector('.toast-close').addEventListener('click', () => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    });
}

function showDetailedView(cardTitle) {
    console.log('Showing detailed view for:', cardTitle);
    showToast(`Loading ${cardTitle} details...`);
}

function showServiceDetails(serviceName) {
    console.log('Showing service details for:', serviceName);
    showToast(`Loading ${serviceName} information...`);
}

function showSearchResults(query) {
    console.log('Showing search results for:', query);
    showToast(`Searching for "${query}"...`);
}

function addToSearchHistory(query) {
    let searchHistory = JSON.parse(localStorage.getItem('searchHistory')) || [];
    
    // Remove if already exists
    searchHistory = searchHistory.filter(item => item !== query);
    
    // Add to beginning
    searchHistory.unshift(query);
    
    // Keep only last 10 searches
    searchHistory = searchHistory.slice(0, 10);
    
    localStorage.setItem('searchHistory', JSON.stringify(searchHistory));
}

function handleSignOut() {
    if (confirm('Are you sure you want to sign out?')) {
        // Clear user data
        localStorage.removeItem('campusHubPreferences');
        localStorage.removeItem('searchHistory');
        
        // Redirect to login page
        showToast('Signing out...', 'info');
        setTimeout(() => {
            window.location.href = 'login.html';
        }, 1500);
    }
}

function showSettings() {
    showToast('Opening settings...');
}

function showProfile() {
    showToast('Opening profile...');
}

function showHelp() {
    showToast('Opening help center...');
}

// Performance monitoring
function trackPageLoad() {
    window.addEventListener('load', function() {
        const loadTime = performance.now();
        console.log(`Campus Hub loaded in ${loadTime.toFixed(2)}ms`);
    });
}

// Initialize performance tracking
trackPageLoad();

// Accessibility improvements
function enhanceAccessibility() {
    // Add keyboard navigation for cards
    const cards = document.querySelectorAll('.dashboard-card');
    cards.forEach(card => {
        card.setAttribute('tabindex', '0');
        card.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                const action = this.querySelector('.card-action');
                if (action) action.click();
            }
        });
    });
    
    // Add screen reader announcements for dynamic content
    const announcer = document.createElement('div');
    announcer.setAttribute('aria-live', 'polite');
    announcer.setAttribute('aria-atomic', 'true');
    announcer.className = 'sr-only';
    document.body.appendChild(announcer);
    
    window.announceToScreenReader = function(message) {
        announcer.textContent = message;
        setTimeout(() => announcer.textContent = '', 1000);
    };
}

// Initialize accessibility features
document.addEventListener('DOMContentLoaded', enhanceAccessibility);

// Export functions for testing (if needed)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        initializeApp,
        handleGlobalSearch,
        performSearch,
        updateDynamicContent
    };
}
