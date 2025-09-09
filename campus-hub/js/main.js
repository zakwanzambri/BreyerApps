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
    
    // Specific button handlers
    const viewAllCalendar = document.getElementById('view-all-calendar');
    if (viewAllCalendar) {
        viewAllCalendar.addEventListener('click', showAcademicCalendar);
    }
    
    const browseAllCourses = document.getElementById('browse-all-courses');
    if (browseAllCourses) {
        browseAllCourses.addEventListener('click', showAllCourses);
    }
    
    const viewMoreServices = document.getElementById('view-more-services');
    if (viewMoreServices) {
        viewMoreServices.addEventListener('click', showAllServices);
    }
    
    const readMoreNews = document.getElementById('read-more-news');
    if (readMoreNews) {
        readMoreNews.addEventListener('click', showAllNews);
    }
    
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
    createModal('Academic Calendar - Full View', `
        <div class="calendar-content">
            <div class="calendar-header">
                <h3>Academic Calendar 2025</h3>
                <div class="calendar-controls">
                    <button class="btn-secondary" onclick="previousMonth()">
                        <i class="fas fa-chevron-left"></i> Previous
                    </button>
                    <span class="current-month">September 2025</span>
                    <button class="btn-secondary" onclick="nextMonth()">
                        Next <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
            
            <div class="calendar-legend">
                <div class="legend-item">
                    <span class="legend-color exam"></span>
                    <span>Examinations</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color assignment"></span>
                    <span>Assignment Due</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color workshop"></span>
                    <span>Workshops</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color holiday"></span>
                    <span>Holidays</span>
                </div>
            </div>
            
            <div class="calendar-events-full">
                <div class="calendar-event exam">
                    <div class="event-date-full">
                        <span class="date-number">15</span>
                        <span class="date-month">Sep</span>
                    </div>
                    <div class="event-details-full">
                        <h4>Culinary Arts Practical Exam</h4>
                        <p><i class="fas fa-map-marker-alt"></i> Kitchen Laboratory A - Block C</p>
                        <p><i class="fas fa-clock"></i> 9:00 AM - 12:00 PM</p>
                        <p><i class="fas fa-user"></i> Chef Rahman Abdullah</p>
                        <div class="event-actions">
                            <button class="btn-primary" onclick="addToPersonalCalendar('culinary-exam')">
                                <i class="fas fa-calendar-plus"></i> Add to Calendar
                            </button>
                            <button class="btn-secondary" onclick="setReminder('culinary-exam')">
                                <i class="fas fa-bell"></i> Set Reminder
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="calendar-event assignment">
                    <div class="event-date-full">
                        <span class="date-number">18</span>
                        <span class="date-month">Sep</span>
                    </div>
                    <div class="event-details-full">
                        <h4>Computer System Project Submission</h4>
                        <p><i class="fas fa-laptop"></i> Database Management Project</p>
                        <p><i class="fas fa-clock"></i> Due by 11:59 PM</p>
                        <p><i class="fas fa-user"></i> En. Ahmad Faizal</p>
                        <div class="event-actions">
                            <button class="btn-primary" onclick="viewProjectDetails('cs-project')">
                                <i class="fas fa-eye"></i> View Details
                            </button>
                            <button class="btn-secondary" onclick="submitProject('cs-project')">
                                <i class="fas fa-upload"></i> Submit Project
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="calendar-event workshop">
                    <div class="event-date-full">
                        <span class="date-number">22</span>
                        <span class="date-month">Sep</span>
                    </div>
                    <div class="event-details-full">
                        <h4>Electrical Wiring Safety Workshop</h4>
                        <p><i class="fas fa-map-marker-alt"></i> Technical Lab - Block B</p>
                        <p><i class="fas fa-clock"></i> 2:00 PM - 5:00 PM</p>
                        <p><i class="fas fa-user"></i> En. Mohd Hafiz</p>
                        <div class="event-actions">
                            <button class="btn-primary" onclick="registerWorkshop('electrical-workshop')">
                                <i class="fas fa-user-plus"></i> Register
                            </button>
                            <button class="btn-secondary" onclick="downloadMaterials('electrical-workshop')">
                                <i class="fas fa-download"></i> Download Materials
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="calendar-event assignment">
                    <div class="event-date-full">
                        <span class="date-number">25</span>
                        <span class="date-month">Sep</span>
                    </div>
                    <div class="event-details-full">
                        <h4>F&B Management Presentation</h4>
                        <p><i class="fas fa-map-marker-alt"></i> Conference Room - Main Block</p>
                        <p><i class="fas fa-clock"></i> 10:00 AM - 12:00 PM</p>
                        <p><i class="fas fa-user"></i> Puan Azura Hassan</p>
                        <div class="event-actions">
                            <button class="btn-primary" onclick="viewPresentationGuide('fbm-presentation')">
                                <i class="fas fa-file-powerpoint"></i> Presentation Guide
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="calendar-event exam">
                    <div class="event-date-full">
                        <span class="date-number">30</span>
                        <span class="date-month">Sep</span>
                    </div>
                    <div class="event-details-full">
                        <h4>Administrative Management Final Exam</h4>
                        <p><i class="fas fa-map-marker-alt"></i> Exam Hall - Block A</p>
                        <p><i class="fas fa-clock"></i> 9:00 AM - 11:00 AM</p>
                        <p><i class="fas fa-user"></i> Puan Siti Nurhaliza</p>
                        <div class="event-actions">
                            <button class="btn-primary" onclick="viewExamInfo('admin-final')">
                                <i class="fas fa-info-circle"></i> Exam Information
                            </button>
                            <button class="btn-secondary" onclick="downloadStudyGuide('admin-final')">
                                <i class="fas fa-book"></i> Study Guide
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="calendar-footer">
                <div class="upcoming-count">
                    <i class="fas fa-calendar-check"></i>
                    <span>5 upcoming events this month</span>
                </div>
                <button class="btn-primary" onclick="exportCalendar()">
                    <i class="fas fa-download"></i> Export Calendar
                </button>
            </div>
        </div>
    `);
}

function showAllCourses() {
    createModal('All Course Materials', `
        <div class="courses-browser">
            <div class="courses-header">
                <h3>Course Materials Library</h3>
                <div class="search-filter-bar">
                    <div class="search-section">
                        <input type="text" id="course-search" placeholder="Search materials..." class="search-input">
                        <button class="btn-primary" onclick="searchCourseMaterials()">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                    <div class="filter-section">
                        <select id="program-filter" class="filter-select">
                            <option value="">All Programs</option>
                            <option value="culinary">Culinary Arts</option>
                            <option value="computer">Computer Systems</option>
                            <option value="electrical">Electrical Wiring</option>
                            <option value="fnb">F&B Management</option>
                            <option value="admin">Administrative Management</option>
                        </select>
                        <select id="type-filter" class="filter-select">
                            <option value="">All Types</option>
                            <option value="lecture">Lecture Notes</option>
                            <option value="practical">Practical Guides</option>
                            <option value="assignment">Assignments</option>
                            <option value="reference">References</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="courses-grid">
                <div class="course-category" data-program="culinary">
                    <div class="category-header">
                        <i class="fas fa-utensils"></i>
                        <h4>Culinary Arts</h4>
                        <span class="material-count">12 materials</span>
                    </div>
                    
                    <div class="course-materials">
                        <div class="material-item" data-type="lecture">
                            <div class="material-icon">
                                <i class="fas fa-file-pdf"></i>
                            </div>
                            <div class="material-info">
                                <h5>Advanced Baking Techniques</h5>
                                <p>Lecture Notes - Week 8</p>
                                <div class="material-meta">
                                    <span><i class="fas fa-calendar"></i> Updated Sep 10</span>
                                    <span><i class="fas fa-download"></i> 45 downloads</span>
                                </div>
                            </div>
                            <div class="material-actions">
                                <button class="btn-primary" onclick="downloadMaterial('baking-techniques')">
                                    <i class="fas fa-download"></i> Download
                                </button>
                                <button class="btn-secondary" onclick="previewMaterial('baking-techniques')">
                                    <i class="fas fa-eye"></i> Preview
                                </button>
                            </div>
                        </div>
                        
                        <div class="material-item" data-type="practical">
                            <div class="material-icon">
                                <i class="fas fa-video"></i>
                            </div>
                            <div class="material-info">
                                <h5>Professional Plating Workshop</h5>
                                <p>Video Tutorial - 45 minutes</p>
                                <div class="material-meta">
                                    <span><i class="fas fa-play"></i> 1.2k views</span>
                                    <span><i class="fas fa-star"></i> 4.8 rating</span>
                                </div>
                            </div>
                            <div class="material-actions">
                                <button class="btn-primary" onclick="watchVideo('plating-workshop')">
                                    <i class="fas fa-play"></i> Watch
                                </button>
                                <button class="btn-secondary" onclick="addToPlaylist('plating-workshop')">
                                    <i class="fas fa-plus"></i> Playlist
                                </button>
                            </div>
                        </div>
                        
                        <div class="material-item" data-type="assignment">
                            <div class="material-icon">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <div class="material-info">
                                <h5>Menu Planning Assignment</h5>
                                <p>Assignment Brief - Due Sep 20</p>
                                <div class="material-meta">
                                    <span><i class="fas fa-clock"></i> 5 days left</span>
                                    <span><i class="fas fa-percentage"></i> 20% weight</span>
                                </div>
                            </div>
                            <div class="material-actions">
                                <button class="btn-primary" onclick="viewAssignment('menu-planning')">
                                    <i class="fas fa-eye"></i> View Brief
                                </button>
                                <button class="btn-secondary" onclick="submitAssignment('menu-planning')">
                                    <i class="fas fa-upload"></i> Submit
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="course-category" data-program="computer">
                    <div class="category-header">
                        <i class="fas fa-laptop-code"></i>
                        <h4>Computer Systems</h4>
                        <span class="material-count">15 materials</span>
                    </div>
                    
                    <div class="course-materials">
                        <div class="material-item" data-type="lecture">
                            <div class="material-icon">
                                <i class="fas fa-file-code"></i>
                            </div>
                            <div class="material-info">
                                <h5>Database Design Principles</h5>
                                <p>Interactive Tutorial + Code Examples</p>
                                <div class="material-meta">
                                    <span><i class="fas fa-code"></i> SQL + Examples</span>
                                    <span><i class="fas fa-clock"></i> 2 hours</span>
                                </div>
                            </div>
                            <div class="material-actions">
                                <button class="btn-primary" onclick="openTutorial('database-design')">
                                    <i class="fas fa-play"></i> Start Tutorial
                                </button>
                                <button class="btn-secondary" onclick="downloadCode('database-design')">
                                    <i class="fas fa-code"></i> Get Code
                                </button>
                            </div>
                        </div>
                        
                        <div class="material-item" data-type="practical">
                            <div class="material-icon">
                                <i class="fas fa-terminal"></i>
                            </div>
                            <div class="material-info">
                                <h5>Network Configuration Lab</h5>
                                <p>Hands-on Lab Guide - Cisco Packet Tracer</p>
                                <div class="material-meta">
                                    <span><i class="fas fa-network-wired"></i> Network Lab</span>
                                    <span><i class="fas fa-users"></i> Group work</span>
                                </div>
                            </div>
                            <div class="material-actions">
                                <button class="btn-primary" onclick="downloadLab('network-config')">
                                    <i class="fas fa-download"></i> Lab Files
                                </button>
                                <button class="btn-secondary" onclick="bookLabTime('network-config')">
                                    <i class="fas fa-calendar"></i> Book Lab
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="course-category" data-program="electrical">
                    <div class="category-header">
                        <i class="fas fa-bolt"></i>
                        <h4>Electrical Wiring</h4>
                        <span class="material-count">10 materials</span>
                    </div>
                    
                    <div class="course-materials">
                        <div class="material-item" data-type="reference">
                            <div class="material-icon">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="material-info">
                                <h5>MS IEC Wiring Standards</h5>
                                <p>Malaysian Standards Reference Guide</p>
                                <div class="material-meta">
                                    <span><i class="fas fa-shield-alt"></i> Safety Standards</span>
                                    <span><i class="fas fa-flag"></i> MS Standards</span>
                                </div>
                            </div>
                            <div class="material-actions">
                                <button class="btn-primary" onclick="viewStandards('ms-iec-wiring')">
                                    <i class="fas fa-book-open"></i> View Guide
                                </button>
                                <button class="btn-secondary" onclick="downloadPDF('ms-iec-wiring')">
                                    <i class="fas fa-file-pdf"></i> Download PDF
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="courses-footer">
                <div class="material-stats">
                    <div class="stat-item">
                        <i class="fas fa-file"></i>
                        <span>37 Total Materials</span>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-download"></i>
                        <span>1,250 Downloads This Month</span>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-clock"></i>
                        <span>Last Updated: Today</span>
                    </div>
                </div>
                <button class="btn-primary" onclick="requestMaterial()">
                    <i class="fas fa-plus"></i> Request New Material
                </button>
            </div>
        </div>
    `);
}

function showAllServices() {
    createModal('Campus Services Directory', `
        <div class="services-directory">
            <div class="services-header">
                <h3>Campus Services Directory</h3>
                <p>Your complete guide to all campus services and support</p>
                <div class="services-search">
                    <input type="text" id="services-search" placeholder="Search services..." class="search-input">
                    <button class="btn-primary" onclick="searchServices()">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </div>
            
            <div class="services-grid">
                <div class="service-category">
                    <div class="category-header-full">
                        <i class="fas fa-graduation-cap"></i>
                        <h4>Academic Services</h4>
                        <span class="service-count">8 services</span>
                    </div>
                    <div class="service-items-detailed">
                        <div class="service-item-full" onclick="showServiceDetail('registrar')">
                            <div class="service-icon-large">
                                <i class="fas fa-university"></i>
                            </div>
                            <div class="service-content">
                                <h5>Registrar Office</h5>
                                <p>Student registration, academic records, transcripts, and enrollment services</p>
                                <div class="service-info">
                                    <span><i class="fas fa-map-marker-alt"></i> Administration Building, Level 2</span>
                                    <span><i class="fas fa-clock"></i> Mon-Fri: 8:00 AM - 5:00 PM</span>
                                    <span><i class="fas fa-phone"></i> +6012-345-6789</span>
                                </div>
                                <div class="service-actions">
                                    <button class="btn-primary" onclick="contactService('registrar')">
                                        <i class="fas fa-phone"></i> Contact Now
                                    </button>
                                    <button class="btn-secondary" onclick="bookAppointment('registrar')">
                                        <i class="fas fa-calendar"></i> Book Appointment
                                    </button>
                                    <button class="btn-secondary" onclick="viewForms('registrar')">
                                        <i class="fas fa-file-alt"></i> Forms & Documents
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="service-item-full" onclick="showServiceDetail('library')">
                            <div class="service-icon-large">
                                <i class="fas fa-book-open"></i>
                            </div>
                            <div class="service-content">
                                <h5>Library & Learning Resources</h5>
                                <p>Books, digital resources, study spaces, research assistance, and computer access</p>
                                <div class="service-info">
                                    <span><i class="fas fa-map-marker-alt"></i> Learning Center Building</span>
                                    <span><i class="fas fa-clock"></i> Mon-Thu: 7:00 AM - 10:00 PM, Fri: 7:00 AM - 6:00 PM</span>
                                    <span><i class="fas fa-users"></i> 150 study seats available</span>
                                </div>
                                <div class="service-actions">
                                    <button class="btn-primary" onclick="searchCatalog()">
                                        <i class="fas fa-search"></i> Search Catalog
                                    </button>
                                    <button class="btn-secondary" onclick="reserveStudyRoom()">
                                        <i class="fas fa-door-open"></i> Reserve Study Room
                                    </button>
                                    <button class="btn-secondary" onclick="renewBooks()">
                                        <i class="fas fa-sync"></i> Renew Books
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="service-item-full" onclick="showServiceDetail('academic-support')">
                            <div class="service-icon-large">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <div class="service-content">
                                <h5>Academic Support Center</h5>
                                <p>Tutoring services, study groups, academic counseling, and learning support</p>
                                <div class="service-info">
                                    <span><i class="fas fa-map-marker-alt"></i> Student Services Building, Level 1</span>
                                    <span><i class="fas fa-clock"></i> Mon-Fri: 9:00 AM - 6:00 PM</span>
                                    <span><i class="fas fa-user-graduate"></i> Free tutoring available</span>
                                </div>
                                <div class="service-actions">
                                    <button class="btn-primary" onclick="requestTutor()">
                                        <i class="fas fa-user-plus"></i> Request Tutor
                                    </button>
                                    <button class="btn-secondary" onclick="joinStudyGroup()">
                                        <i class="fas fa-users"></i> Join Study Group
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="service-category">
                    <div class="category-header-full">
                        <i class="fas fa-user-friends"></i>
                        <h4>Student Life & Support</h4>
                        <span class="service-count">6 services</span>
                    </div>
                    <div class="service-items-detailed">
                        <div class="service-item-full" onclick="showServiceDetail('counseling')">
                            <div class="service-icon-large">
                                <i class="fas fa-heart"></i>
                            </div>
                            <div class="service-content">
                                <h5>Student Counseling Services</h5>
                                <p>Personal counseling, academic guidance, career advice, and mental health support</p>
                                <div class="service-info">
                                    <span><i class="fas fa-map-marker-alt"></i> Student Wellness Center</span>
                                    <span><i class="fas fa-clock"></i> Mon-Fri: 8:00 AM - 5:00 PM</span>
                                    <span><i class="fas fa-lock"></i> Confidential services</span>
                                </div>
                                <div class="service-actions">
                                    <button class="btn-primary" onclick="bookCounseling()">
                                        <i class="fas fa-calendar-plus"></i> Book Session
                                    </button>
                                    <button class="btn-secondary" onclick="anonymousChat()">
                                        <i class="fas fa-comments"></i> Anonymous Chat
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="service-item-full" onclick="showServiceDetail('financial-aid')">
                            <div class="service-icon-large">
                                <i class="fas fa-hand-holding-usd"></i>
                            </div>
                            <div class="service-content">
                                <h5>Financial Aid & Scholarships</h5>
                                <p>Scholarship applications, financial assistance, payment plans, and funding advice</p>
                                <div class="service-info">
                                    <span><i class="fas fa-map-marker-alt"></i> Finance Office, Level 1</span>
                                    <span><i class="fas fa-clock"></i> Mon-Fri: 8:30 AM - 4:30 PM</span>
                                    <span><i class="fas fa-award"></i> Multiple scholarship programs</span>
                                </div>
                                <div class="service-actions">
                                    <button class="btn-primary" onclick="applyScholarship()">
                                        <i class="fas fa-file-signature"></i> Apply Scholarship
                                    </button>
                                    <button class="btn-secondary" onclick="paymentPlan()">
                                        <i class="fas fa-credit-card"></i> Payment Plans
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="service-category">
                    <div class="category-header-full">
                        <i class="fas fa-shield-alt"></i>
                        <h4>Health & Safety</h4>
                        <span class="service-count">4 services</span>
                    </div>
                    <div class="service-items-detailed">
                        <div class="service-item-full" onclick="showServiceDetail('health-center')">
                            <div class="service-icon-large">
                                <i class="fas fa-heartbeat"></i>
                            </div>
                            <div class="service-content">
                                <h5>Campus Health Center</h5>
                                <p>Medical services, health checkups, vaccinations, and emergency care</p>
                                <div class="service-info">
                                    <span><i class="fas fa-map-marker-alt"></i> Health Center Building</span>
                                    <span><i class="fas fa-clock"></i> Mon-Fri: 8:00 AM - 5:00 PM</span>
                                    <span><i class="fas fa-phone"></i> Emergency: +6012-911-1234</span>
                                </div>
                                <div class="service-actions">
                                    <button class="btn-primary" onclick="bookHealthAppointment()">
                                        <i class="fas fa-stethoscope"></i> Book Appointment
                                    </button>
                                    <button class="btn-secondary" onclick="emergencyContacts()">
                                        <i class="fas fa-exclamation-triangle"></i> Emergency Contacts
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="service-item-full" onclick="showServiceDetail('security')">
                            <div class="service-icon-large">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="service-content">
                                <h5>Campus Security</h5>
                                <p>24/7 security services, lost & found, incident reporting, and safety escorts</p>
                                <div class="service-info">
                                    <span><i class="fas fa-map-marker-alt"></i> Security Office - Main Gate</span>
                                    <span><i class="fas fa-clock"></i> 24/7 Service</span>
                                    <span><i class="fas fa-phone"></i> Emergency: +6012-999-8888</span>
                                </div>
                                <div class="service-actions">
                                    <button class="btn-primary" onclick="reportIncident()">
                                        <i class="fas fa-exclamation-circle"></i> Report Incident
                                    </button>
                                    <button class="btn-secondary" onclick="requestEscort()">
                                        <i class="fas fa-walking"></i> Request Escort
                                    </button>
                                    <button class="btn-secondary" onclick="lostAndFound()">
                                        <i class="fas fa-search"></i> Lost & Found
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="services-footer">
                <div class="quick-contacts">
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <strong>Main Office</strong>
                            <span>+603-1234-5678</span>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <strong>General Email</strong>
                            <span>info@kolej.edu.my</span>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-clock"></i>
                        <div>
                            <strong>Office Hours</strong>
                            <span>Mon-Fri: 8:00 AM - 5:00 PM</span>
                        </div>
                    </div>
                </div>
                <button class="btn-primary" onclick="downloadServiceDirectory()">
                    <i class="fas fa-download"></i> Download Directory (PDF)
                </button>
            </div>
        </div>
    `);
}

function showAllNews() {
    createModal('Campus News & Announcements', `
        <div class="news-hub">
            <div class="news-header">
                <h3>Campus News & Announcements</h3>
                <p>Stay updated with the latest campus news, events, and important announcements</p>
                <div class="news-controls">
                    <div class="news-search">
                        <input type="text" id="news-search" placeholder="Search news..." class="search-input">
                        <button class="btn-primary" onclick="searchNews()">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                    <div class="news-filters">
                        <button class="filter-btn active" onclick="filterNews('all')">All</button>
                        <button class="filter-btn" onclick="filterNews('academic')">Academic</button>
                        <button class="filter-btn" onclick="filterNews('events')">Events</button>
                        <button class="filter-btn" onclick="filterNews('campus')">Campus Life</button>
                        <button class="filter-btn" onclick="filterNews('urgent')">Urgent</button>
                    </div>
                </div>
            </div>
            
            <div class="featured-news">
                <div class="featured-article">
                    <div class="featured-image">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="featured-content">
                        <div class="article-meta">
                            <span class="category featured">Featured</span>
                            <span class="date">September 12, 2025</span>
                            <span class="author">By: Administration</span>
                        </div>
                        <h4>Kolej Excellence Awards 2025 - Students Recognition Ceremony</h4>
                        <p>Join us for the annual Excellence Awards ceremony recognizing outstanding academic achievements, leadership excellence, and community service contributions by our students across all diploma programs...</p>
                        <div class="article-actions">
                            <button class="btn-primary" onclick="readFullArticle('excellence-awards-2025')">
                                <i class="fas fa-book-open"></i> Read Full Article
                            </button>
                            <button class="btn-secondary" onclick="shareArticle('excellence-awards-2025')">
                                <i class="fas fa-share"></i> Share
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="news-grid">
                <article class="news-article-full" data-category="academic">
                    <div class="article-image">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="article-content">
                        <div class="article-meta">
                            <span class="category academic">Academic</span>
                            <span class="date">September 10, 2025</span>
                            <span class="read-time"><i class="fas fa-clock"></i> 3 min read</span>
                        </div>
                        <h5>New Semester Registration Opens - Important Deadlines</h5>
                        <p>Important updates regarding the registration process for the upcoming semester. All students must complete course selection and payment by September 20th. Late registration penalties will apply after the deadline...</p>
                        <div class="article-tags">
                            <span class="tag">Registration</span>
                            <span class="tag">Deadlines</span>
                            <span class="tag">Important</span>
                        </div>
                        <div class="article-actions">
                            <button class="btn-primary" onclick="readArticle('registration-deadline')">
                                <i class="fas fa-eye"></i> Read More
                            </button>
                            <button class="btn-secondary" onclick="addToReminders('registration-deadline')">
                                <i class="fas fa-bell"></i> Set Reminder
                            </button>
                        </div>
                    </div>
                </article>
                
                <article class="news-article-full" data-category="events">
                    <div class="article-image">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <div class="article-content">
                        <div class="article-meta">
                            <span class="category events">Events</span>
                            <span class="date">September 8, 2025</span>
                            <span class="read-time"><i class="fas fa-clock"></i> 5 min read</span>
                        </div>
                        <h5>Industry Partnership Career Fair 2025</h5>
                        <p>Join us for the biggest career fair of the year featuring 50+ companies from hospitality, technology, manufacturing, and business sectors. Network with industry professionals and explore exciting career opportunities...</p>
                        <div class="article-tags">
                            <span class="tag">Career Fair</span>
                            <span class="tag">Industry Partners</span>
                            <span class="tag">Job Opportunities</span>
                        </div>
                        <div class="article-actions">
                            <button class="btn-primary" onclick="readArticle('career-fair-2025')">
                                <i class="fas fa-eye"></i> Read More
                            </button>
                            <button class="btn-secondary" onclick="registerEvent('career-fair-2025')">
                                <i class="fas fa-user-plus"></i> Register Now
                            </button>
                        </div>
                    </div>
                </article>
                
                <article class="news-article-full" data-category="campus">
                    <div class="article-image">
                        <i class="fas fa-tools"></i>
                    </div>
                    <div class="article-content">
                        <div class="article-meta">
                            <span class="category campus">Campus Life</span>
                            <span class="date">September 7, 2025</span>
                            <span class="read-time"><i class="fas fa-clock"></i> 2 min read</span>
                        </div>
                        <h5>New Workshop Equipment Installation Complete</h5>
                        <p>Exciting updates! Our technical workshops have been upgraded with state-of-the-art equipment for Electrical Wiring and Computer Systems programs. The new facilities are now ready for hands-on training...</p>
                        <div class="article-tags">
                            <span class="tag">Facilities</span>
                            <span class="tag">Equipment Upgrade</span>
                            <span class="tag">Technical Labs</span>
                        </div>
                        <div class="article-actions">
                            <button class="btn-primary" onclick="readArticle('workshop-upgrade')">
                                <i class="fas fa-eye"></i> Read More
                            </button>
                            <button class="btn-secondary" onclick="tourFacilities('technical-labs')">
                                <i class="fas fa-map-marked"></i> Schedule Tour
                            </button>
                        </div>
                    </div>
                </article>
                
                <article class="news-article-full" data-category="urgent">
                    <div class="article-image urgent-indicator">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="article-content">
                        <div class="article-meta">
                            <span class="category urgent">Urgent Notice</span>
                            <span class="date">September 12, 2025</span>
                            <span class="priority">High Priority</span>
                        </div>
                        <h5>Temporary Library Closure - Sept 15-16</h5>
                        <p>URGENT: The main library will be temporarily closed on September 15-16 for system maintenance. Alternative study spaces will be available in the Student Center. Online resources remain accessible 24/7...</p>
                        <div class="article-tags">
                            <span class="tag">Library Closure</span>
                            <span class="tag">Maintenance</span>
                            <span class="tag">Alternative Spaces</span>
                        </div>
                        <div class="article-actions">
                            <button class="btn-primary" onclick="readArticle('library-closure')">
                                <i class="fas fa-eye"></i> Read More
                            </button>
                            <button class="btn-secondary" onclick="findAlternativeSpaces()">
                                <i class="fas fa-search"></i> Find Study Spaces
                            </button>
                        </div>
                    </div>
                </article>
                
                <article class="news-article-full" data-category="academic">
                    <div class="article-image">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <div class="article-content">
                        <div class="article-meta">
                            <span class="category academic">Academic</span>
                            <span class="date">September 5, 2025</span>
                            <span class="read-time"><i class="fas fa-clock"></i> 4 min read</span>
                        </div>
                        <h5>Culinary Arts Program Receives Industry Recognition</h5>
                        <p>Proud to announce that our Diploma in Culinary Arts program has received the "Excellence in Culinary Education" award from the Malaysian Culinary Association. This recognition highlights our commitment to quality education...</p>
                        <div class="article-tags">
                            <span class="tag">Culinary Arts</span>
                            <span class="tag">Industry Recognition</span>
                            <span class="tag">Program Excellence</span>
                        </div>
                        <div class="article-actions">
                            <button class="btn-primary" onclick="readArticle('culinary-recognition')">
                                <i class="fas fa-eye"></i> Read More
                            </button>
                            <button class="btn-secondary" onclick="learnMoreProgram('culinary-arts')">
                                <i class="fas fa-info-circle"></i> Program Info
                            </button>
                        </div>
                    </div>
                </article>
                
                <article class="news-article-full" data-category="events">
                    <div class="article-image">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="article-content">
                        <div class="article-meta">
                            <span class="category events">Events</span>
                            <span class="date">September 3, 2025</span>
                            <span class="read-time"><i class="fas fa-clock"></i> 3 min read</span>
                        </div>
                        <h5>Student Leadership Workshop Series 2025</h5>
                        <p>Enhance your leadership skills with our comprehensive workshop series covering team management, communication, and project leadership. Open to all students with limited seats available...</p>
                        <div class="article-tags">
                            <span class="tag">Leadership</span>
                            <span class="tag">Professional Development</span>
                            <span class="tag">Skill Building</span>
                        </div>
                        <div class="article-actions">
                            <button class="btn-primary" onclick="readArticle('leadership-workshop')">
                                <i class="fas fa-eye"></i> Read More
                            </button>
                            <button class="btn-secondary" onclick="registerWorkshop('leadership-series')">
                                <i class="fas fa-user-plus"></i> Register
                            </button>
                        </div>
                    </div>
                </article>
            </div>
            
            <div class="news-footer">
                <div class="news-stats">
                    <div class="stat-item">
                        <i class="fas fa-newspaper"></i>
                        <span>15 Articles This Month</span>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-eye"></i>
                        <span>2,340 Total Views</span>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-rss"></i>
                        <span>Subscribe to Updates</span>
                    </div>
                </div>
                <div class="news-actions">
                    <button class="btn-primary" onclick="subscribeNews()">
                        <i class="fas fa-bell"></i> Subscribe to News
                    </button>
                    <button class="btn-secondary" onclick="newsArchive()">
                        <i class="fas fa-archive"></i> View Archive
                    </button>
                    <button class="btn-secondary" onclick="submitNews()">
                        <i class="fas fa-plus"></i> Submit News
                    </button>
                </div>
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

// Enhanced Modal Helper Functions
function addToPersonalCalendar(eventId) {
    showToast(`Adding ${eventId} to your personal calendar...`, 'success');
    // Implementation for calendar integration
}

function setReminder(eventId) {
    showToast(`Reminder set for ${eventId}`, 'success');
    // Implementation for reminder system
}

function viewProjectDetails(projectId) {
    showToast(`Opening project details for ${projectId}...`, 'info');
    // Implementation for project detail view
}

function submitProject(projectId) {
    showToast(`Opening submission portal for ${projectId}...`, 'info');
    // Implementation for project submission
}

function registerWorkshop(workshopId) {
    showToast(`Registering for workshop: ${workshopId}...`, 'success');
    // Implementation for workshop registration
}

function downloadMaterials(materialId) {
    showToast(`Downloading materials for ${materialId}...`, 'info');
    // Implementation for material download
}

function viewPresentationGuide(presentationId) {
    showToast(`Opening presentation guide for ${presentationId}...`, 'info');
    // Implementation for presentation guide
}

function viewExamInfo(examId) {
    showToast(`Opening exam information for ${examId}...`, 'info');
    // Implementation for exam information
}

function downloadStudyGuide(examId) {
    showToast(`Downloading study guide for ${examId}...`, 'info');
    // Implementation for study guide download
}

function exportCalendar() {
    showToast('Exporting calendar...', 'info');
    // Implementation for calendar export
}

function previousMonth() {
    showToast('Loading previous month...', 'info');
    // Implementation for calendar navigation
}

function nextMonth() {
    showToast('Loading next month...', 'info');
    // Implementation for calendar navigation
}

// Course Material Functions
function searchCourseMaterials() {
    const query = document.getElementById('course-search')?.value || '';
    showToast(`Searching for: ${query}`, 'info');
    // Implementation for course material search
}

function downloadMaterial(materialId) {
    showToast(`Downloading material: ${materialId}...`, 'info');
    // Implementation for material download
}

function previewMaterial(materialId) {
    showToast(`Opening preview for: ${materialId}...`, 'info');
    // Implementation for material preview
}

function watchVideo(videoId) {
    showToast(`Opening video: ${videoId}...`, 'info');
    // Implementation for video player
}

function addToPlaylist(videoId) {
    showToast(`Added ${videoId} to playlist`, 'success');
    // Implementation for playlist management
}

function viewAssignment(assignmentId) {
    showToast(`Opening assignment: ${assignmentId}...`, 'info');
    // Implementation for assignment view
}

function submitAssignment(assignmentId) {
    showToast(`Opening submission for: ${assignmentId}...`, 'info');
    // Implementation for assignment submission
}

function openTutorial(tutorialId) {
    showToast(`Starting tutorial: ${tutorialId}...`, 'info');
    // Implementation for interactive tutorials
}

function downloadCode(codeId) {
    showToast(`Downloading code: ${codeId}...`, 'info');
    // Implementation for code download
}

function downloadLab(labId) {
    showToast(`Downloading lab files: ${labId}...`, 'info');
    // Implementation for lab file download
}

function bookLabTime(labId) {
    showToast(`Opening lab booking for: ${labId}...`, 'info');
    // Implementation for lab time booking
}

function viewStandards(standardsId) {
    showToast(`Opening standards guide: ${standardsId}...`, 'info');
    // Implementation for standards guide
}

function downloadPDF(pdfId) {
    showToast(`Downloading PDF: ${pdfId}...`, 'info');
    // Implementation for PDF download
}

function requestMaterial() {
    showToast('Opening material request form...', 'info');
    // Implementation for material request
}

// Service Functions
function searchServices() {
    const query = document.getElementById('services-search')?.value || '';
    showToast(`Searching services for: ${query}`, 'info');
    // Implementation for service search
}

function showServiceDetail(serviceId) {
    showToast(`Opening service details: ${serviceId}...`, 'info');
    // Implementation for detailed service view
}

function contactService(serviceId) {
    showToast(`Contacting service: ${serviceId}...`, 'info');
    // Implementation for service contact
}

function bookAppointment(serviceId) {
    showToast(`Booking appointment with: ${serviceId}...`, 'info');
    // Implementation for appointment booking
}

function viewForms(serviceId) {
    showToast(`Opening forms for: ${serviceId}...`, 'info');
    // Implementation for forms view
}

function searchCatalog() {
    showToast('Opening library catalog...', 'info');
    // Implementation for library catalog search
}

function reserveStudyRoom() {
    showToast('Opening study room reservation...', 'info');
    // Implementation for study room reservation
}

function renewBooks() {
    showToast('Opening book renewal...', 'info');
    // Implementation for book renewal
}

function requestTutor() {
    showToast('Opening tutor request form...', 'info');
    // Implementation for tutor request
}

function joinStudyGroup() {
    showToast('Opening study group registration...', 'info');
    // Implementation for study group joining
}

function bookCounseling() {
    showToast('Opening counseling appointment booking...', 'info');
    // Implementation for counseling booking
}

function anonymousChat() {
    showToast('Opening anonymous chat support...', 'info');
    // Implementation for anonymous chat
}

function applyScholarship() {
    showToast('Opening scholarship application...', 'info');
    // Implementation for scholarship application
}

function paymentPlan() {
    showToast('Opening payment plan options...', 'info');
    // Implementation for payment plans
}

function bookHealthAppointment() {
    showToast('Opening health center appointment booking...', 'info');
    // Implementation for health appointment
}

function emergencyContacts() {
    showToast('Displaying emergency contact information...', 'info');
    // Implementation for emergency contacts
}

function reportIncident() {
    showToast('Opening incident report form...', 'info');
    // Implementation for incident reporting
}

function requestEscort() {
    showToast('Requesting safety escort service...', 'info');
    // Implementation for escort request
}

function lostAndFound() {
    showToast('Opening lost and found portal...', 'info');
    // Implementation for lost and found
}

function downloadServiceDirectory() {
    showToast('Downloading service directory PDF...', 'info');
    // Implementation for directory download
}

// News Functions
function searchNews() {
    const query = document.getElementById('news-search')?.value || '';
    showToast(`Searching news for: ${query}`, 'info');
    // Implementation for news search
}

function filterNews(category) {
    showToast(`Filtering news by: ${category}`, 'info');
    // Update active filter button
    document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    // Implementation for news filtering
}

function readFullArticle(articleId) {
    showToast(`Opening full article: ${articleId}...`, 'info');
    // Implementation for full article view
}

function shareArticle(articleId) {
    showToast(`Sharing article: ${articleId}...`, 'info');
    // Implementation for article sharing
}

function readArticle(articleId) {
    showToast(`Reading article: ${articleId}...`, 'info');
    // Implementation for article reading
}

function addToReminders(articleId) {
    showToast(`Added reminder for: ${articleId}`, 'success');
    // Implementation for reminder system
}

function registerEvent(eventId) {
    showToast(`Registering for event: ${eventId}...`, 'info');
    // Implementation for event registration
}

function tourFacilities(facilityId) {
    showToast(`Scheduling facility tour: ${facilityId}...`, 'info');
    // Implementation for facility tours
}

function findAlternativeSpaces() {
    showToast('Finding alternative study spaces...', 'info');
    // Implementation for space finding
}

function learnMoreProgram(programId) {
    showToast(`Opening program information: ${programId}...`, 'info');
    // Implementation for program details
}

function subscribeNews() {
    showToast('Opening news subscription...', 'info');
    // Implementation for news subscription
}

function newsArchive() {
    showToast('Opening news archive...', 'info');
    // Implementation for news archive
}

function submitNews() {
    showToast('Opening news submission form...', 'info');
    // Implementation for news submission
}
