# KSF Amortization API Infrastructure

## Overview

The KSF Amortization API is built on a modern PHP request routing and handling system. It provides:

- **Automatic Request Routing**: Maps HTTP methods and paths to controller methods
- **Authentication & Authorization**: Middleware for auth and admin checks
- **Base Controller**: Common functionality for all controllers
- **Request Validation**: Type checking, required fields, email/URL validation
- **Standardized Responses**: Consistent JSON response formatting
- **Error Handling**: Centralized exception handling

## Architecture

```
┌─────────────────────────────────────────┐
│      HTTP Request (Browser/Client)      │
└──────────────────┬──────────────────────┘
                   │
         ┌─────────▼─────────┐
         │   index.php       │ Entry point
         │  (Router loader)  │
         └────────┬──────────┘
                  │
    ┌─────────────▼──────────────┐
    │   Application Bootstrap    │
    │ (application initialization)│
    └─────────────┬──────────────┘
                  │
         ┌────────▼────────┐
         │  Router Class   │
         │ (Route matching)│
         └────────┬────────┘
                  │
         ┌────────▼────────────┐
         │  BaseController      │
         │  (Request handling)  │
         └────────┬────────────┘
                  │
    ┌─────────────▼────────────────┐
    │  Specific Controller Method   │
    │  (Business logic)             │
    └─────────────┬────────────────┘
                  │
         ┌────────▼──────────┐
         │  JSON Response    │
         │  (Browser/Client) │
         └───────────────────┘
```

## File Structure

```
src/Ksfraser/Api/
├── index.php                    # API entry point
├── routes.php                   # Route definitions (40+ endpoints)
├── Router.php                   # HTTP request router
├── Bootstrap.php                # Application initialization
│
└── Controllers/
    ├── BaseController.php       # Base class for all controllers
    ├── AuthController.php       # Authentication endpoints
    ├── UserController.php       # User management endpoints
    ├── ClientController.php     # Client management endpoints
    ├── MetricsController.php    # Metrics endpoints
    ├── HealthController.php     # Health check endpoints
    ├── SettingsController.php   # Settings endpoints
    └── ScopeController.php      # OAuth scope management

tests/Unit/
├── RouterTest.php               # Router unit tests
└── BaseControllerTest.php       # BaseController unit tests
```

## Core Classes

### Router Class

The Router handles HTTP request routing.

**Features:**
- HTTP method matching (GET, POST, PUT, DELETE, etc.)
- Path matching with dynamic parameters
- Query parameter parsing
- Request body parsing (JSON, form data)
- Middleware validation (auth, admin)
- Exception handling

**Example Usage:**
```php
$routes = include 'routes.php';
$router = new Router($routes);
$router->route();  // Routes the current HTTP request
```

### BaseController Class

Base class for all API controllers.

**Features:**
- Authentication management
- Authorization checking (admin role)
- Request validation (required fields, types, email, URL)
- Standardized response formatting
- Error handling
- Request data extraction
- Logging

**Example Controller:**
```php
namespace Ksfraser\Api\Controllers;

class UserController extends BaseController
{
    /**
     * Get user profile
     *
     * GET /users/:id
     */
    public function show(array $data, array $request)
    {
        $this->requireAuth();
        
        $userId = $data['id'] ?? null;
        
        if (!$userId) {
            return $this->error('User ID is required', 400);
        }
        
        // Fetch user from database
        $user = $this->getUserFromDB($userId);
        
        if (!$user) {
            return $this->error('User not found', 404);
        }
        
        return $this->success($user);
    }
    
    /**
     * Create new user
     *
     * POST /users
     */
    public function store(array $data, array $request)
    {
        $this->requireAdmin();
        
        // Validate required fields
        $this->validateRequired($data, ['email', 'password', 'name']);
        
        // Validate email
        if (!$this->isValidEmail($data['email'])) {
            return $this->error('Invalid email format', 400);
        }
        
        // Create user in database
        $user = $this->createUserInDB($data);
        
        return $this->success($user, 201);
    }
}
```

### Application Class

Initializes and runs the API application.

**Features:**
- Environment setup
- Route loading
- CORS configuration
- Exception handling
- Debug mode support

**Example Bootstrap:**
```php
$app = new Application([
    'debug' => false,
    'base_path' => '/modules/amortization/api',
    'timezone' => 'UTC',
]);

$app->run();
```

## Route Definition

Routes are defined in `routes.php` with the following structure:

```php
return [
    [
        'method' => 'GET',
        'path' => '/users/:id',
        'handler' => 'UserController::show',
        'middleware' => ['auth'],
        'description' => 'Get user profile',
    ],
    // ... more routes
];
```

**Route Properties:**
- `method`: HTTP method (GET, POST, PUT, DELETE, PATCH, etc.)
- `path`: Route path with optional parameters (`:paramName`)
- `handler`: Controller class and method (`Class::method`)
- `middleware`: Array of middleware requirements (`['auth']`, `['auth', 'admin']`)
- `description`: Human-readable description

## Request Lifecycle

### 1. Request Arrives

```
GET /users/123
Authorization: Bearer token123
```

### 2. Router Parses Request

```php
$request = [
    'method' => 'GET',
    'path' => '/users/123',
    'query' => [],
    'body' => [],
    'headers' => ['Authorization' => 'Bearer token123'],
];
```

### 3. Router Matches Route

Routes `/users/:id` matches `/users/123`

### 4. Middleware Validation

Checks `['auth']` middleware:
- Validates authentication
- Throws exception if not authenticated

### 5. Route Execution

Extracts parameters: `['id' => '123']`

Calls: `UserController::show(['id' => '123', ...], $request)`

### 6. Controller Response

```php
public function show(array $data, array $request)
{
    $userId = $data['id'];  // '123'
    return $this->success(['id' => 123, 'name' => 'John']);
}
```

### 7. JSON Response

```json
{
    "success": true,
    "data": {
        "id": 123,
        "name": "John"
    },
    "status": 200
}
```

## Dynamic Route Parameters

Routes can include dynamic parameters using `:paramName` syntax:

```php
// Single parameter
[
    'method' => 'GET',
    'path' => '/users/:id',
    'handler' => 'UserController::show',
]

// Multiple parameters
[
    'method' => 'GET',
    'path' => '/clients/:clientId/metrics/:metricId',
    'handler' => 'MetricsController::show',
]
```

Parameters are extracted and passed to the controller method.

## Middleware

### Built-in Middleware

1. **auth**: Requires authentication
   - Checks session user_id OR Bearer token
   - Returns 401 if not authenticated

2. **admin**: Requires admin role
   - Requires 'auth' middleware first
   - Checks user_role === 'admin'
   - Returns 403 if not admin

### Using Middleware

```php
[
    'method' => 'POST',
    'path' => '/users',
    'handler' => 'UserController::store',
    'middleware' => ['auth', 'admin'],  // Requires both auth AND admin
]
```

### Checking Middleware in Controller

```php
public function store(array $data, array $request)
{
    $this->requireAuth();   // Throws if not authenticated
    $this->requireAdmin();  // Throws if not admin
    
    // ... rest of method
}
```

## Response Formats

### Success Response

```json
{
    "success": true,
    "data": { ... },
    "status": 200
}
```

**With Pagination:**
```json
{
    "success": true,
    "data": [ ... ],
    "pagination": {
        "page": 1,
        "pageSize": 20,
        "total": 100,
        "pageCount": 5
    },
    "status": 200
}
```

### Error Response

```json
{
    "error": true,
    "message": "User not found",
    "status": 404
}
```

## Validation Methods

### Required Fields

```php
$this->validateRequired($data, ['email', 'password']);
// Throws exception if any field is missing
```

### Type Validation

```php
$this->validateTypes($data, [
    'count' => 'int',
    'name' => 'string',
    'active' => 'bool',
]);
// Throws exception if types don't match
```

### Email Validation

```php
if (!$this->isValidEmail($data['email'])) {
    return $this->error('Invalid email', 400);
}
```

### URL Validation

```php
if (!$this->isValidUrl($data['redirect_uri'])) {
    return $this->error('Invalid URL', 400);
}
```

## Creating a New Controller

1. **Create Controller Class** in `src/Ksfraser/Api/Controllers/`:

```php
namespace Ksfraser\Api\Controllers;

class ItemController extends BaseController
{
    public function index(array $data, array $request)
    {
        // List items
        $items = $this->getItems();
        return $this->success($items);
    }
    
    public function show(array $data, array $request)
    {
        // Get single item
        $itemId = $data['id'] ?? null;
        $item = $this->getItem($itemId);
        
        if (!$item) {
            return $this->error('Item not found', 404);
        }
        
        return $this->success($item);
    }
    
    public function store(array $data, array $request)
    {
        // Create item
        $this->requireAdmin();
        $this->validateRequired($data, ['name']);
        
        $item = $this->createItem($data);
        return $this->success($item, 201);
    }
    
    public function update(array $data, array $request)
    {
        // Update item
        $this->requireAdmin();
        $itemId = $data['id'] ?? null;
        
        $item = $this->updateItem($itemId, $data);
        return $this->success($item);
    }
    
    public function destroy(array $data, array $request)
    {
        // Delete item
        $this->requireAdmin();
        $itemId = $data['id'] ?? null;
        
        $this->deleteItem($itemId);
        return $this->success(null, 204);
    }
}
```

2. **Add Routes** in `routes.php`:

```php
return [
    // ... existing routes ...
    
    [
        'method' => 'GET',
        'path' => '/items',
        'handler' => 'ItemController::index',
        'middleware' => ['auth'],
    ],
    [
        'method' => 'GET',
        'path' => '/items/:id',
        'handler' => 'ItemController::show',
        'middleware' => ['auth'],
    ],
    [
        'method' => 'POST',
        'path' => '/items',
        'handler' => 'ItemController::store',
        'middleware' => ['auth', 'admin'],
    ],
    // ... more routes ...
];
```

## Testing

Run all tests:
```bash
npm run test
```

Run API tests only:
```bash
npm run test tests/Unit/RouterTest.php tests/Unit/BaseControllerTest.php
```

## Error Handling

### Router Errors

- **Path not found**: 404 Not Found
- **Middleware validation failed**: 403 Forbidden or 401 Unauthorized
- **Controller not found**: 500 Internal Server Error
- **Unhandled exception**: 500 Internal Server Error

### Controller Errors

Controllers can throw exceptions which are caught and converted to JSON responses:

```php
throw new Exception('Invalid input', 400);
```

Results in:
```json
{
    "error": true,
    "message": "Invalid input",
    "status": 400
}
```

## CORS Configuration

CORS headers are automatically set:
- `Access-Control-Allow-Origin: *`
- `Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS`
- `Access-Control-Allow-Headers: Content-Type, Authorization`

Modify in `Application::setupCors()` for production.

## Debug Mode

Enable debug mode in `index.php`:

```php
define('DEBUG', true);
```

This will:
- Display full exception messages
- Include stack traces in error responses
- Enable error reporting
- Log detailed error information

## Security Considerations

1. **Authentication**: Implement token validation in `BaseController::validateToken()`
2. **CORS**: Whitelist allowed origins in production
3. **Rate Limiting**: Implement in middleware or Apache/Nginx
4. **Input Validation**: Always validate user input in controllers
5. **SQL Injection**: Use prepared statements (parameterized queries)
6. **CSRF**: Validate CSRF tokens if needed
7. **HTTPS**: Always use HTTPS in production

## Performance Tips

1. **Caching**: Implement caching for frequently accessed data
2. **Database Indexes**: Index commonly searched/filtered fields
3. **Pagination**: Always paginate large datasets
4. **Query Optimization**: Use SELECT only needed fields
5. **Middleware Checks**: Cache authentication status when possible

## API Endpoints

See [routes.php](routes.php) for complete documentation of all 40+ endpoints:

- **Auth** (6): login, authorize, token management, logout
- **User** (12): profile, password, 2FA, tokens, consents, sessions
- **Admin/Clients** (8): CRUD operations, secret management
- **Metrics** (8): overview, requests, errors, response time, export
- **Health** (2): health check, status
- **Settings** (2): get, update
- **Scopes** (4): CRUD for OAuth scopes

## References

- [routes.php](routes.php) - Route definitions
- [Router.php](Router.php) - HTTP routing logic
- [BaseController.php](Controllers/BaseController.php) - Base controller functionality
- [Bootstrap.php](Bootstrap.php) - Application bootstrapping
- [tests](../../tests/Unit) - Unit tests

## Contributing

When adding new endpoints:

1. Add route to `routes.php`
2. Create controller method in appropriate controller
3. Add validation and error handling
4. Add unit tests
5. Update API documentation
6. Test with provided tools (curl, Postman, etc.)

## License

MIT License - See LICENSE file for details
