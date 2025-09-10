// Campus Hub - Header Functions
// Fixed and working header functionality

document.addEventListener('DOMContentLoaded', function() {
    initializeHeaderFunctions();
});

function initializeHeaderFunctions() {
    // Profile menu
    const profileMenu = document.querySelector('.profile-menu');
    if (profileMenu) {
        profileMenu.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleProfileMenu();
        });
    }
    
    // Notifications button
    const notificationsBtn = document.querySelector('.notifications-btn');
    if (notificationsBtn) {
        notificationsBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleNotifications();
        });
    }
    
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        enhanceSearchInput();
    }
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        closeAllDropdowns(e);
    });
}

// Toggle profile dropdown menu
function toggleProfileMenu() {
    // Remove any existing dropdown first
    const existingDropdown = document.querySelector('.profile-dropdown');
    if (existingDropdown) {
        existingDropdown.remove();
    }
    
    // Close notifications panel if open
    const notificationPanel = document.querySelector('.notification-panel');
    if (notificationPanel) {
        notificationPanel.classList.remove('show');
    }
    
    // Create new dropdown
    const dropdown = document.createElement('div');
    dropdown.className = 'profile-dropdown show';
    dropdown.innerHTML = `
        <div class="dropdown-menu">
            <a href="#profile" class="dropdown-item" data-action="profile">
                <i class="fas fa-user"></i> View Profile
            </a>
            <a href="user-settings.html" class="dropdown-item" data-action="settings">
                <i class="fas fa-cog"></i> Settings
            </a>
            <a href="help.html" class="dropdown-item" data-action="help">
                <i class="fas fa-question-circle"></i> Help & Support
            </a>
            <div class="dropdown-divider"></div>
            <button class="dropdown-item" data-action="logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>
    `;
    
    // Add to profile menu
    const profileMenu = document.querySelector('.profile-menu');
    profileMenu.appendChild(dropdown);
    
    // Add event listeners to dropdown items
    dropdown.querySelectorAll('.dropdown-item').forEach(item => {
        item.addEventListener('click', handleProfileAction);
    });
}

// Handle profile menu actions
function handleProfileAction(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const action = e.currentTarget.getAttribute('data-action');
    
    switch(action) {
        case 'profile':
            showToast('Opening your profile...', 'info');
            setTimeout(() => {
                window.location.href = 'user-profile.html';
            }, 500);
            break;
            
        case 'settings':
            showToast('Opening settings...', 'info');
            setTimeout(() => {
                window.location.href = 'user-settings.html';
            }, 500);
            break;
            
        case 'help':
            showToast('Opening help center...', 'info');
            setTimeout(() => {
                window.location.href = 'help.html';
            }, 500);
            break;
            
        case 'logout':
            handleLogout();
            break;
    }
    
    // Close dropdown
    const dropdown = document.querySelector('.profile-dropdown');
    if (dropdown) {
        dropdown.remove();
    }
}

// Toggle notifications panel
function toggleNotifications() {
    // Remove any existing panel first
    const existingPanel = document.querySelector('.notification-panel');
    if (existingPanel) {
        existingPanel.remove();
    }
    
    // Close profile dropdown if open
    const profileDropdown = document.querySelector('.profile-dropdown');
    if (profileDropdown) {
        profileDropdown.remove();
    }
    
    // Create new notification panel
    const panel = document.createElement('div');
    panel.className = 'notification-panel show';
    panel.innerHTML = `
        <div class="notification-header">
            <h4><i class="fas fa-bell"></i> Notifications</h4>
            <button class="mark-all-read" onclick="markAllNotificationsRead()">
                <i class="fas fa-check-double"></i> Mark all read
            </button>
        </div>
        <div class="notification-list">
            <div class="notification-item unread">
                <div class="notification-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="notification-content">
                    <h5>Assignment Due Tomorrow</h5>
                    <p>Database Management project is due tomorrow at 11:59 PM</p>
                    <span class="notification-time">2 hours ago</span>
                </div>
            </div>
            <div class="notification-item unread">
                <div class="notification-icon">
                    <i class="fas fa-utensils"></i>
                </div>
                <div class="notification-content">
                    <h5>Practical Exam Reminder</h5>
                    <p>Culinary Arts practical exam scheduled for September 15th</p>
                    <span class="notification-time">1 day ago</span>
                </div>
            </div>
            <div class="notification-item unread">
                <div class="notification-icon">
                    <i class="fas fa-bullhorn"></i>
                </div>
                <div class="notification-content">
                    <h5>Career Fair This Friday</h5>
                    <p>Industry employers will be at Main Hall from 10 AM to 4 PM</p>
                    <span class="notification-time">3 days ago</span>
                </div>
            </div>
            <div class="notification-item">
                <div class="notification-icon">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="notification-content">
                    <h5>Library Hours Extended</h5>
                    <p>Library will be open until 11 PM during exam week</p>
                    <span class="notification-time">1 week ago</span>
                </div>
            </div>
        </div>
        <div class="notification-footer">
            <a href="#" class="view-all-notifications">View All Notifications</a>
        </div>
    `;
    
    // Add to notifications button
    const notificationsBtn = document.querySelector('.notifications-btn');
    notificationsBtn.appendChild(panel);
}

// Mark all notifications as read
function markAllNotificationsRead() {
    const unreadItems = document.querySelectorAll('.notification-item.unread');
    unreadItems.forEach(item => {
        item.classList.remove('unread');
    });
    
    // Update notification badge
    const badge = document.querySelector('.notification-badge');
    if (badge) {
        badge.style.display = 'none';
    }
    
    showToast('All notifications marked as read', 'success');
}

// Handle logout
function handleLogout() {
    if (confirm('Are you sure you want to logout?')) {
        showToast('Logging out...', 'info');
        
        // Use auth manager for logout if available
        if (window.authManager) {
            window.authManager.logout();
        } else {
            // Fallback logout
            localStorage.removeItem('user_logged_in');
            localStorage.removeItem('user_data');
            localStorage.removeItem('user_token');
            sessionStorage.clear();
            
            // Redirect to login page after delay
            setTimeout(() => {
                window.location.href = 'user-login.html';
            }, 1500);
        }
    }
}

// Enhanced search functionality
function enhanceSearchInput() {
    const searchInput = document.getElementById('searchInput');
    const searchContainer = searchInput.parentElement;
    
    // Add clear button if not exists
    if (!searchContainer.querySelector('.search-clear')) {
        const clearBtn = document.createElement('button');
        clearBtn.className = 'search-clear';
        clearBtn.innerHTML = '<i class="fas fa-times"></i>';
        clearBtn.title = 'Clear search';
        searchContainer.appendChild(clearBtn);
        
        // Hide clear button initially
        clearBtn.style.display = 'none';
        
        // Show/hide clear button based on input
        searchInput.addEventListener('input', function() {
            if (this.value.length > 0) {
                clearBtn.style.display = 'block';
            } else {
                clearBtn.style.display = 'none';
            }
        });
        
        // Clear search on button click
        clearBtn.addEventListener('click', function() {
            searchInput.value = '';
            this.style.display = 'none';
            searchInput.focus();
            showToast('Search cleared', 'info');
        });
    }
    
    // Add search functionality
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            const query = this.value.trim();
            if (query) {
                performSearch(query);
            }
        }
    });
    
    // Add search icon click functionality
    const searchIcon = searchContainer.querySelector('.search-icon');
    if (searchIcon) {
        searchIcon.addEventListener('click', function() {
            const query = searchInput.value.trim();
            if (query) {
                performSearch(query);
            } else {
                searchInput.focus();
            }
        });
    }
}

// Perform search
function performSearch(query) {
    showToast(`Searching for: ${query}`, 'info');
    
    // Simple search implementation
    // In real app, this would make API call
    console.log('Searching for:', query);
    
    // Simulate search results
    setTimeout(() => {
        showToast(`Found results for: ${query}`, 'success');
    }, 1000);
}

// Close all dropdowns
function closeAllDropdowns(e) {
    if (!e.target.closest('.profile-menu')) {
        const profileDropdown = document.querySelector('.profile-dropdown');
        if (profileDropdown) {
            profileDropdown.remove();
        }
    }
    
    if (!e.target.closest('.notifications-btn') && !e.target.closest('.notification-panel')) {
        const notificationPanel = document.querySelector('.notification-panel');
        if (notificationPanel) {
            notificationPanel.classList.remove('show');
            setTimeout(() => {
                if (notificationPanel.parentNode) {
                    notificationPanel.remove();
                }
            }, 300);
        }
    }
}

// Toast notification system
function showToast(message, type = 'info') {
    // Remove existing toast
    const existingToast = document.querySelector('.toast');
    if (existingToast) {
        existingToast.remove();
    }
    
    // Create new toast
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <i class="fas fa-${getToastIcon(type)}"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    // Show toast
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    
    // Hide toast after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 300);
    }, 3000);
}

// Get toast icon based on type
function getToastIcon(type) {
    switch(type) {
        case 'success': return 'check-circle';
        case 'error': return 'exclamation-circle';
        case 'warning': return 'exclamation-triangle';
        case 'info':
        default: return 'info-circle';
    }
}

// Export functions for global use
window.toggleProfileMenu = toggleProfileMenu;
window.toggleNotifications = toggleNotifications;
window.markAllNotificationsRead = markAllNotificationsRead;
window.handleLogout = handleLogout;
window.showToast = showToast;
