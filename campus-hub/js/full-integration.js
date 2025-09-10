/**
 * Campus Hub - Full Integration Manager
 * Real-time data synchronization and personalized content
 */

class FullIntegrationManager {
    constructor() {
        this.apiBase = 'api/full-integration.php';
        this.updateInterval = null;
        this.notificationInterval = null;
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.startRealTimeUpdates();
        this.loadInitialData();
    }
    
    // ====== REAL-TIME DATA LOADING ======
    
    async loadInitialData() {
        try {
            await this.loadUserDashboard();
            await this.loadUserCourses();
            await this.loadUpcomingDeadlines();
            await this.loadNotifications();
            await this.loadAcademicCalendar();
        } catch (error) {
            console.error('Failed to load initial data:', error);
            this.showError('Failed to load dashboard data');
        }
    }
    
    async loadUserDashboard() {
        const userId = this.getCurrentUserId();
        if (!userId) return;
        
        try {
            const response = await fetch(`${this.apiBase}?action=user-dashboard&user_id=${userId}`);
            const data = await response.json();
            
            if (data.success) {
                this.updateDashboardStats(data.data.stats);
                this.updateUserProfile(data.data.user);
            }
        } catch (error) {
            console.error('Error loading dashboard:', error);
        }
    }
    
    async loadUserCourses() {
        const userId = this.getCurrentUserId();
        if (!userId) return;
        
        try {
            const response = await fetch(`${this.apiBase}?action=user-courses&user_id=${userId}`);
            const data = await response.json();
            
            if (data.success) {
                this.renderCourses(data.data);
            }
        } catch (error) {
            console.error('Error loading courses:', error);
        }
    }
    
    async loadUpcomingDeadlines() {
        const userId = this.getCurrentUserId();
        if (!userId) return;
        
        try {
            const response = await fetch(`${this.apiBase}?action=upcoming-deadlines&user_id=${userId}`);
            const data = await response.json();
            
            if (data.success) {
                this.renderUpcomingDeadlines(data.data);
            }
        } catch (error) {
            console.error('Error loading deadlines:', error);
        }
    }
    
    async loadNotifications() {
        const userId = this.getCurrentUserId();
        if (!userId) return;
        
        try {
            const response = await fetch(`${this.apiBase}?action=notifications&user_id=${userId}&limit=5`);
            const data = await response.json();
            
            if (data.success) {
                this.renderNotifications(data.data);
                this.updateNotificationBadge(data.data);
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
        }
    }
    
    async loadAcademicCalendar() {
        const userId = this.getCurrentUserId();
        if (!userId) return;
        
        try {
            const response = await fetch(`${this.apiBase}?action=academic-calendar&user_id=${userId}`);
            const data = await response.json();
            
            if (data.success) {
                this.renderAcademicEvents(data.data);
            }
        } catch (error) {
            console.error('Error loading calendar:', error);
        }
    }
    
    async loadPersonalizedNews() {
        const userId = this.getCurrentUserId();
        if (!userId) return;
        
        try {
            const response = await fetch(`${this.apiBase}?action=personalized-news&user_id=${userId}`);
            const data = await response.json();
            
            if (data.success) {
                this.renderPersonalizedNews(data.data);
            }
        } catch (error) {
            console.error('Error loading news:', error);
        }
    }
    
    // ====== UI RENDERING METHODS ======
    
    updateDashboardStats(stats) {
        // Update dashboard statistics
        const statsElements = {
            'total-courses': stats.total_courses,
            'pending-assignments': stats.pending_assignments,
            'unread-notifications': stats.unread_notifications,
            'current-gpa': stats.current_gpa
        };
        
        Object.entries(statsElements).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = value;
                this.animateCountUp(element, value);
            }
        });
    }
    
    updateUserProfile(user) {
        // Update user profile information
        const profileElements = {
            'user-name': user.full_name,
            'user-email': user.email,
            'student-id': user.student_id,
            'program-name': user.program_name
        };
        
        Object.entries(profileElements).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element && value) {
                element.textContent = value;
            }
        });
    }
    
    renderCourses(courses) {
        const container = document.getElementById('courses-container');
        if (!container) return;
        
        container.innerHTML = courses.map(course => `
            <div class="course-card" data-course-id="${course.id}">
                <div class="course-header">
                    <h4>${course.course_code}</h4>
                    <span class="course-credits">${course.credits} Credits</span>
                </div>
                <div class="course-content">
                    <h5>${course.course_name}</h5>
                    <p class="course-lecturer">👨‍🏫 ${course.lecturer_name || 'TBA'}</p>
                    <p class="course-description">${course.description || ''}</p>
                </div>
                <div class="course-footer">
                    <span class="course-semester">Semester ${course.semester}</span>
                    ${course.grade ? `<span class="course-grade grade-${course.grade}">${course.grade}</span>` : '<span class="course-status">In Progress</span>'}
                </div>
            </div>
        `).join('');
    }
    
    renderUpcomingDeadlines(deadlines) {
        const container = document.getElementById('deadlines-container');
        if (!container) return;
        
        if (deadlines.length === 0) {
            container.innerHTML = '<div class="no-deadlines">🎉 No upcoming deadlines!</div>';
            return;
        }
        
        container.innerHTML = deadlines.map(deadline => {
            const urgencyClass = deadline.days_remaining <= 2 ? 'urgent' : 
                               deadline.days_remaining <= 7 ? 'warning' : 'normal';
            
            return `
                <div class="deadline-item ${urgencyClass}">
                    <div class="deadline-info">
                        <h4>${deadline.title}</h4>
                        <p class="deadline-course">${deadline.course_code} - ${deadline.course_name}</p>
                    </div>
                    <div class="deadline-time">
                        <span class="days-remaining">${deadline.days_remaining} days</span>
                        <span class="due-date">${this.formatDate(deadline.due_date)}</span>
                    </div>
                </div>
            `;
        }).join('');
    }
    
    renderNotifications(notifications) {
        const container = document.getElementById('notifications-container');
        if (!container) return;
        
        container.innerHTML = notifications.map(notification => `
            <div class="notification-item ${notification.is_read ? 'read' : 'unread'}" 
                 data-notification-id="${notification.id}">
                <div class="notification-icon">
                    ${this.getNotificationIcon(notification.type)}
                </div>
                <div class="notification-content">
                    <h4>${notification.title}</h4>
                    <p>${notification.message}</p>
                    <span class="notification-time">${this.formatTimeAgo(notification.created_at)}</span>
                </div>
                ${!notification.is_read ? '<div class="unread-indicator"></div>' : ''}
            </div>
        `).join('');
        
        // Add click handlers for notifications
        container.querySelectorAll('.notification-item.unread').forEach(item => {
            item.addEventListener('click', () => this.markNotificationRead(item.dataset.notificationId));
        });
    }
    
    renderAcademicEvents(events) {
        const container = document.getElementById('academic-events-container');
        if (!container) return;
        
        // Group events by date
        const eventsByDate = this.groupEventsByDate(events);
        
        container.innerHTML = Object.entries(eventsByDate).map(([date, dayEvents]) => `
            <div class="calendar-day">
                <div class="calendar-date">
                    <span class="day">${this.formatCalendarDay(date)}</span>
                    <span class="month">${this.formatCalendarMonth(date)}</span>
                </div>
                <div class="calendar-events">
                    ${dayEvents.map(event => `
                        <div class="calendar-event ${event.event_type}">
                            <h4>${event.title}</h4>
                            <p>${event.description || ''}</p>
                            ${event.event_time ? `<span class="event-time">${event.event_time}</span>` : ''}
                        </div>
                    `).join('')}
                </div>
            </div>
        `).join('');
    }
    
    renderPersonalizedNews(news) {
        const container = document.getElementById('personalized-news-container');
        if (!container) return;
        
        container.innerHTML = news.map(article => `
            <article class="news-article">
                <div class="news-header">
                    <span class="news-category">${article.category}</span>
                    <span class="news-date">${this.formatTimeAgo(article.created_at)}</span>
                </div>
                <h3>${article.title}</h3>
                <p>${article.excerpt || article.content.substring(0, 150)}...</p>
                ${article.program_name ? `<span class="news-program">📚 ${article.program_name}</span>` : ''}
            </article>
        `).join('');
    }
    
    // ====== REAL-TIME UPDATES ======
    
    startRealTimeUpdates() {
        // Update dashboard stats every 30 seconds
        this.updateInterval = setInterval(() => {
            this.loadUserDashboard();
        }, 30000);
        
        // Check for new notifications every 10 seconds
        this.notificationInterval = setInterval(() => {
            this.loadNotifications();
        }, 10000);
    }
    
    stopRealTimeUpdates() {
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
        }
        if (this.notificationInterval) {
            clearInterval(this.notificationInterval);
        }
    }
    
    // ====== CROSS-COMPONENT COMMUNICATION ======
    
    setupEventListeners() {
        // Listen for navigation events
        document.addEventListener('campusHubNavigation', (event) => {
            this.handleNavigationEvent(event.detail);
        });
        
        // Listen for user actions
        document.addEventListener('userAction', (event) => {
            this.handleUserAction(event.detail);
        });
        
        // Handle page visibility changes
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.stopRealTimeUpdates();
            } else {
                this.startRealTimeUpdates();
                this.loadInitialData();
            }
        });
    }
    
    handleNavigationEvent(detail) {
        console.log('Navigation event:', detail);
        // Refresh data when user navigates
        this.loadInitialData();
    }
    
    handleUserAction(detail) {
        console.log('User action:', detail);
        // Handle specific user actions
        switch (detail.action) {
            case 'assignment_submitted':
                this.loadUpcomingDeadlines();
                this.loadUserDashboard();
                break;
            case 'notification_preferences_changed':
                this.loadNotifications();
                break;
        }
    }
    
    // ====== NOTIFICATION METHODS ======
    
    async markNotificationRead(notificationId) {
        try {
            const formData = new FormData();
            formData.append('notification_id', notificationId);
            
            const response = await fetch(`${this.apiBase}?action=mark-notification-read`, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            if (data.success) {
                // Update UI
                const notificationElement = document.querySelector(`[data-notification-id="${notificationId}"]`);
                if (notificationElement) {
                    notificationElement.classList.remove('unread');
                    notificationElement.classList.add('read');
                    const indicator = notificationElement.querySelector('.unread-indicator');
                    if (indicator) indicator.remove();
                }
                
                // Refresh notification count
                this.loadNotifications();
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }
    
    updateNotificationBadge(notifications) {
        const unreadCount = notifications.filter(n => !n.is_read).length;
        const badge = document.querySelector('.notification-badge');
        
        if (badge) {
            if (unreadCount > 0) {
                badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }
        }
    }
    
    // ====== UTILITY METHODS ======
    
    getCurrentUserId() {
        // Get current user ID from AuthManager
        if (window.AuthManager && AuthManager.isLoggedIn()) {
            const user = AuthManager.getCurrentUser();
            return user ? user.id : null;
        }
        
        // Fallback to localStorage
        const userData = localStorage.getItem('user_data');
        if (userData) {
            try {
                const user = JSON.parse(userData);
                return user.id;
            } catch (e) {
                console.error('Error parsing user data:', e);
            }
        }
        
        return null;
    }
    
    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    }
    
    formatTimeAgo(dateString) {
        const now = new Date();
        const date = new Date(dateString);
        const diffInSeconds = Math.floor((now - date) / 1000);
        
        if (diffInSeconds < 60) return 'Just now';
        if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} minutes ago`;
        if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} hours ago`;
        return `${Math.floor(diffInSeconds / 86400)} days ago`;
    }
    
    formatCalendarDay(dateString) {
        return new Date(dateString).getDate();
    }
    
    formatCalendarMonth(dateString) {
        return new Date(dateString).toLocaleDateString('en-US', { month: 'short' });
    }
    
    groupEventsByDate(events) {
        return events.reduce((groups, event) => {
            const date = event.event_date;
            if (!groups[date]) {
                groups[date] = [];
            }
            groups[date].push(event);
            return groups;
        }, {});
    }
    
    getNotificationIcon(type) {
        const icons = {
            'academic': '📚',
            'administrative': '📋',
            'social': '👥',
            'urgent': '🚨'
        };
        return icons[type] || '📢';
    }
    
    animateCountUp(element, targetValue) {
        const currentValue = parseInt(element.textContent) || 0;
        const increment = Math.ceil((targetValue - currentValue) / 20);
        let current = currentValue;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= targetValue) {
                current = targetValue;
                clearInterval(timer);
            }
            element.textContent = current;
        }, 50);
    }
    
    showError(message) {
        // Show error notification
        console.error(message);
        // You can implement a toast notification system here
    }
    
    showSuccess(message) {
        // Show success notification
        console.log(message);
        // You can implement a toast notification system here
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if user is logged in
    if (AuthManager && AuthManager.isLoggedIn()) {
        window.FullIntegrationManager = new FullIntegrationManager();
    }
});

// Clean up on page unload
window.addEventListener('beforeunload', function() {
    if (window.FullIntegrationManager) {
        window.FullIntegrationManager.stopRealTimeUpdates();
    }
});
