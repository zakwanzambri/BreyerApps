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
    // Sample suggestions based on query - updated for college programs
    const suggestions = [
        'Academic Calendar',
        'Course Materials',
        'Campus Services',
        'Library Hours',
        'Cafeteria Menu',
        'Shuttle Schedule',
        'Grade Portal',
        'Financial Aid',
        'Student Email',
        'Campus Map',
        'Culinary Arts Program',
        'Computer System Lab',
        'Electrical Workshop',
        'Administrative Office',
        'F&B Management',
        'Practical Exam Schedule'
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
    const cardTitle = this.closest('.dashboard-card').querySelector('h3').textContent.replace(/^\s*\S+\s*/, ''); // Remove icon
    console.log('Card action clicked:', cardTitle);
    
    // Route to detailed views
    switch(cardTitle.toLowerCase()) {
        case 'academic calendar':
            showAcademicCalendar();
            break;
        case 'course materials':
            showAllCourses();
            break;
        case 'campus services':
            showAllServices();
            break;
        case 'news & announcements':
            showAllNews();
            break;
        case 'quick links':
            showAllQuickLinks();
            break;
        case 'campus weather':
            showWeatherDetails();
            break;
        default:
            showDetailedView(cardTitle);
    }
}

// Detailed view functions
function showAcademicCalendar() {
    createModal('Academic Calendar', `
        <div class="calendar-content">
            <h3>Full Academic Calendar</h3>
            <div class="calendar-month">
                <h4>September 2025</h4>
                <div class="calendar-events-full">
                    <div class="calendar-event">
                        <span class="event-date-full">Sept 15</span>
                        <span class="event-title">Culinary Arts Practical Exam</span>
                        <span class="event-location">Kitchen Laboratory A</span>
                        <span class="event-time">9:00 AM - 12:00 PM</span>
                    </div>
                    <div class="calendar-event">
                        <span class="event-date-full">Sept 18</span>
                        <span class="event-title">Computer System Project Due</span>
                        <span class="event-location">Online Submission</span>
                        <span class="event-time">11:59 PM</span>
                    </div>
                    <div class="calendar-event">
                        <span class="event-date-full">Sept 22</span>
                        <span class="event-title">Electrical Wiring Workshop</span>
                        <span class="event-location">Technical Lab Block B</span>
                        <span class="event-time">2:00 PM - 5:00 PM</span>
                    </div>
                    <div class="calendar-event">
                        <span class="event-date-full">Sept 25</span>
                        <span class="event-title">F&B Management Presentation</span>
                        <span class="event-location">Conference Room</span>
                        <span class="event-time">10:00 AM - 12:00 PM</span>
                    </div>
                    <div class="calendar-event">
                        <span class="event-date-full">Sept 30</span>
                        <span class="event-title">Administrative Management Final</span>
                        <span class="event-location">Exam Hall</span>
                        <span class="event-time">9:00 AM - 11:00 AM</span>
                    </div>
                </div>
            </div>
        </div>
    `);
}

function showAllCourses() {
    createModal('All Course Materials', `
        <div class="courses-content">
            <h3>Course Materials & Resources</h3>
            <div class="course-tabs">
                <button class="tab-btn active" onclick="switchCourseTab('all')">All Courses</button>
                <button class="tab-btn" onclick="switchCourseTab('current')">Current Semester</button>
                <button class="tab-btn" onclick="switchCourseTab('completed')">Completed</button>
            </div>
            <div class="courses-grid">
                <div class="course-card">
                    <h4>Diploma in Culinary Arts</h4>
                    <p>Semester 3 of 6</p>
                    <div class="course-progress-bar">
                        <div class="progress-fill" style="width: 88%"></div>
                    </div>
                    <div class="course-links">
                        <a href="#" onclick="downloadMaterial('culinary-syllabus')">Download Syllabus</a>
                        <a href="#" onclick="viewAssignments('culinary')">View Assignments</a>
                    </div>
                </div>
                <div class="course-card">
                    <h4>Diploma in Computer System</h4>
                    <p>Semester 3 of 6</p>
                    <div class="course-progress-bar">
                        <div class="progress-fill" style="width: 85%"></div>
                    </div>
                    <div class="course-links">
                        <a href="#" onclick="downloadMaterial('cs-syllabus')">Download Syllabus</a>
                        <a href="#" onclick="viewAssignments('cs')">View Assignments</a>
                    </div>
                </div>
            </div>
        </div>
    `);
}

function showAllServices() {
    createModal('Campus Services Directory', `
        <div class="services-directory">
            <h3>Complete Services Directory</h3>
            <div class="service-categories">
                <div class="service-category">
                    <h4>Academic Services</h4>
                    <div class="service-items">
                        <div class="service-item" onclick="contactService('registrar')">
                            <i class="fas fa-graduation-cap"></i>
                            <span>Registrar Office</span>
                            <small>Registration & Records</small>
                        </div>
                        <div class="service-item" onclick="contactService('library')">
                            <i class="fas fa-books"></i>
                            <span>Library Services</span>
                            <small>Books & Digital Resources</small>
                        </div>
                    </div>
                </div>
                <div class="service-category">
                    <h4>Student Life</h4>
                    <div class="service-items">
                        <div class="service-item" onclick="contactService('dining')">
                            <i class="fas fa-utensils"></i>
                            <span>Food Services</span>
                            <small>Cafeteria & Catering</small>
                        </div>
                        <div class="service-item" onclick="contactService('housing')">
                            <i class="fas fa-home"></i>
                            <span>Student Housing</span>
                            <small>Dormitory & Accommodations</small>
                        </div>
                    </div>
                </div>
                <div class="service-category">
                    <h4>Health & Safety</h4>
                    <div class="service-items">
                        <div class="service-item" onclick="contactService('health')">
                            <i class="fas fa-heartbeat"></i>
                            <span>Health Center</span>
                            <small>Medical Services</small>
                        </div>
                        <div class="service-item" onclick="contactService('security')">
                            <i class="fas fa-shield-alt"></i>
                            <span>Campus Security</span>
                            <small>Safety & Emergency</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `);
}

function showAllNews() {
    createModal('Campus News & Announcements', `
        <div class="news-full">
            <h3>Latest Campus News</h3>
            <div class="news-filters">
                <button class="filter-btn active" onclick="filterNews('all')">All</button>
                <button class="filter-btn" onclick="filterNews('academic')">Academic</button>
                <button class="filter-btn" onclick="filterNews('events')">Events</button>
                <button class="filter-btn" onclick="filterNews('campus')">Campus</button>
            </div>
            <div class="news-articles">
                <article class="news-article">
                    <div class="article-meta">
                        <span class="category academic">Academic</span>
                        <span class="date">September 9, 2025</span>
                    </div>
                    <h4>New Semester Registration Guidelines</h4>
                    <p>Important updates regarding the registration process for the upcoming semester. All students are required to complete their course selection by September 20th...</p>
                    <a href="#" class="read-more">Read More</a>
                </article>
                <article class="news-article">
                    <div class="article-meta">
                        <span class="category events">Events</span>
                        <span class="date">September 8, 2025</span>
                    </div>
                    <h4>Industry Partnership Career Fair</h4>
                    <p>Join us for the biggest career fair of the year featuring 50+ companies from various industries including hospitality, technology, and manufacturing...</p>
                    <a href="#" class="read-more">Read More</a>
                </article>
            </div>
        </div>
    `);
}

function showWeatherDetails() {
    createModal('Campus Weather & Status', `
        <div class="weather-details">
            <h3>Campus Weather Information</h3>
            <div class="weather-extended">
                <div class="current-weather">
                    <div class="weather-main">
                        <i class="fas fa-sun weather-icon-large"></i>
                        <div class="weather-temp-large">
                            <span class="temp-large">72°F</span>
                            <span class="condition-large">Sunny</span>
                        </div>
                    </div>
                    <div class="weather-details-grid">
                        <div class="weather-detail-item">
                            <i class="fas fa-eye"></i>
                            <span class="label">Visibility</span>
                            <span class="value">10 km</span>
                        </div>
                        <div class="weather-detail-item">
                            <i class="fas fa-tint"></i>
                            <span class="label">Humidity</span>
                            <span class="value">45%</span>
                        </div>
                        <div class="weather-detail-item">
                            <i class="fas fa-wind"></i>
                            <span class="label">Wind Speed</span>
                            <span class="value">8 mph</span>
                        </div>
                        <div class="weather-detail-item">
                            <i class="fas fa-thermometer-half"></i>
                            <span class="label">Feels Like</span>
                            <span class="value">75°F</span>
                        </div>
                    </div>
                </div>
                <div class="campus-status-detail">
                    <h4>Campus Status</h4>
                    <div class="status-items">
                        <div class="status-detail">
                            <i class="fas fa-wifi text-success"></i>
                            <span>WiFi: Operational</span>
                        </div>
                        <div class="status-detail">
                            <i class="fas fa-power-off text-success"></i>
                            <span>Power: Normal</span>
                        </div>
                        <div class="status-detail">
                            <i class="fas fa-water text-success"></i>
                            <span>Water: Available</span>
                        </div>
                        <div class="status-detail">
                            <i class="fas fa-shield-alt text-success"></i>
                            <span>Security: Active</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `);
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
    
    // Route to specific services
    switch(linkText.toLowerCase()) {
        case 'student portal':
            openStudentPortal();
            break;
        case 'campus email':
            openCampusEmail();
            break;
        case 'lms':
            openLMS();
            break;
        case 'financial aid':
            openFinancialAid();
            break;
        case 'student id':
            openStudentID();
            break;
        case 'schedule':
            openSchedule();
            break;
        default:
            showToast(`Opening ${linkText}...`);
    }
}

// Quick link specific functions
function openStudentPortal() {
    createModal('Student Portal', `
        <div class="portal-content">
            <h3>Student Information Portal</h3>
            <div class="portal-section">
                <h4>Personal Information</h4>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Student ID:</label>
                        <span>2025001234</span>
                    </div>
                    <div class="info-item">
                        <label>Program:</label>
                        <span>Diploma in Computer System</span>
                    </div>
                    <div class="info-item">
                        <label>Semester:</label>
                        <span>3 of 6</span>
                    </div>
                    <div class="info-item">
                        <label>Status:</label>
                        <span class="status active">Active</span>
                    </div>
                </div>
            </div>
            <div class="portal-actions">
                <button class="btn-primary" onclick="updateProfile()">Update Profile</button>
                <button class="btn-secondary" onclick="downloadTranscript()">Download Transcript</button>
            </div>
        </div>
    `);
}

function openCampusEmail() {
    // Simulate opening email
    showToast('Redirecting to campus email...', 'info');
    setTimeout(() => {
        window.open('mailto:', '_blank');
    }, 1000);
}

function openLMS() {
    createModal('Learning Management System', `
        <div class="lms-content">
            <h3>Course Materials & Resources</h3>
            <div class="lms-courses">
                <div class="lms-course">
                    <h4>Database Management</h4>
                    <div class="course-materials">
                        <a href="#" class="material-link">Lecture Notes Week 1-4</a>
                        <a href="#" class="material-link">Assignment Guidelines</a>
                        <a href="#" class="material-link">Practice Exercises</a>
                    </div>
                </div>
                <div class="lms-course">
                    <h4>System Analysis</h4>
                    <div class="course-materials">
                        <a href="#" class="material-link">Chapter 1-3 Readings</a>
                        <a href="#" class="material-link">Case Study Materials</a>
                        <a href="#" class="material-link">Lab Instructions</a>
                    </div>
                </div>
            </div>
        </div>
    `);
}

function openFinancialAid() {
    createModal('Financial Aid Information', `
        <div class="financial-content">
            <h3>Financial Aid Status</h3>
            <div class="aid-summary">
                <div class="aid-item">
                    <label>Semester Fees:</label>
                    <span>RM 2,500.00</span>
                </div>
                <div class="aid-item">
                    <label>Scholarship Applied:</label>
                    <span class="aid-amount">RM 1,000.00</span>
                </div>
                <div class="aid-item">
                    <label>Outstanding Balance:</label>
                    <span class="balance">RM 1,500.00</span>
                </div>
                <div class="aid-item">
                    <label>Next Payment Due:</label>
                    <span>October 15, 2025</span>
                </div>
            </div>
            <div class="aid-actions">
                <button class="btn-primary" onclick="makePayment()">Make Payment</button>
                <button class="btn-secondary" onclick="applyScholarship()">Apply for Scholarship</button>
            </div>
        </div>
    `);
}

function openStudentID() {
    createModal('Digital Student ID', `
        <div class="student-id-content">
            <div class="id-card">
                <div class="id-header">
                    <h4>KOLEJ TEKNOLOGI</h4>
                    <span>Student Identification Card</span>
                </div>
                <div class="id-body">
                    <div class="id-photo">
                        <img src="https://via.placeholder.com/100x120/397AFB/FFFFFF?text=PHOTO" alt="Student Photo">
                    </div>
                    <div class="id-details">
                        <div class="detail-row">
                            <label>Name:</label>
                            <span>Ahmad Syafiq bin Abdullah</span>
                        </div>
                        <div class="detail-row">
                            <label>ID:</label>
                            <span>2025001234</span>
                        </div>
                        <div class="detail-row">
                            <label>Program:</label>
                            <span>Diploma in Computer System</span>
                        </div>
                        <div class="detail-row">
                            <label>Valid Until:</label>
                            <span>June 2027</span>
                        </div>
                    </div>
                </div>
                <div class="id-barcode">
                    <img src="https://via.placeholder.com/200x50/000000/FFFFFF?text=|||||||||||||||" alt="Barcode">
                </div>
            </div>
        </div>
    `);
}

function openSchedule() {
    createModal('Class Schedule', `
        <div class="schedule-content">
            <h3>Weekly Class Schedule</h3>
            <div class="schedule-grid">
                <div class="schedule-day">
                    <h4>Monday</h4>
                    <div class="schedule-item">
                        <span class="time">8:00 AM - 10:00 AM</span>
                        <span class="subject">Database Management</span>
                        <span class="room">Lab A</span>
                    </div>
                    <div class="schedule-item">
                        <span class="time">2:00 PM - 4:00 PM</span>
                        <span class="subject">System Analysis</span>
                        <span class="room">Room 201</span>
                    </div>
                </div>
                <div class="schedule-day">
                    <h4>Tuesday</h4>
                    <div class="schedule-item">
                        <span class="time">9:00 AM - 11:00 AM</span>
                        <span class="subject">Programming Logic</span>
                        <span class="room">Lab B</span>
                    </div>
                </div>
                <div class="schedule-day">
                    <h4>Wednesday</h4>
                    <div class="schedule-item">
                        <span class="time">10:00 AM - 12:00 PM</span>
                        <span class="subject">Network Fundamentals</span>
                        <span class="room">Lab C</span>
                    </div>
                </div>
            </div>
        </div>
    `);
}

// Handle service clicks
function handleServiceClick() {
    const serviceName = this.querySelector('h4').textContent;
    const serviceStatus = this.querySelector('p').textContent;
    console.log('Service clicked:', serviceName);
    
    // Add click effect
    this.style.transform = 'translateY(-2px)';
    setTimeout(() => {
        this.style.transform = 'translateY(0)';
    }, 200);
    
    // Route to specific services
    switch(serviceName.toLowerCase()) {
        case 'dining':
            openDiningService();
            break;
        case 'library':
            openLibraryService();
            break;
        case 'health center':
            openHealthService();
            break;
        case 'shuttle':
            openShuttleService();
            break;
        case 'gym':
            openGymService();
            break;
        case 'parking':
            openParkingService();
            break;
        default:
            showServiceDetails(serviceName);
    }
}

// Service-specific functions
function openDiningService() {
    createModal('Dining Services', `
        <div class="dining-content">
            <h3>Campus Cafeteria</h3>
            <div class="service-hours">
                <h4>Operating Hours</h4>
                <div class="hours-list">
                    <div class="hour-item">
                        <span>Monday - Friday:</span>
                        <span>7:00 AM - 9:00 PM</span>
                    </div>
                    <div class="hour-item">
                        <span>Saturday:</span>
                        <span>8:00 AM - 8:00 PM</span>
                    </div>
                    <div class="hour-item">
                        <span>Sunday:</span>
                        <span>9:00 AM - 7:00 PM</span>
                    </div>
                </div>
            </div>
            <div class="menu-today">
                <h4>Today's Menu</h4>
                <div class="menu-section">
                    <h5>Main Dishes</h5>
                    <ul>
                        <li>Nasi Lemak - RM 5.50</li>
                        <li>Mee Goreng - RM 4.50</li>
                        <li>Chicken Rice - RM 6.00</li>
                    </ul>
                </div>
                <div class="menu-section">
                    <h5>Beverages</h5>
                    <ul>
                        <li>Teh Tarik - RM 2.00</li>
                        <li>Kopi O - RM 1.50</li>
                        <li>Fresh Juice - RM 3.50</li>
                    </ul>
                </div>
            </div>
        </div>
    `);
}

function openLibraryService() {
    createModal('Library Services', `
        <div class="library-content">
            <h3>Campus Library</h3>
            <div class="library-status">
                <div class="status-item">
                    <span class="status-label">Current Status:</span>
                    <span class="status-value open">Open</span>
                </div>
                <div class="status-item">
                    <span class="status-label">Available Seats:</span>
                    <span class="status-value">45/120</span>
                </div>
            </div>
            <div class="library-services">
                <h4>Available Services</h4>
                <div class="service-list">
                    <div class="service-item">
                        <i class="fas fa-book"></i>
                        <span>Book Borrowing</span>
                    </div>
                    <div class="service-item">
                        <i class="fas fa-laptop"></i>
                        <span>Computer Access</span>
                    </div>
                    <div class="service-item">
                        <i class="fas fa-print"></i>
                        <span>Printing Services</span>
                    </div>
                    <div class="service-item">
                        <i class="fas fa-wifi"></i>
                        <span>Free WiFi</span>
                    </div>
                </div>
            </div>
            <div class="library-hours">
                <h4>Operating Hours</h4>
                <p>Monday - Sunday: 24/7 Study Hall<br>
                Librarian Services: 8:00 AM - 6:00 PM</p>
            </div>
        </div>
    `);
}

function openHealthService() {
    createModal('Health Center', `
        <div class="health-content">
            <h3>Campus Health Center</h3>
            <div class="health-services">
                <h4>Available Services</h4>
                <div class="service-grid">
                    <div class="health-service">
                        <i class="fas fa-stethoscope"></i>
                        <span>General Consultation</span>
                    </div>
                    <div class="health-service">
                        <i class="fas fa-pills"></i>
                        <span>Basic Medication</span>
                    </div>
                    <div class="health-service">
                        <i class="fas fa-band-aid"></i>
                        <span>First Aid</span>
                    </div>
                    <div class="health-service">
                        <i class="fas fa-syringe"></i>
                        <span>Vaccinations</span>
                    </div>
                </div>
            </div>
            <div class="emergency-contact">
                <h4>Emergency Contact</h4>
                <div class="contact-info">
                    <p><strong>Emergency:</strong> 999</p>
                    <p><strong>Health Center:</strong> +603-1234-5678</p>
                    <p><strong>Location:</strong> Block A, Ground Floor</p>
                </div>
            </div>
            <div class="appointment-section">
                <button class="btn-primary" onclick="bookAppointment()">Book Appointment</button>
                <button class="btn-secondary" onclick="emergencyCall()">Emergency Call</button>
            </div>
        </div>
    `);
}

function openShuttleService() {
    createModal('Campus Shuttle', `
        <div class="shuttle-content">
            <h3>Campus Shuttle Service</h3>
            <div class="shuttle-schedule">
                <h4>Next Departures</h4>
                <div class="departure-list">
                    <div class="departure-item">
                        <span class="route">Main Gate → Hostel</span>
                        <span class="time">10:15 AM</span>
                        <span class="status arriving">Arriving</span>
                    </div>
                    <div class="departure-item">
                        <span class="route">Hostel → Main Campus</span>
                        <span class="time">10:30 AM</span>
                        <span class="status scheduled">Scheduled</span>
                    </div>
                    <div class="departure-item">
                        <span class="route">Campus → Shopping Center</span>
                        <span class="time">10:45 AM</span>
                        <span class="status scheduled">Scheduled</span>
                    </div>
                </div>
            </div>
            <div class="shuttle-info">
                <h4>Service Information</h4>
                <p><strong>Operating Hours:</strong> 7:00 AM - 10:00 PM</p>
                <p><strong>Frequency:</strong> Every 15 minutes</p>
                <p><strong>Cost:</strong> Free for students</p>
            </div>
        </div>
    `);
}

function openGymService() {
    createModal('Gymnasium & Sports', `
        <div class="gym-content">
            <h3>Campus Gymnasium</h3>
            <div class="gym-status">
                <div class="capacity-info">
                    <span class="capacity-label">Current Capacity:</span>
                    <span class="capacity-value">15/50</span>
                    <div class="capacity-bar">
                        <div class="capacity-fill" style="width: 30%"></div>
                    </div>
                </div>
            </div>
            <div class="gym-facilities">
                <h4>Available Facilities</h4>
                <div class="facility-grid">
                    <div class="facility-item">
                        <i class="fas fa-dumbbell"></i>
                        <span>Weight Training</span>
                    </div>
                    <div class="facility-item">
                        <i class="fas fa-running"></i>
                        <span>Cardio Equipment</span>
                    </div>
                    <div class="facility-item">
                        <i class="fas fa-basketball-ball"></i>
                        <span>Basketball Court</span>
                    </div>
                    <div class="facility-item">
                        <i class="fas fa-table-tennis"></i>
                        <span>Table Tennis</span>
                    </div>
                </div>
            </div>
            <div class="gym-hours">
                <h4>Operating Hours</h4>
                <p>Monday - Sunday: 5:00 AM - 11:00 PM</p>
            </div>
        </div>
    `);
}

function openParkingService() {
    createModal('Parking Information', `
        <div class="parking-content">
            <h3>Campus Parking</h3>
            <div class="parking-status">
                <div class="parking-lot">
                    <h4>Lot A (Main Building)</h4>
                    <div class="lot-status">
                        <span class="available">Available: 25</span>
                        <span class="total">Total: 100</span>
                    </div>
                </div>
                <div class="parking-lot">
                    <h4>Lot B (Student Center)</h4>
                    <div class="lot-status">
                        <span class="available">Available: 8</span>
                        <span class="total">Total: 50</span>
                    </div>
                </div>
                <div class="parking-lot">
                    <h4>Lot C (Sports Complex)</h4>
                    <div class="lot-status">
                        <span class="available">Available: 30</span>
                        <span class="total">Total: 75</span>
                    </div>
                </div>
            </div>
            <div class="parking-info">
                <h4>Parking Permits</h4>
                <p><strong>Student Monthly:</strong> RM 20.00</p>
                <p><strong>Daily Rate:</strong> RM 2.00</p>
                <p><strong>Visitor Rate:</strong> RM 1.00/hour</p>
            </div>
            <div class="parking-actions">
                <button class="btn-primary" onclick="applyParkingPermit()">Apply for Permit</button>
            </div>
        </div>
    `);
}

// Handle course actions
function handleCourseAction(e) {
    e.preventDefault();
    const actionText = this.textContent;
    const courseName = this.closest('.course-item').querySelector('h4').textContent;
    
    console.log('Course action:', actionText, 'for', courseName);
    
    // Route to specific actions
    switch(actionText.toLowerCase()) {
        case 'syllabus':
            openSyllabus(courseName);
            break;
        case 'assignments':
            openAssignments(courseName);
            break;
        case 'grades':
            openGrades(courseName);
            break;
        default:
            showToast(`Opening ${actionText} for ${courseName}`);
    }
}

// Course-specific functions
function openSyllabus(courseName) {
    // Create modal for syllabus
    createModal('Course Syllabus', `
        <div class="syllabus-content">
            <h3>${courseName}</h3>
            <div class="syllabus-section">
                <h4>Course Description</h4>
                <p>Comprehensive course covering all aspects of ${courseName.toLowerCase()}.</p>
            </div>
            <div class="syllabus-section">
                <h4>Learning Objectives</h4>
                <ul>
                    <li>Understand fundamental concepts</li>
                    <li>Apply practical skills</li>
                    <li>Complete hands-on projects</li>
                    <li>Demonstrate proficiency</li>
                </ul>
            </div>
            <div class="syllabus-section">
                <h4>Assessment Methods</h4>
                <ul>
                    <li>Assignments: 40%</li>
                    <li>Practical Exams: 35%</li>
                    <li>Final Project: 25%</li>
                </ul>
            </div>
        </div>
    `);
}

function openAssignments(courseName) {
    // Create assignments list
    createModal('Course Assignments', `
        <div class="assignments-content">
            <h3>${courseName} - Assignments</h3>
            <div class="assignment-item">
                <div class="assignment-header">
                    <h4>Assignment 1: Practical Application</h4>
                    <span class="due-date">Due: Sept 15, 2025</span>
                </div>
                <p>Complete the practical exercise demonstrating course concepts.</p>
                <div class="assignment-status submitted">Submitted</div>
            </div>
            <div class="assignment-item">
                <div class="assignment-header">
                    <h4>Assignment 2: Project Portfolio</h4>
                    <span class="due-date">Due: Sept 22, 2025</span>
                </div>
                <p>Create a comprehensive portfolio showcasing your work.</p>
                <div class="assignment-status pending">Pending</div>
            </div>
            <div class="assignment-item">
                <div class="assignment-header">
                    <h4>Assignment 3: Final Presentation</h4>
                    <span class="due-date">Due: Sept 30, 2025</span>
                </div>
                <p>Present your final project to the class.</p>
                <div class="assignment-status upcoming">Upcoming</div>
            </div>
        </div>
    `);
}

function openGrades(courseName) {
    // Create grades overview
    createModal('Course Grades', `
        <div class="grades-content">
            <h3>${courseName} - Grade Report</h3>
            <div class="grade-summary">
                <div class="current-grade">
                    <span class="grade-label">Current Grade</span>
                    <span class="grade-value">88%</span>
                    <span class="grade-letter">A-</span>
                </div>
            </div>
            <div class="grade-breakdown">
                <div class="grade-item">
                    <span class="item-name">Assignment 1</span>
                    <span class="item-score">92/100</span>
                    <span class="item-grade">A</span>
                </div>
                <div class="grade-item">
                    <span class="item-name">Practical Exam 1</span>
                    <span class="item-score">85/100</span>
                    <span class="item-grade">B+</span>
                </div>
                <div class="grade-item">
                    <span class="item-name">Assignment 2</span>
                    <span class="item-score">90/100</span>
                    <span class="item-grade">A-</span>
                </div>
                <div class="grade-item pending">
                    <span class="item-name">Final Project</span>
                    <span class="item-score">Pending</span>
                    <span class="item-grade">-</span>
                </div>
            </div>
        </div>
    `);
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

// Create modal function
function createModal(title, content) {
    // Remove existing modal if any
    const existingModal = document.querySelector('.modal-overlay');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Create modal structure
    const modalOverlay = document.createElement('div');
    modalOverlay.className = 'modal-overlay';
    modalOverlay.innerHTML = `
        <div class="modal-container">
            <div class="modal-header">
                <h3>${title}</h3>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-content">
                ${content}
            </div>
        </div>
    `;
    
    // Add to page
    document.body.appendChild(modalOverlay);
    
    // Show modal with animation
    setTimeout(() => modalOverlay.classList.add('show'), 50);
    
    // Close on overlay click
    modalOverlay.addEventListener('click', function(e) {
        if (e.target === modalOverlay) {
            closeModal();
        }
    });
    
    // Close on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });
}

// Close modal function
function closeModal() {
    const modal = document.querySelector('.modal-overlay');
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => modal.remove(), 300);
    }
}

// Helper functions for modal actions
function updateProfile() {
    showToast('Profile update feature coming soon!', 'info');
    closeModal();
}

function downloadTranscript() {
    showToast('Downloading transcript...', 'success');
    closeModal();
}

function makePayment() {
    showToast('Redirecting to payment portal...', 'info');
    closeModal();
}

function applyScholarship() {
    showToast('Opening scholarship application...', 'info');
    closeModal();
}

function bookAppointment() {
    showToast('Opening appointment booking...', 'info');
    closeModal();
}

function emergencyCall() {
    if (confirm('This will call emergency services. Continue?')) {
        showToast('Calling emergency services...', 'warning');
        closeModal();
    }
}

function applyParkingPermit() {
    showToast('Opening parking permit application...', 'info');
    closeModal();
}

function downloadMaterial(materialId) {
    showToast(`Downloading ${materialId}...`, 'success');
}

function viewAssignments(courseId) {
    showToast(`Loading assignments for ${courseId}...`, 'info');
}

function contactService(serviceId) {
    showToast(`Connecting to ${serviceId} service...`, 'info');
}

function switchCourseTab(tabId) {
    // Remove active class from all tabs
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    // Add active class to clicked tab
    event.target.classList.add('active');
    showToast(`Showing ${tabId} courses`, 'info');
}

function filterNews(category) {
    // Remove active class from all filters
    document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
    // Add active class to clicked filter
    event.target.classList.add('active');
    showToast(`Filtering ${category} news`, 'info');
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
