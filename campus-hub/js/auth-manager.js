// Campus Hub - Authentication & Session Management
// Handles user authentication, session persistence, and access control

class AuthManager {
    constructor() {
        this.init();
    }
    
    init() {
        // Check authentication status on page load
        this.checkSession();
        this.updateUIBasedOnAuth();
    }
    
    // Check if user is logged in
    isLoggedIn() {
        const isLoggedIn = localStorage.getItem('user_logged_in');
        const userData = localStorage.getItem('user_data');
        const token = localStorage.getItem('user_token');
        
        return isLoggedIn === 'true' && userData && token;
    }
    
    // Get current user data
    getCurrentUser() {
        if (!this.isLoggedIn()) return null;
        
        try {
            const userData = localStorage.getItem('user_data');
            return JSON.parse(userData);
        } catch (error) {
            console.error('Error parsing user data:', error);
            this.logout();
            return null;
        }
    }
    
    // Get user token
    getToken() {
        return localStorage.getItem('user_token');
    }
    
    // Check session and redirect if necessary
    checkSession() {
        const currentPage = window.location.pathname.split('/').pop();
        const protectedPages = [
            'student-dashboard.html',
            'staff-dashboard.html',
            'user-profile.html',
            'user-settings.html'
        ];
        
        // If on a protected page, ensure user is logged in
        if (protectedPages.includes(currentPage)) {
            if (!this.isLoggedIn()) {
                this.redirectToLogin();
                return;
            }
            
            // Check if user has correct role for the page
            const user = this.getCurrentUser();
            if (user) {
                this.validatePageAccess(currentPage, user.role);
            }
        }
        
        // If on login page and already logged in, redirect to dashboard
        if (currentPage === 'user-login.html' && this.isLoggedIn()) {
            this.redirectToDashboard();
        }
    }
    
    // Validate if user has access to current page
    validatePageAccess(page, userRole) {
        const pageRoleMap = {
            'student-dashboard.html': 'student',
            'staff-dashboard.html': 'staff'
        };
        
        const requiredRole = pageRoleMap[page];
        if (requiredRole && userRole !== requiredRole && userRole !== 'admin') {
            alert(`Access denied. This page is for ${requiredRole}s only.`);
            this.redirectToDashboard();
        }
    }
    
    // Update UI elements based on authentication status
    updateUIBasedOnAuth() {
        const user = this.getCurrentUser();
        
        if (user) {
            this.updateHeaderForLoggedInUser(user);
            this.showUserSpecificContent(user);
        } else {
            this.updateHeaderForGuestUser();
            this.showGuestContent();
        }
    }
    
    // Update header for logged in user
    updateHeaderForLoggedInUser(user) {
        // Update user name in profile menu
        const userNameElement = document.querySelector('.user-name');
        if (userNameElement) {
            userNameElement.textContent = user.full_name || user.username;
        }
        
        // Update user avatar if available
        const userAvatar = document.querySelector('.user-avatar img');
        if (userAvatar && user.avatar) {
            userAvatar.src = user.avatar;
        }
        
        // Show logout option in profile dropdown
        const profileMenu = document.querySelector('.profile-menu');
        if (profileMenu && !profileMenu.classList.contains('auth-enabled')) {
            profileMenu.classList.add('auth-enabled');
        }
        
        // Update navigation links based on role
        this.updateNavigationForRole(user.role);
    }
    
    // Update header for guest user
    updateHeaderForGuestUser() {
        // Show login button instead of profile menu
        const headerActions = document.querySelector('.header-actions');
        if (headerActions && !document.querySelector('.login-btn-header')) {
            const loginBtn = document.createElement('a');
            loginBtn.href = 'user-login.html';
            loginBtn.className = 'btn btn-primary login-btn-header';
            loginBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Login';
            
            // Replace profile menu with login button
            const profileMenu = document.querySelector('.profile-menu');
            if (profileMenu) {
                headerActions.replaceChild(loginBtn, profileMenu);
            }
        }
    }
    
    // Update navigation based on user role
    updateNavigationForRole(role) {
        const nav = document.querySelector('.nav');
        if (!nav) return;
        
        // Add role-specific navigation items
        if (role === 'student') {
            this.addNavItem(nav, 'student-dashboard.html', 'Dashboard', 'fas fa-tachometer-alt');
        } else if (role === 'staff') {
            this.addNavItem(nav, 'staff-dashboard.html', 'Dashboard', 'fas fa-tachometer-alt');
        } else if (role === 'admin') {
            this.addNavItem(nav, 'admin/index.html', 'Admin Panel', 'fas fa-cog');
        }
    }
    
    // Add navigation item
    addNavItem(nav, href, text, iconClass) {
        // Check if item already exists
        const existingItem = nav.querySelector(`a[href="${href}"]`);
        if (existingItem) return;
        
        const navItem = document.createElement('a');
        navItem.href = href;
        navItem.className = 'nav-link';
        navItem.innerHTML = `
            <i class="${iconClass}"></i>
            <span>${text}</span>
        `;
        
        // Insert after home link
        const homeLink = nav.querySelector('a[href="index.html"]');
        if (homeLink && homeLink.nextSibling) {
            nav.insertBefore(navItem, homeLink.nextSibling);
        } else {
            nav.appendChild(navItem);
        }
    }
    
    // Show content specific to logged in users
    showUserSpecificContent(user) {
        // Show personalized welcome message
        const welcomeElements = document.querySelectorAll('.welcome-user');
        welcomeElements.forEach(element => {
            element.textContent = `Welcome back, ${user.full_name || user.username}!`;
            element.style.display = 'block';
        });
        
        // Hide guest-only content
        const guestElements = document.querySelectorAll('.guest-only');
        guestElements.forEach(element => {
            element.style.display = 'none';
        });
        
        // Show user-only content
        const userElements = document.querySelectorAll('.user-only');
        userElements.forEach(element => {
            element.style.display = 'block';
        });
    }
    
    // Show content for guest users
    showGuestContent() {
        // Hide user-only content
        const userElements = document.querySelectorAll('.user-only');
        userElements.forEach(element => {
            element.style.display = 'none';
        });
        
        // Show guest-only content
        const guestElements = document.querySelectorAll('.guest-only');
        guestElements.forEach(element => {
            element.style.display = 'block';
        });
        
        // Hide personalized welcome messages
        const welcomeElements = document.querySelectorAll('.welcome-user');
        welcomeElements.forEach(element => {
            element.style.display = 'none';
        });
    }
    
    // Login user and save session
    login(userData, token) {
        localStorage.setItem('user_logged_in', 'true');
        localStorage.setItem('user_data', JSON.stringify(userData));
        localStorage.setItem('user_token', token);
        localStorage.setItem('login_timestamp', new Date().getTime().toString());
        
        this.updateUIBasedOnAuth();
        
        console.log('User logged in:', userData);
    }
    
    // Logout user and clear session
    logout() {
        localStorage.removeItem('user_logged_in');
        localStorage.removeItem('user_data');
        localStorage.removeItem('user_token');
        localStorage.removeItem('login_timestamp');
        sessionStorage.clear();
        
        console.log('User logged out');
        
        // Redirect to login page
        this.redirectToLogin();
    }
    
    // Redirect to appropriate dashboard based on user role
    redirectToDashboard() {
        const user = this.getCurrentUser();
        if (!user) {
            this.redirectToLogin();
            return;
        }
        
        switch(user.role) {
            case 'student':
                window.location.href = 'student-dashboard.html';
                break;
            case 'staff':
                window.location.href = 'staff-dashboard.html';
                break;
            case 'admin':
                window.location.href = 'admin/index.html';
                break;
            default:
                this.redirectToLogin();
        }
    }
    
    // Redirect to login page
    redirectToLogin() {
        // Don't redirect if already on login page
        const currentPage = window.location.pathname.split('/').pop();
        if (currentPage !== 'user-login.html') {
            window.location.href = 'user-login.html';
        }
    }
    
    // Check session expiry (24 hours)
    checkSessionExpiry() {
        const loginTimestamp = localStorage.getItem('login_timestamp');
        if (!loginTimestamp) return false;
        
        const now = new Date().getTime();
        const loginTime = parseInt(loginTimestamp);
        const twentyFourHours = 24 * 60 * 60 * 1000; // 24 hours in milliseconds
        
        if (now - loginTime > twentyFourHours) {
            alert('Your session has expired. Please log in again.');
            this.logout();
            return true;
        }
        
        return false;
    }
    
    // Extend session (refresh timestamp)
    extendSession() {
        if (this.isLoggedIn()) {
            localStorage.setItem('login_timestamp', new Date().getTime().toString());
        }
    }
    
    // Make authenticated API calls
    async apiCall(endpoint, options = {}) {
        const token = this.getToken();
        if (!token) {
            throw new Error('No authentication token available');
        }
        
        const defaultHeaders = {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
        };
        
        const config = {
            ...options,
            headers: {
                ...defaultHeaders,
                ...options.headers
            }
        };
        
        try {
            const response = await fetch(endpoint, config);
            
            // Handle unauthorized response
            if (response.status === 401) {
                alert('Session expired. Please log in again.');
                this.logout();
                throw new Error('Unauthorized');
            }
            
            return response;
        } catch (error) {
            console.error('API call failed:', error);
            throw error;
        }
    }
}

// Initialize authentication manager
let authManager;

document.addEventListener('DOMContentLoaded', function() {
    authManager = new AuthManager();
    
    // Check session expiry every 5 minutes
    setInterval(() => {
        if (authManager.isLoggedIn()) {
            authManager.checkSessionExpiry();
        }
    }, 5 * 60 * 1000);
    
    // Extend session on user activity
    let activityTimer;
    const extendSessionOnActivity = () => {
        clearTimeout(activityTimer);
        activityTimer = setTimeout(() => {
            authManager.extendSession();
        }, 1000);
    };
    
    document.addEventListener('click', extendSessionOnActivity);
    document.addEventListener('keypress', extendSessionOnActivity);
    document.addEventListener('scroll', extendSessionOnActivity);
});

// Export for global use
window.authManager = authManager;
window.AuthManager = AuthManager;
