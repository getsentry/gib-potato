# GibPotato Laravel Routes Documentation

## Overview

This document provides a comprehensive overview of all routes in the GibPotato Laravel application. Routes are organized into three main categories: Web Routes, API Routes, and Service Routes.

## Route Categories

### 1. Web Routes (`/routes/web.php`)

These routes handle the web interface and authentication flow.

#### Authentication Routes (Guest Only)
| Method | URI | Name | Controller | Description |
|--------|-----|------|------------|-------------|
| GET | `/login` | `login` | `SlackLoginController@login` | Display login page |
| GET | `/login/mobile` | `login.mobile` | `SlackLoginController@mobile` | Mobile login page |
| GET | `/start-open-id/{workspace?}` | `slack.redirect` | `SlackLoginController@redirect` | Initiate Slack OAuth flow |
| GET | `/open-id/{workspace?}` | `slack.callback` | `SlackLoginController@callback` | Handle Slack OAuth callback |
| GET/POST | `/logout` | `logout` | `SlackLoginController@logout` | Log out user |

#### Public Routes
| Method | URI | Name | Controller | Description |
|--------|-----|------|------------|-------------|
| GET | `/terms` | `terms` | `TermsController@index` | Terms of service page |

#### Authenticated SPA Routes
All these routes serve the Vue.js SPA and require authentication:

| Method | URI | Name | Controller | Description |
|--------|-----|------|------------|-------------|
| GET | `/` | `home` | `HomeController@index` | Home/Dashboard |
| GET | `/shop` | `shop` | `HomeController@index` | Potato shop |
| GET | `/collection` | `collection` | `HomeController@index` | User's collection |
| GET | `/quick-wins` | `quick-wins` | `HomeController@index` | Quick wins/achievements |
| GET | `/profile` | `profile` | `HomeController@index` | User profile |
| GET | `/settings` | `settings` | `HomeController@index` | User settings |

### 2. API Routes (`/routes/api.php`)

All API routes:
- Are prefixed with `/api`
- Require authentication via Laravel Sanctum
- Return JSON responses
- Are consumed by the Vue.js frontend

#### Leaderboard
| Method | URI | Name | Controller | Description |
|--------|-----|------|------------|-------------|
| GET | `/api/leaderboard` | `api.leaderboard` | `LeaderBoardController@index` | Get potato leaderboard |

#### Users
| Method | URI | Name | Controller | Description |
|--------|-----|------|------------|-------------|
| GET | `/api/users` | `api.users.index` | `UsersController@index` | List all users |

#### Current User
| Method | URI | Name | Controller | Description |
|--------|-----|------|------------|-------------|
| GET | `/api/user` | `api.user.show` | `UsersController@show` | Get current user data |
| PATCH | `/api/user` | `api.user.update` | `UsersController@update` | Update user settings |
| GET | `/api/user/profile` | `api.user.profile` | `UsersController@profile` | Get detailed user profile |

#### Shop
| Method | URI | Name | Controller | Description |
|--------|-----|------|------------|-------------|
| GET | `/api/shop/products` | `api.shop.products` | `ShopController@products` | List available products |
| POST | `/api/shop/purchase` | `api.shop.purchase` | `ShopController@purchase` | Purchase a product |

#### Collection
| Method | URI | Name | Controller | Description |
|--------|-----|------|------------|-------------|
| GET | `/api/collection` | `api.collection` | `CollectionController@index` | Get user's collection |

#### Quick Wins
| Method | URI | Name | Controller | Description |
|--------|-----|------|------------|-------------|
| GET | `/api/quick-wins` | `api.quick-wins` | `QuickWinsController@index` | Get quick wins data |

### 3. Service Routes (`/routes/services.php`)

These routes handle integration with external services.

#### Slack Events (Potal Integration)
| Method | URI | Name | Controller | Middleware | Description |
|--------|-----|------|------------|------------|-------------|
| POST | `/events` | `services.events.handle` | `EventsController@handle` | `verify.potal` | Handle Slack events from potal |

#### Health Check
| Method | URI | Name | Controller | Description |
|--------|-----|------|------------|-------------|
| GET | `/health` | `services.health` | Closure | Service health check |

## Authentication & Middleware

### Web Authentication
- **Middleware**: `auth`
- **Method**: Session-based authentication with Slack OAuth
- **Login Flow**: `/login` → `/start-open-id` → Slack → `/open-id` → Authenticated

### API Authentication
- **Middleware**: `auth:sanctum`
- **Method**: Bearer token authentication
- **Headers Required**: 
  ```
  Authorization: Bearer {token}
  X-Requested-With: XMLHttpRequest
  Accept: application/json
  ```

### Service Authentication
- **Middleware**: `verify.potal`
- **Method**: Custom token verification for potal service
- **Headers Required**:
  ```
  Authorization: {POTAL_TOKEN}
  ```

## Rate Limiting

Different rate limits apply to different route groups:

- **API Routes**: 60 requests per minute per user
- **Purchase Endpoint**: 5 requests per minute per user
- **Events Endpoint**: 10 requests per second per IP

## CSRF Protection

- Web routes use Laravel's CSRF protection
- API routes are exempt but require proper authentication headers
- Service routes use custom authentication instead of CSRF

## Example API Requests

### Get Current User
```bash
curl -X GET https://gib-potato.sentry.io/api/user \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json" \
  -H "X-Requested-With: XMLHttpRequest"
```

### Purchase a Product
```bash
curl -X POST https://gib-potato.sentry.io/api/shop/purchase \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-Requested-With: XMLHttpRequest" \
  -d '{"product_id": "uuid-here", "presentee_id": "uuid-here", "message": "Enjoy!"}'
```

### Handle Slack Event (from potal)
```bash
curl -X POST https://gib-potato.sentry.io/events \
  -H "Authorization: {POTAL_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "message",
    "sender": "U123456",
    "receivers": ["U789012"],
    "amount": 1,
    "channel": "C123456",
    "text": "Great work! :potato:",
    "timestamp": "1234567890.123456"
  }'
```

## Route Model Binding

The application uses Laravel's route model binding for cleaner controller code:

- User models are automatically resolved by UUID
- Products are resolved by UUID
- API responses use Resource classes for consistent formatting

## Response Formats

### Success Response
```json
{
  "data": {
    // Resource data
  },
  "meta": {
    // Optional metadata
  }
}
```

### Error Response
```json
{
  "message": "Error message",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

### Paginated Response
```json
{
  "data": [...],
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 10,
    "per_page": 15,
    "to": 15,
    "total": 150
  }
}
```