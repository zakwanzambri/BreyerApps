# Campus Hub - Your Student Portal

A modern, beautiful portal for college students to access everything they need in one easy-to-use application. This solves the common problem of scattered information and resources that students face daily.

## Features

### 🎯 Quick Access Dashboard
- **Course Schedule** - View your class timetable
- **Grades & GPA** - Track academic performance  
- **Library Resources** - Access digital and physical library materials
- **Campus Map** - Navigate campus locations
- **Dining Services** - Check meal plans and dining hours
- **Student Services** - Access registrar, advising, and support
- **Financial Aid** - View aid status and payment info
- **Events Calendar** - Stay updated on campus events

### 📢 Real-time Information
- **Campus Announcements** - Latest news and updates
- **Upcoming Events** - Personal and campus-wide events
- **Quick Stats** - GPA, credits, account balance at a glance

### 📱 Modern Design
- **Responsive Design** - Works perfectly on desktop, tablet, and mobile
- **Beautiful Interface** - Clean, modern design with intuitive navigation
- **Real-time Updates** - Live time/date display
- **Interactive Elements** - Hover effects and smooth transitions

## Technology Stack

- **React 19** - Modern React with latest features
- **Vite** - Fast build tool and development server
- **CSS3** - Custom styling with gradients and modern effects
- **Responsive Design** - Mobile-first approach

## Getting Started

### Prerequisites
- Node.js (v16 or higher)
- npm or yarn

### Installation
```bash
cd campus-hub
npm install
```

### Development
```bash
npm run dev
```
Open http://localhost:5173 to view the portal.

### Build for Production
```bash
npm run build
```

### Preview Production Build
```bash
npm run preview
```

## Project Structure
```
campus-hub/
├── src/
│   ├── App.jsx          # Main portal component
│   ├── App.css          # Portal styling
│   ├── index.css        # Global styles
│   └── main.jsx         # React app entry point
├── public/              # Static assets
├── index.html           # HTML template
└── package.json         # Dependencies and scripts
```

## Features in Detail

### Quick Access Cards
Eight main service areas with color-coded design:
- Visual icons for easy recognition
- Hover effects for interactivity
- Click functionality (currently shows alerts, ready for integration)

### Information Panels
- **Announcements**: Latest campus news with timestamps
- **Events**: Color-coded by type (exam, study, lecture, social)
- **Stats**: Key student metrics in an easy-to-scan format

### Responsive Behavior
- Desktop: Multi-column grid layout
- Tablet: Responsive grid adjustment
- Mobile: Single-column stack with optimized spacing

## Future Enhancements
- User authentication and personalization
- Real-time data integration with campus systems
- Push notifications for important updates
- Dark mode support
- Advanced search and filtering
- Integration with student information systems

## Contributing
This portal is designed to be easily extensible. Key areas for enhancement:
1. Backend integration for real data
2. Additional service modules
3. Advanced personalization features
4. Accessibility improvements

---

**Campus Hub** - Making student life easier, one click at a time. 🎓
