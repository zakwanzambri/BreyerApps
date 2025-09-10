/**
 * Campus Hub Admin Dashboard JavaScript
 * Comprehensive admin interface functionality
 */

class AdminDashboard {
    constructor() {
        this.currentSection = 'overview';
        this.charts = {};
        this.updateInterval = null;
        this.apiBaseUrl = '../php/api';
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.loadInitialData();
        this.initializeCharts();
        this.startAutoRefresh();
        
        console.log('Admin Dashboard initialized');
    }
    
    setupEventListeners() {
        // Sidebar toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', this.toggleSidebar.bind(this));
        }
        
        // Navigation links
        document.querySelectorAll('.nav-link[data-section]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const section = link.dataset.section;
                this.showSection(section);
            });
        });
        
        // Window resize handler
        window.addEventListener('resize', this.handleResize.bind(this));
        
        // Notification bell
        const notificationBell = document.getElementById('notificationBell');
        if (notificationBell) {
            notificationBell.addEventListener('click', this.showNotifications.bind(this));
        }
    }
    
    toggleSidebar() {
        const sidebar = document.getElementById('adminSidebar');
        const main = document.getElementById('adminMain');
        
        if (window.innerWidth > 768) {
            sidebar.classList.toggle('collapsed');
            main.classList.toggle('expanded');
        } else {
            sidebar.classList.toggle('show');
        }
    }
    
    showSection(sectionName) {
        // Hide all sections
        document.querySelectorAll('.content-section').forEach(section => {
            section.classList.remove('active');
        });
        
        // Show target section
        const targetSection = document.getElementById(`${sectionName}-section`);
        if (targetSection) {
            targetSection.classList.add('active');
        } else {
            // Show placeholder for non-implemented sections
            document.getElementById('placeholder-section').classList.add('active');
        }
        
        // Update navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });
        
        const activeLink = document.querySelector(`[data-section="${sectionName}"]`);
        if (activeLink) {
            activeLink.classList.add('active');
        }
        
        // Update breadcrumb
        document.getElementById('currentSection').textContent = 
            this.getSectionTitle(sectionName);
        
        this.currentSection = sectionName;
        
        // Load section-specific data
        this.loadSectionData(sectionName);
    }
    
    getSectionTitle(sectionName) {
        const titles = {
            'overview': 'Overview',
            'analytics': 'Analytics',
            'news': 'News Management',
            'events': 'Events Management',
            'media': 'Media Library',
            'users': 'User Management',
            'roles': 'Roles & Permissions',
            'sessions': 'Active Sessions',
            'monitoring': 'System Monitor',
            'logs': 'System Logs',
            'backups': 'Backups',
            'settings': 'Settings',
            'maintenance': 'Maintenance'
        };
        
        return titles[sectionName] || 'Dashboard';
    }
    
    async loadInitialData() {
        try {
            await Promise.all([
                this.loadDashboardStats(),
                this.loadRecentActivity(),
                this.loadSystemAlerts(),
                this.loadNotificationCount()
            ]);
        } catch (error) {
            console.error('Error loading initial data:', error);
            this.showAlert('Error loading dashboard data', 'danger');
        }
    }
    
    async loadDashboardStats() {
        try {
            // Simulate API call for now
            const stats = await this.mockApiCall('dashboard-stats', {
                totalUsers: 1247,
                totalNews: 89,
                totalEvents: 23,
                activeUsers: 156,
                pendingApprovals: 12,
                systemHealth: 98
            });
            
            this.renderStatsGrid(stats);
        } catch (error) {
            console.error('Error loading dashboard stats:', error);
        }
    }
    
    renderStatsGrid(stats) {
        const statsGrid = document.getElementById('statsGrid');
        if (!statsGrid) return;
        
        const statsCards = [
            {
                title: 'Total Users',
                value: stats.totalUsers,
                change: '+12%',
                changeType: 'positive',
                icon: 'fas fa-users',
                iconClass: 'primary'
            },
            {
                title: 'Active Users',
                value: stats.activeUsers,
                change: '+8%',
                changeType: 'positive',
                icon: 'fas fa-user-check',
                iconClass: 'success'
            },
            {
                title: 'News Articles',
                value: stats.totalNews,
                change: '+5',
                changeType: 'positive',
                icon: 'fas fa-newspaper',
                iconClass: 'info'
            },
            {
                title: 'Pending Approvals',
                value: stats.pendingApprovals,
                change: '-3',
                changeType: 'negative',
                icon: 'fas fa-clock',
                iconClass: 'warning'
            },
            {
                title: 'System Health',
                value: `${stats.systemHealth}%`,
                change: '+2%',
                changeType: 'positive',
                icon: 'fas fa-heartbeat',
                iconClass: 'success'
            },
            {
                title: 'Events This Month',
                value: stats.totalEvents,
                change: '+7',
                changeType: 'positive',
                icon: 'fas fa-calendar-alt',
                iconClass: 'primary'
            }
        ];
        
        statsGrid.innerHTML = statsCards.map(stat => `
            <div class="stat-card ${stat.iconClass}">
                <div class="stat-header">
                    <span class="stat-title">${stat.title}</span>
                    <div class="stat-icon ${stat.iconClass}">
                        <i class="${stat.icon}"></i>
                    </div>
                </div>
                <div class="stat-value">${stat.value}</div>
                <div class="stat-change ${stat.changeType}">
                    <i class="fas fa-arrow-${stat.changeType === 'positive' ? 'up' : 'down'}"></i>
                    ${stat.change} from last month
                </div>
            </div>
        `).join('');
        
        // Update navigation badges
        document.getElementById('pendingNews').textContent = Math.floor(stats.pendingApprovals / 2);
        document.getElementById('upcomingEvents').textContent = stats.totalEvents;
    }
    
    async loadRecentActivity() {
        try {
            // Simulate API call
            const activities = await this.mockApiCall('recent-activity', [
                {
                    time: '2 minutes ago',
                    user: 'Ahmad Syafiq',
                    action: 'Published article',
                    resource: 'Campus News Update',
                    status: 'success'
                },
                {
                    time: '15 minutes ago',
                    user: 'Sarah Johnson',
                    action: 'Created event',
                    resource: 'Tech Workshop 2024',
                    status: 'pending'
                },
                {
                    time: '1 hour ago',
                    user: 'System',
                    action: 'Backup completed',
                    resource: 'Daily backup',
                    status: 'success'
                },
                {
                    time: '2 hours ago',
                    user: 'Mike Chen',
                    action: 'User registration',
                    resource: 'New student account',
                    status: 'success'
                },
                {
                    time: '3 hours ago',
                    user: 'Admin',
                    action: 'System maintenance',
                    resource: 'Database optimization',
                    status: 'success'
                }
            ]);
            
            this.renderRecentActivity(activities);
        } catch (error) {
            console.error('Error loading recent activity:', error);
        }
    }
    
    renderRecentActivity(activities) {
        const tableBody = document.querySelector('#recentActivityTable tbody');
        if (!tableBody) return;
        
        tableBody.innerHTML = activities.map(activity => `
            <tr>
                <td>${activity.time}</td>
                <td>${activity.user}</td>
                <td>${activity.action}</td>
                <td>${activity.resource}</td>
                <td>
                    <span class="status-badge status-${activity.status}">
                        ${activity.status}
                    </span>
                </td>
            </tr>
        `).join('');
    }
    
    async loadSystemAlerts() {
        try {
            // Simulate API call
            const alerts = await this.mockApiCall('system-alerts', [
                {
                    type: 'warning',
                    message: 'Disk usage is at 85%',
                    time: '10 minutes ago'
                },
                {
                    type: 'info',
                    message: 'Scheduled maintenance in 2 days',
                    time: '1 hour ago'
                }
            ]);
            
            this.renderSystemAlerts(alerts);
        } catch (error) {
            console.error('Error loading system alerts:', error);
        }
    }
    
    renderSystemAlerts(alerts) {
        const alertsContainer = document.getElementById('systemAlerts');
        const alertsCount = document.getElementById('alertsCount');
        
        if (!alertsContainer) return;
        
        alertsCount.textContent = alerts.length;
        
        if (alerts.length === 0) {
            alertsContainer.innerHTML = `
                <div class="p-3 text-center text-muted">
                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                    <p>No system alerts</p>
                </div>
            `;
            return;
        }
        
        alertsContainer.innerHTML = alerts.map(alert => `
            <div class="alert alert-${alert.type} alert-dismissible fade show m-3" role="alert">
                <i class="fas fa-${alert.type === 'warning' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                <strong>${alert.message}</strong>
                <small class="d-block mt-1">${alert.time}</small>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `).join('');
    }
    
    async loadNotificationCount() {
        try {
            // Simulate API call
            const count = await this.mockApiCall('notification-count', 3);
            document.getElementById('notificationCount').textContent = count;
        } catch (error) {
            console.error('Error loading notification count:', error);
        }
    }
    
    initializeCharts() {
        this.initActivityChart();
        this.initContentChart();
        this.initPageViewsChart();
    }
    
    initActivityChart() {
        const ctx = document.getElementById('activityChart');
        if (!ctx) return;
        
        this.charts.activity = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [
                    {
                        label: 'Page Views',
                        data: [1200, 1900, 3000, 2100, 3200, 2800, 2400],
                        borderColor: '#0066cc',
                        backgroundColor: 'rgba(0, 102, 204, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'Active Users',
                        data: [300, 450, 600, 520, 680, 590, 480],
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    initContentChart() {
        const ctx = document.getElementById('contentChart');
        if (!ctx) return;
        
        this.charts.content = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['News Articles', 'Events', 'Media Files', 'User Posts'],
                datasets: [{
                    data: [45, 25, 20, 10],
                    backgroundColor: [
                        '#0066cc',
                        '#28a745',
                        '#ffc107',
                        '#17a2b8'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
    
    initPageViewsChart() {
        const ctx = document.getElementById('pageViewsChart');
        if (!ctx) return;
        
        this.charts.pageViews = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: Array.from({length: 30}, (_, i) => `Day ${i + 1}`),
                datasets: [{
                    label: 'Page Views',
                    data: Array.from({length: 30}, () => Math.floor(Math.random() * 1000) + 500),
                    backgroundColor: 'rgba(0, 102, 204, 0.7)',
                    borderColor: '#0066cc',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    async loadSectionData(sectionName) {
        switch (sectionName) {
            case 'analytics':
                await this.loadAnalyticsData();
                break;
            case 'monitoring':
                await this.loadSystemMetrics();
                break;
            case 'users':
                await this.loadUsersData();
                break;
            case 'news':
                await this.loadNewsData();
                break;
            case 'events':
                await this.loadEventsData();
                break;
        }
    }
    
    async loadAnalyticsData() {
        try {
            // Load top search queries
            const queries = await this.mockApiCall('top-search-queries', [
                { query: 'campus events', count: 245 },
                { query: 'library hours', count: 189 },
                { query: 'course registration', count: 156 },
                { query: 'dining hall menu', count: 134 },
                { query: 'parking permit', count: 98 }
            ]);
            
            this.renderTopSearchQueries(queries);
        } catch (error) {
            console.error('Error loading analytics data:', error);
        }
    }
    
    renderTopSearchQueries(queries) {
        const container = document.getElementById('topSearchQueries');
        if (!container) return;
        
        container.innerHTML = `
            <div class="list-group list-group-flush">
                ${queries.map((query, index) => `
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-primary rounded-pill me-2">${index + 1}</span>
                            ${query.query}
                        </div>
                        <span class="badge bg-secondary">${query.count}</span>
                    </div>
                `).join('')}
            </div>
        `;
    }
    
    async loadSystemMetrics() {
        try {
            const metrics = await this.mockApiCall('system-metrics', {
                cpu: { usage: 45, label: '45%' },
                memory: { usage: 67, label: '67%' },
                disk: { usage: 85, label: '85%' },
                dbConnections: '12/100',
                activeSessions: '156',
                apiRequests: '2,847',
                errorRate: '0.02%',
                cacheHitRate: '94.5%',
                responseTime: '245ms'
            });
            
            this.renderSystemMetrics(metrics);
        } catch (error) {
            console.error('Error loading system metrics:', error);
        }
    }
    
    renderSystemMetrics(metrics) {
        // Update server metrics
        document.getElementById('cpuUsage').textContent = metrics.cpu.label;
        document.getElementById('cpuProgress').style.width = `${metrics.cpu.usage}%`;
        
        document.getElementById('memoryUsage').textContent = metrics.memory.label;
        document.getElementById('memoryProgress').style.width = `${metrics.memory.usage}%`;
        
        document.getElementById('diskUsage').textContent = metrics.disk.label;
        document.getElementById('diskProgress').style.width = `${metrics.disk.usage}%`;
        
        // Update application metrics
        document.getElementById('dbConnections').textContent = metrics.dbConnections;
        document.getElementById('activeSessions').textContent = metrics.activeSessions;
        document.getElementById('apiRequests').textContent = metrics.apiRequests;
        document.getElementById('errorRate').textContent = metrics.errorRate;
        document.getElementById('cacheHitRate').textContent = metrics.cacheHitRate;
        document.getElementById('responseTime').textContent = metrics.responseTime;
    }
    
    startAutoRefresh() {
        // Refresh dashboard data every 30 seconds
        this.updateInterval = setInterval(() => {
            if (this.currentSection === 'overview') {
                this.loadDashboardStats();
                this.loadRecentActivity();
                this.loadSystemAlerts();
            } else if (this.currentSection === 'monitoring') {
                this.loadSystemMetrics();
            }
        }, 30000);
    }
    
    handleResize() {
        // Redraw charts on window resize
        Object.values(this.charts).forEach(chart => {
            if (chart && typeof chart.resize === 'function') {
                chart.resize();
            }
        });
    }
    
    showNotifications() {
        // Show notifications modal or dropdown
        console.log('Showing notifications...');
    }
    
    showAlert(message, type = 'info') {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        // Add alert to the top of admin content
        const adminContent = document.querySelector('.admin-content');
        if (adminContent) {
            adminContent.insertAdjacentHTML('afterbegin', alertHtml);
        }
    }
    
    // Utility function to simulate API calls
    async mockApiCall(endpoint, defaultData) {
        // Simulate network delay
        await new Promise(resolve => setTimeout(resolve, 100));
        
        // Try to make real API call first
        try {
            const response = await fetch(`${this.apiBaseUrl}/${endpoint}.php`);
            if (response.ok) {
                return await response.json();
            }
        } catch (error) {
            console.log(`Using mock data for ${endpoint}:`, error.message);
        }
        
        // Return mock data if API call fails
        return defaultData;
    }
}

// Global functions called from HTML
window.refreshDashboard = function() {
    if (window.adminDashboard) {
        window.adminDashboard.loadInitialData();
    }
};

window.updateActivityChart = function(days) {
    console.log('Updating activity chart for', days, 'days');
    // Implementation would update the chart with new data
};

window.exportReport = function(format) {
    console.log('Exporting report in', format, 'format');
    // Implementation would generate and download the report
};

window.refreshSystemMetrics = function() {
    if (window.adminDashboard) {
        window.adminDashboard.loadSystemMetrics();
    }
};

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.adminDashboard = new AdminDashboard();
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (window.adminDashboard && window.adminDashboard.updateInterval) {
        clearInterval(window.adminDashboard.updateInterval);
    }
});
