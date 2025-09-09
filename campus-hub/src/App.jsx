import { useState } from 'react'
import './App.css'

function App() {
  const [currentTime, setCurrentTime] = useState(new Date())
  
  // Update time every minute
  useState(() => {
    const timer = setInterval(() => {
      setCurrentTime(new Date())
    }, 60000)
    return () => clearInterval(timer)
  }, [])

  const formatTime = (date) => {
    return date.toLocaleTimeString('en-US', { 
      hour: 'numeric', 
      minute: '2-digit',
      hour12: true 
    })
  }

  const formatDate = (date) => {
    return date.toLocaleDateString('en-US', { 
      weekday: 'long',
      year: 'numeric', 
      month: 'long', 
      day: 'numeric' 
    })
  }

  const quickAccessItems = [
    { title: 'Course Schedule', icon: '📅', color: '#4A90E2' },
    { title: 'Grades & GPA', icon: '📊', color: '#50C878' },
    { title: 'Library Resources', icon: '📚', color: '#FF6B6B' },
    { title: 'Campus Map', icon: '🗺️', color: '#FFD93D' },
    { title: 'Dining Services', icon: '🍽️', color: '#FF9F43' },
    { title: 'Student Services', icon: '🎓', color: '#6C5CE7' },
    { title: 'Financial Aid', icon: '💰', color: '#00B894' },
    { title: 'Events Calendar', icon: '🎉', color: '#FD79A8' }
  ]

  const recentAnnouncements = [
    {
      title: 'Spring Registration Opens',
      date: '2 hours ago',
      content: 'Registration for spring semester courses begins Monday, December 1st at 8:00 AM.'
    },
    {
      title: 'Library Extended Hours',
      date: '1 day ago', 
      content: 'The library will be open 24/7 during finals week to support your studies.'
    },
    {
      title: 'Career Fair Next Week',
      date: '2 days ago',
      content: 'Join us for the annual career fair featuring 100+ employers from various industries.'
    }
  ]

  const upcomingEvents = [
    { event: 'Mathematics Exam', time: 'Today, 2:00 PM', type: 'exam' },
    { event: 'Study Group - Physics', time: 'Tomorrow, 6:00 PM', type: 'study' },
    { event: 'Guest Lecture: AI Ethics', time: 'Friday, 3:00 PM', type: 'lecture' },
    { event: 'Weekend Social Event', time: 'Saturday, 7:00 PM', type: 'social' }
  ]

  return (
    <div className="campus-hub">
      {/* Header */}
      <header className="hub-header">
        <div className="header-content">
          <div className="logo-section">
            <h1>🎓 Campus Hub</h1>
            <p>Your Student Portal</p>
          </div>
          <div className="user-section">
            <div className="time-date">
              <div className="current-time">{formatTime(currentTime)}</div>
              <div className="current-date">{formatDate(currentTime)}</div>
            </div>
            <div className="user-profile">
              <span className="user-avatar">👤</span>
              <span className="user-name">Welcome, Student</span>
            </div>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="hub-main">
        <div className="main-content">
          
          {/* Quick Access Section */}
          <section className="quick-access">
            <h2>Quick Access</h2>
            <div className="access-grid">
              {quickAccessItems.map((item, index) => (
                <div 
                  key={index} 
                  className="access-card"
                  style={{ borderLeft: `4px solid ${item.color}` }}
                  onClick={() => alert(`Opening ${item.title}...`)}
                >
                  <div className="card-icon" style={{ color: item.color }}>
                    {item.icon}
                  </div>
                  <div className="card-title">{item.title}</div>
                </div>
              ))}
            </div>
          </section>

          {/* Dashboard Content */}
          <div className="dashboard-grid">
            
            {/* Announcements */}
            <section className="announcements">
              <h3>📢 Campus Announcements</h3>
              <div className="announcements-list">
                {recentAnnouncements.map((announcement, index) => (
                  <div key={index} className="announcement-item">
                    <div className="announcement-header">
                      <h4>{announcement.title}</h4>
                      <span className="announcement-date">{announcement.date}</span>
                    </div>
                    <p>{announcement.content}</p>
                  </div>
                ))}
              </div>
            </section>

            {/* Upcoming Events */}
            <section className="upcoming-events">
              <h3>⏰ Upcoming Events</h3>
              <div className="events-list">
                {upcomingEvents.map((event, index) => (
                  <div key={index} className={`event-item ${event.type}`}>
                    <div className="event-info">
                      <div className="event-title">{event.event}</div>
                      <div className="event-time">{event.time}</div>
                    </div>
                    <div className={`event-type-indicator ${event.type}`}></div>
                  </div>
                ))}
              </div>
            </section>

          </div>

          {/* Quick Stats */}
          <section className="quick-stats">
            <div className="stats-grid">
              <div className="stat-card">
                <div className="stat-value">3.7</div>
                <div className="stat-label">Current GPA</div>
              </div>
              <div className="stat-card">
                <div className="stat-value">15</div>
                <div className="stat-label">Credit Hours</div>
              </div>
              <div className="stat-card">
                <div className="stat-value">42</div>
                <div className="stat-label">Days Until Finals</div>
              </div>
              <div className="stat-card">
                <div className="stat-value">$2,450</div>
                <div className="stat-label">Account Balance</div>
              </div>
            </div>
          </section>

        </div>
      </main>

      {/* Footer */}
      <footer className="hub-footer">
        <div className="footer-content">
          <p>&copy; 2024 Campus Hub - Making student life easier, one click at a time.</p>
          <div className="footer-links">
            <a href="#help">Help & Support</a>
            <a href="#privacy">Privacy Policy</a>
            <a href="#contact">Contact IT</a>
          </div>
        </div>
      </footer>
    </div>
  )
}

export default App
