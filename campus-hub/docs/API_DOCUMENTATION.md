# Campus Hub API Documentation

## Overview

Campus Hub Portal provides a comprehensive RESTful API for managing campus resources including users, news, events, and more. All API endpoints return JSON responses and support proper HTTP status codes.

## Base URL

```
http://localhost/BreyerApps/campus-hub/php/api/
```

## Authentication

Most endpoints require authentication. The system uses session-based authentication:

1. Login via `/auth.php?action=login`
2. Include session cookies in subsequent requests
3. Some endpoints require specific roles (admin, staff, student)

### Session Management

```http
GET /auth.php?action=check_session
```

Returns current user session information.

## API Endpoints

### 📚 Authentication API (`auth.php`)

#### Login
```http
POST /auth.php?action=login
Content-Type: application/json

{
  "username": "admin",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "username": "admin",
      "name": "Administrator",
      "role": "admin"
    }
  }
}
```

#### Register
```http
POST /auth.php?action=register
Content-Type: application/json

{
  "username": "newuser",
  "name": "New User",
  "email": "user@example.com",
  "password": "password123",
  "role": "student"
}
```

#### Logout
```http
POST /auth.php?action=logout
```

### 👥 Users API (`users.php`)

#### Get Users List
```http
GET /users.php?action=list&page=1&limit=20&role=student&search=john
```

**Query Parameters:**
- `page` (int): Page number (default: 1)
- `limit` (int): Items per page (max: 50, default: 20)
- `role` (string): Filter by role (admin, staff, student)
- `status` (string): Filter by status (active, inactive)
- `search` (string): Search in name, username, email

**Response:**
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": 1,
        "username": "john_doe",
        "name": "John Doe",
        "email": "john@example.com",
        "role": "student",
        "student_id": "2024001",
        "created_ago": "2 days ago"
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 5,
      "total_items": 100,
      "items_per_page": 20
    }
  }
}
```

#### Get User Profile
```http
GET /users.php?action=profile&id=1
```

#### Create User (Admin only)
```http
POST /users.php?action=create
Content-Type: application/json

{
  "username": "newstudent",
  "name": "New Student",
  "email": "student@example.com",
  "password": "password123",
  "role": "student",
  "student_id": "2024002",
  "program_id": "CS"
}
```

#### Update User
```http
PUT /users.php?action=update&id=1
Content-Type: application/json

{
  "name": "Updated Name",
  "email": "newemail@example.com",
  "phone": "+1234567890"
}
```

#### Change Password
```http
POST /users.php?action=change-password&id=1
Content-Type: application/json

{
  "current_password": "oldpassword",
  "new_password": "newpassword123"
}
```

#### Search Users
```http
GET /users.php?action=search&q=john&limit=10
```

#### Get User Statistics (Admin only)
```http
GET /users.php?action=stats
```

### 📰 News API (`news.php`)

#### Get News List
```http
GET /news.php?action=list&page=1&limit=10&category=academic&search=graduation
```

**Query Parameters:**
- `page`, `limit`: Pagination
- `category` (string): Filter by category
- `status` (string): Filter by status (published, draft)
- `search` (string): Search in title, content, summary

#### Get News Detail
```http
GET /news.php?action=detail&id=1
```

#### Get Featured News
```http
GET /news.php?action=featured&limit=5
```

#### Get Recent News
```http
GET /news.php?action=recent&limit=10
```

#### Create News (Staff/Admin only)
```http
POST /news.php?action=create
Content-Type: application/json

{
  "title": "Important Campus Update",
  "summary": "Brief summary of the news",
  "content": "Full content of the news article...",
  "category": "academic",
  "status": "published",
  "is_featured": true,
  "image_url": "/uploads/news_image.jpg"
}
```

#### Update News (Staff/Admin only)
```http
PUT /news.php?action=update&id=1
Content-Type: application/json

{
  "title": "Updated Title",
  "content": "Updated content..."
}
```

#### Delete News (Admin only)
```http
DELETE /news.php?action=delete&id=1
```

#### Upload News Image
```http
POST /news.php?action=upload-image
Content-Type: multipart/form-data

image: [file]
```

#### Search News
```http
GET /news.php?action=search&q=graduation&page=1
```

#### Get News Categories
```http
GET /news.php?action=categories
```

### 📅 Events API (`events.php`)

#### Get Calendar Events
```http
GET /events.php?action=calendar&month=12&year=2024&limit=50
```

#### Get Upcoming Events
```http
GET /events.php?action=upcoming&limit=10&days=30
```

#### Get Event Detail
```http
GET /events.php?action=detail&id=1
```

#### Create Event (Staff/Admin only)
```http
POST /events.php?action=create
Content-Type: application/json

{
  "title": "Campus Open Day",
  "description": "Annual campus open day event...",
  "event_type": "academic",
  "start_date": "2024-12-15",
  "end_date": "2024-12-15",
  "start_time": "09:00:00",
  "end_time": "17:00:00",
  "location": "Main Campus",
  "max_participants": 100,
  "image_url": "/uploads/event_image.jpg"
}
```

#### Register for Event
```http
POST /events.php?action=register
Content-Type: application/json

{
  "event_id": 1
}
```

#### Unregister from Event
```http
DELETE /events.php?action=unregister&event_id=1
```

#### Get My Events
```http
GET /events.php?action=my-events
```

#### Search Events
```http
GET /events.php?action=search&q=open day&type=academic
```

### 🔍 Search API (`search.php`)

#### Global Search
```http
GET /search.php?action=global&q=campus&limit=20
```

**Response:**
```json
{
  "success": true,
  "data": {
    "query": "campus",
    "total_results": 15,
    "categories": {
      "news": {
        "name": "News",
        "count": 5,
        "items": [...]
      },
      "events": {
        "name": "Events", 
        "count": 8,
        "items": [...]
      },
      "users": {
        "name": "Users",
        "count": 2,
        "items": [...]
      }
    }
  }
}
```

#### Search Suggestions
```http
GET /search.php?action=suggestions&q=camp&limit=10
```

## Error Handling

All endpoints return standardized error responses:

```json
{
  "success": false,
  "message": "Error description",
  "timestamp": "2024-12-13 10:30:00"
}
```

### Common HTTP Status Codes

- `200 OK`: Success
- `201 Created`: Resource created successfully
- `400 Bad Request`: Invalid request data
- `401 Unauthorized`: Authentication required
- `403 Forbidden`: Insufficient permissions
- `404 Not Found`: Resource not found
- `405 Method Not Allowed`: HTTP method not supported
- `429 Too Many Requests`: Rate limit exceeded
- `500 Internal Server Error`: Server error

## Rate Limiting

API requests are limited to 200 requests per hour per IP address. Rate limit headers are included in responses:

```
X-RateLimit-Limit: 200
X-RateLimit-Remaining: 150
X-RateLimit-Reset: 1702464000
```

## File Uploads

### Supported File Types

- **Images**: jpg, jpeg, png, gif, webp (max 5MB)
- **Documents**: pdf, doc, docx, txt (max 10MB)

### Upload Response

```json
{
  "success": true,
  "data": {
    "filename": "unique_filename.jpg",
    "url": "uploads/unique_filename.jpg",
    "size": 1024000,
    "type": "image/jpeg"
  }
}
```

## Pagination

All list endpoints support pagination:

```json
{
  "pagination": {
    "current_page": 1,
    "total_pages": 10,
    "total_items": 200,
    "items_per_page": 20,
    "has_next": true,
    "has_prev": false
  }
}
```

## Search and Filtering

### Search Parameters

- `q` or `search`: Search query
- `page`: Page number
- `limit`: Results per page
- `sort`: Sort field
- `order`: Sort direction (asc, desc)

### Advanced Filtering

Many endpoints support additional filters:

```http
GET /news.php?action=list&category=academic&status=published&featured=true
GET /events.php?action=calendar&type=cultural&date_from=2024-12-01&date_to=2024-12-31
GET /users.php?action=list&role=student&program=CS&year=2024
```

## Response Caching

Some endpoints implement caching for better performance:

- News lists: 5 minutes
- Event calendars: 10 minutes
- User statistics: 1 hour

Cache can be bypassed with `?cache=false` parameter.

## WebHooks (Future Enhancement)

The API supports webhooks for real-time notifications:

- User registration
- Event creation/updates
- News publication
- System alerts

## SDK and Libraries

### JavaScript/Node.js

```javascript
const campusHub = new CampusHubAPI('http://localhost/BreyerApps/campus-hub/php/api/');

// Login
await campusHub.auth.login('username', 'password');

// Get news
const news = await campusHub.news.list({ page: 1, limit: 10 });

// Create event
const event = await campusHub.events.create({
  title: 'New Event',
  start_date: '2024-12-15',
  // ...
});
```

### PHP

```php
$api = new CampusHubClient('http://localhost/BreyerApps/campus-hub/php/api/');

// Login
$api->auth()->login('username', 'password');

// Get users
$users = $api->users()->list(['role' => 'student']);

// Create news
$news = $api->news()->create([
    'title' => 'Important Update',
    'content' => 'Content here...'
]);
```

## Testing

### Example cURL Commands

```bash
# Login
curl -X POST http://localhost/BreyerApps/campus-hub/php/api/auth.php?action=login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password"}'

# Get news
curl -X GET "http://localhost/BreyerApps/campus-hub/php/api/news.php?action=list&limit=5" \
  -H "Cookie: PHPSESSID=your_session_id"

# Create event
curl -X POST http://localhost/BreyerApps/campus-hub/php/api/events.php?action=create \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -d '{"title":"Test Event","start_date":"2024-12-15",...}'
```

## Support

For API support and questions:

- Email: support@campushub.edu
- Documentation: [API Docs](http://localhost/BreyerApps/campus-hub/docs/)
- GitHub: [Repository](https://github.com/campushub/api)

## Changelog

### Version 2.0.0 (2024-12-13)
- Enhanced authentication system
- Added comprehensive search functionality
- Improved error handling and validation
- Added rate limiting
- Enhanced file upload support
- Added caching system
- Complete API documentation

### Version 1.0.0 (2024-12-01)
- Initial API release
- Basic CRUD operations for users, news, events
- Session-based authentication
- File upload support
