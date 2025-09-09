/**
 * Admin Panel JavaScript
 * Campus Hub Portal - Enhanced Version
 */

class AdminPanel {
    constructor() {
        this.apiBase = '../php/api/';
        this.currentSection = 'dashboard';
        this.init();
    }
    
    init() {
        this.setupNavigation();
        this.setupEventListeners();
        this.checkAuthentication();
        this.loadDashboard();
    }
    
    setupNavigation() {
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const section = e.target.closest('a').dataset.section;
                this.showSection(section);
            });
        });
    }
    
    setupEventListeners() {
        // News form
        const newsForm = document.getElementById('news-form-element');
        if (newsForm) {
            newsForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveNews();
            });
        }
        
        // Event form
        const eventForm = document.getElementById('event-form-element');
        if (eventForm) {
            eventForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveEvent();
            });
        }
    }
    
    async checkAuthentication() {
        try {
            // Check if user is logged in via localStorage (for demo)
            const adminLoggedIn = localStorage.getItem('admin_logged_in');
            const adminUser = localStorage.getItem('admin_user');
            
            if (adminLoggedIn === 'true' && adminUser) {
                const user = JSON.parse(adminUser);
                document.getElementById('current-user').textContent = `Welcome, ${user.full_name || user.username}`;
                return;
            }
            
            // Try API authentication
            const response = await fetch(`${this.apiBase}auth.php?action=check_session`);
            const data = await response.json();
            
            if (data.success && data.data && data.data.authenticated) {
                const user = data.data.user;
                if (user.role !== 'admin' && user.role !== 'staff') {
                    alert('Access denied. Admin privileges required.');
                    window.location.href = '../index.html';
                    return;
                }
                document.getElementById('current-user').textContent = `Welcome, ${user.full_name || user.username}`;
                return;
            }
            
            // Not authenticated, redirect to login
            window.location.href = 'login.html';
            
        } catch (error) {
            console.error('Auth check failed:', error);
            // For demo purposes, allow access if API is down
            console.log('API unavailable, allowing demo access');
            document.getElementById('current-user').textContent = 'Welcome, Admin (Demo Mode)';
        }
    }
    
    showSection(sectionName) {
        // Hide all sections
        document.querySelectorAll('.content-section').forEach(section => {
            section.classList.add('hidden');
        });
        
        // Remove active class from all nav links
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });
        
        // Show selected section
        const section = document.getElementById(`${sectionName}-section`);
        if (section) {
            section.classList.remove('hidden');
        }
        
        // Add active class to current nav link
        const activeLink = document.querySelector(`[data-section="${sectionName}"]`);
        if (activeLink) {
            activeLink.classList.add('active');
        }
        
        // Update page title
        const titles = {
            dashboard: 'Dashboard',
            news: 'News & Announcements',
            events: 'Events & Calendar',
            services: 'Campus Services',
            users: 'User Management',
            programs: 'Programs',
            settings: 'Settings'
        };
        
        document.getElementById('page-title').textContent = titles[sectionName] || 'Dashboard';
        this.currentSection = sectionName;
        
        // Load section data
        this.loadSectionData(sectionName);
    }
    
    async loadSectionData(sectionName) {
        switch (sectionName) {
            case 'dashboard':
                this.loadDashboard();
                break;
            case 'news':
                this.loadNews();
                break;
            case 'events':
                this.loadEvents();
                break;
            // Add other cases as needed
        }
    }
    
    async loadDashboard() {
        try {
            // Load statistics
            const [newsResponse, eventsResponse] = await Promise.all([
                fetch(`${this.apiBase}news.php?action=list&limit=1`),
                fetch(`${this.apiBase}events.php?action=upcoming&limit=1`)
            ]);
            
            const newsData = await newsResponse.json();
            const eventsData = await eventsResponse.json();
            
            // Update stat cards
            document.getElementById('news-count').textContent = newsData.data?.pagination?.total || 0;
            document.getElementById('events-count').textContent = eventsData.data?.length || 0;
            
            // Load recent activity
            this.loadRecentActivity();
        } catch (error) {
            console.error('Failed to load dashboard:', error);
        }
    }
    
    async loadRecentActivity() {
        try {
            const response = await fetch(`${this.apiBase}news.php?action=recent&limit=5`);
            const data = await response.json();
            
            if (data.success && data.data) {
                const activityHtml = data.data.map(item => `
                    <div style="padding: 1rem; border-bottom: 1px solid var(--gray-200);">
                        <strong>${item.title}</strong>
                        <div style="color: var(--gray-600); font-size: 0.9em;">${item.time_ago} - ${item.category}</div>
                    </div>
                `).join('');
                
                document.getElementById('recent-activity').innerHTML = activityHtml || '<p>No recent activity</p>';
            }
        } catch (error) {
            console.error('Failed to load recent activity:', error);
            document.getElementById('recent-activity').innerHTML = '<p>Failed to load recent activity</p>';
        }
    }
    
    async loadNews() {
        try {
            const response = await fetch(`${this.apiBase}news.php?action=list&limit=20`);
            const data = await response.json();
            
            if (data.success && data.data) {
                const newsHtml = this.renderNewsTable(data.data.news);
                document.getElementById('news-list').innerHTML = newsHtml;
            }
        } catch (error) {
            console.error('Failed to load news:', error);
            document.getElementById('news-list').innerHTML = '<p>Failed to load news articles</p>';
        }
    }
    
    renderNewsTable(news) {
        if (!news || news.length === 0) {
            return '<p>No news articles found</p>';
        }
        
        const tableHtml = `
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Author</th>
                        <th>Published</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${news.map(item => `
                        <tr>
                            <td>
                                <strong>${item.title}</strong>
                                ${item.featured ? '<span style="color: var(--warning-color); margin-left: 0.5rem;">★ Featured</span>' : ''}
                            </td>
                            <td><span class="badge badge-${item.category}">${item.category}</span></td>
                            <td>${item.author_name || 'Unknown'}</td>
                            <td>${item.time_ago}</td>
                            <td><span class="badge badge-${item.status}">${item.status}</span></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-small btn-edit" onclick="adminPanel.editNews(${item.id})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-small btn-delete" onclick="adminPanel.deleteNews(${item.id})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
        
        return tableHtml;
    }
    
    async loadEvents() {
        try {
            const response = await fetch(`${this.apiBase}events.php?action=calendar&limit=50`);
            const data = await response.json();
            
            if (data.success && data.data) {
                const eventsHtml = this.renderEventsTable(data.data);
                document.getElementById('events-list').innerHTML = eventsHtml;
            }
        } catch (error) {
            console.error('Failed to load events:', error);
            document.getElementById('events-list').innerHTML = '<p>Failed to load events</p>';
        }
    }
    
    renderEventsTable(events) {
        if (!events || events.length === 0) {
            return '<p>No events found</p>';
        }
        
        const tableHtml = `
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Event Title</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${events.map(item => `
                        <tr>
                            <td><strong>${item.title}</strong></td>
                            <td><span class="badge badge-${item.event_type}">${item.event_type}</span></td>
                            <td>
                                ${item.start_date_formatted}
                                ${item.start_time_formatted ? `<br><small>${item.start_time_formatted}</small>` : ''}
                            </td>
                            <td>${item.location || 'TBD'}</td>
                            <td><span class="badge badge-${item.status}">${item.status}</span></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-small btn-edit" onclick="adminPanel.editEvent(${item.id})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-small btn-delete" onclick="adminPanel.deleteEvent(${item.id})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
        
        return tableHtml;
    }
    
    // News management functions
    showNewsForm() {
        document.getElementById('news-form').classList.remove('hidden');
        document.getElementById('news-form-title').textContent = 'Add New Article';
        document.getElementById('news-form-element').reset();
        document.getElementById('news-id').value = '';
    }
    
    hideNewsForm() {
        document.getElementById('news-form').classList.add('hidden');
    }
    
    async saveNews() {
        const id = document.getElementById('news-id').value;
        const title = document.getElementById('news-title').value;
        const category = document.getElementById('news-category').value;
        const content = document.getElementById('news-content').value;
        const excerpt = document.getElementById('news-excerpt').value;
        const featured = document.getElementById('news-featured').checked;
        
        if (!title || !category || !content) {
            alert('Please fill in all required fields');
            return;
        }
        
        const newsData = {
            title,
            category,
            content,
            excerpt,
            featured,
            status: 'published'
        };
        
        try {
            const url = id ? 
                `${this.apiBase}news.php?action=update&id=${id}` : 
                `${this.apiBase}news.php?action=create`;
            
            const response = await fetch(url, {
                method: id ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(newsData)
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert(data.message);
                this.hideNewsForm();
                this.loadNews();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            console.error('Failed to save news:', error);
            alert('Failed to save news article');
        }
    }
    
    async editNews(id) {
        try {
            const response = await fetch(`${this.apiBase}news.php?action=detail&id=${id}`);
            const data = await response.json();
            
            if (data.success && data.data) {
                const news = data.data;
                document.getElementById('news-id').value = news.id;
                document.getElementById('news-title').value = news.title;
                document.getElementById('news-category').value = news.category;
                document.getElementById('news-content').value = news.content;
                document.getElementById('news-excerpt').value = news.excerpt || '';
                document.getElementById('news-featured').checked = news.featured;
                
                document.getElementById('news-form-title').textContent = 'Edit Article';
                document.getElementById('news-form').classList.remove('hidden');
            }
        } catch (error) {
            console.error('Failed to load news for editing:', error);
            alert('Failed to load news article');
        }
    }
    
    async deleteNews(id) {
        if (!confirm('Are you sure you want to delete this news article?')) {
            return;
        }
        
        try {
            const response = await fetch(`${this.apiBase}news.php?action=delete&id=${id}`, {
                method: 'DELETE'
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('News article deleted successfully');
                this.loadNews();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            console.error('Failed to delete news:', error);
            alert('Failed to delete news article');
        }
    }
    
    // Event management functions
    showEventForm() {
        document.getElementById('event-form').classList.remove('hidden');
        document.getElementById('event-form-title').textContent = 'Add New Event';
        document.getElementById('event-form-element').reset();
        document.getElementById('event-id').value = '';
    }
    
    hideEventForm() {
        document.getElementById('event-form').classList.add('hidden');
    }
    
    async saveEvent() {
        const id = document.getElementById('event-id').value;
        const title = document.getElementById('event-title').value;
        const eventType = document.getElementById('event-type').value;
        const description = document.getElementById('event-description').value;
        const startDate = document.getElementById('event-start-date').value;
        const endDate = document.getElementById('event-end-date').value;
        const startTime = document.getElementById('event-start-time').value;
        const endTime = document.getElementById('event-end-time').value;
        const location = document.getElementById('event-location').value;
        
        if (!title || !eventType || !startDate) {
            alert('Please fill in all required fields');
            return;
        }
        
        const eventData = {
            title,
            event_type: eventType,
            description,
            start_date: startDate,
            end_date: endDate || null,
            start_time: startTime || null,
            end_time: endTime || null,
            location
        };
        
        try {
            const url = id ? 
                `${this.apiBase}events.php?action=update&id=${id}` : 
                `${this.apiBase}events.php?action=create`;
            
            const response = await fetch(url, {
                method: id ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(eventData)
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert(data.message);
                this.hideEventForm();
                this.loadEvents();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            console.error('Failed to save event:', error);
            alert('Failed to save event');
        }
    }
    
    async editEvent(id) {
        try {
            const response = await fetch(`${this.apiBase}events.php?action=detail&id=${id}`);
            const data = await response.json();
            
            if (data.success && data.data) {
                const event = data.data;
                document.getElementById('event-id').value = event.id;
                document.getElementById('event-title').value = event.title;
                document.getElementById('event-type').value = event.event_type;
                document.getElementById('event-description').value = event.description || '';
                document.getElementById('event-start-date').value = event.start_date;
                document.getElementById('event-end-date').value = event.end_date || '';
                document.getElementById('event-start-time').value = event.start_time || '';
                document.getElementById('event-end-time').value = event.end_time || '';
                document.getElementById('event-location').value = event.location || '';
                
                document.getElementById('event-form-title').textContent = 'Edit Event';
                document.getElementById('event-form').classList.remove('hidden');
            }
        } catch (error) {
            console.error('Failed to load event for editing:', error);
            alert('Failed to load event');
        }
    }
    
    async deleteEvent(id) {
        if (!confirm('Are you sure you want to delete this event?')) {
            return;
        }
        
        try {
            const response = await fetch(`${this.apiBase}events.php?action=delete&id=${id}`, {
                method: 'DELETE'
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('Event deleted successfully');
                this.loadEvents();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            console.error('Failed to delete event:', error);
            alert('Failed to delete event');
        }
    }
}

// Global functions
function showNewsForm() {
    adminPanel.showNewsForm();
}

function hideNewsForm() {
    adminPanel.hideNewsForm();
}

function showEventForm() {
    adminPanel.showEventForm();
}

function hideEventForm() {
    adminPanel.hideEventForm();
}

async function logout() {
    try {
        await fetch('../php/api/auth.php?action=logout', {
            method: 'DELETE'
        });
        window.location.href = 'login.html';
    } catch (error) {
        console.error('Logout failed:', error);
        window.location.href = 'login.html';
    }
}

// Initialize admin panel
const adminPanel = new AdminPanel();
