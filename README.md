# Slim 4 Starter Skeleton

A modern, robust, easily expandable, and highly testable starter application for PHP 8+, built with:
- **[Slim 4](https://www.slimframework.com/)**: Fast, lightweight PSR-7 / PSR-15 micro-framework.
- **[PHP-DI 7](https://php-di.org/)**: Powerful Dependency Injection container with autowiring.
- **[Doctrine ORM 3](https://www.doctrine-project.org/)**: Entity management with modern PHP 8 Attributes.
- **[Doctrine Migrations 3](https://www.doctrine-project.org/projects/migrations.html)**: Database schema migration management.
- **[Twig 3](https://twig.symfony.com/)**: Flexible and secure templating engine via `slim/twig-view`.
- **[Monolog 3](https://github.com/Seldaek/monolog)**: PSR-3 logging.
- **[vlucas/phpdotenv](https://github.com/vlucas/phpdotenv)**: `.env` configuration management.
- **[Symfony Console](https://symfony.com/doc/current/components/console.html)**: CLI commands and Doctrine schema tool.
- **[PHPUnit 13](https://phpunit.de/)**: Unit and end-to-end HTTP integration tests with in-memory database support.

---

## 📁 Directory Structure

```text
├── bin/
│   └── console            # CLI Application (Doctrine commands & custom tasks)
├── config/
│   ├── Dependencies.php   # Service definitions & factories
│   ├── Middleware.php     # Global middleware registration
│   ├── Routes.php         # Application routes definition
│   └── Settings.php       # Structured configuration loader
├── migrations/            # Doctrine database migrations
├── public/
│   └── index.php          # Web entry point
├── src/
│   ├── Auth/              # Authentication contracts & session implementation
│   │   ├── AuthInterface.php
│   │   └── SessionAuth.php
│   ├── Controller/        # Single-Action Controllers (Invokable)
│   │   ├── Api/
│   │   │   └── HealthCheckController.php
│   │   ├── Auth/
│   │   │   ├── LoginController.php
│   │   │   ├── LogoutController.php
│   │   │   └── ShowLoginController.php
│   │   ├── User/
│   │   │   ├── CreateUserController.php
│   │   │   └── ListUsersController.php
│   │   ├── Controller.php # Abstract base controller (Twig injection & render helper)
│   │   └── HomeController.php
│   ├── Entity/            # Doctrine ORM Entities (PHP 8 Attributes)
│   │   ├── Traits/
│   │   │   └── TimestampableTrait.php # Reusable createdAt / updatedAt lifecycle callbacks
│   │   └── User.php
│   ├── Middleware/        # PSR-15 Middlewares (AuthMiddleware)
│   │   └── AuthMiddleware.php
│   └── Repository/        # Persistence & data query abstractions
│       └── UserRepository.php
├── templates/             # Twig views and layouts
│   ├── auth/
│   │   └── login.html.twig
│   ├── layout.html.twig
│   ├── home.html.twig
│   └── users/
│       └── index.html.twig
├── tests/                 # PHPUnit test suite
│   ├── TestCase.php       # Base test case with in-memory DB & PSR-7 test client
│   ├── Functional/        # End-to-end HTTP controller tests
│   └── Unit/              # Entity and repository unit tests
├── bootstrap.php          # Application bootstrap & factory functions
├── phpunit.xml            # PHPUnit test configuration
└── composer.json          # Package dependencies & autoloading
```

---

## 🚀 Getting Started

### 1. Installation
Install project dependencies:
```bash
composer install
```

### 2. Environment Configuration
Copy `.env.example` to `.env` (optional, default fallback settings are provided in `config/Settings.php`):
```bash
cp .env.example .env
```

### 3. Database Schema & Migrations Setup
Initialize or update the database using Doctrine Migrations:
```bash
# Check migrations status
php bin/console migrations:status

# Generate a migration based on entity mapping changes
php bin/console migrations:diff

# Run pending migrations
php bin/console migrations:migrate
```

Alternatively, direct schema tools are also available for quick prototyping:
```bash
# Create schema directly
php bin/console orm:schema-tool:create

# Update schema directly
php bin/console orm:schema-tool:update --force
```

### 4. Running the Built-in Server
Start the PHP built-in web server:
```bash
php -S localhost:8080 -t public
```
Visit `http://localhost:8080` in your browser.

---

## 🛠️ How to Extend

### Adding a New Route & Controller
Create an invokable controller in `src/Controller/`. Controllers can extend `App\Controller\Controller` to access the `$this->render()` helper, with `Twig` automatically injected via PHP-DI attributes:

```php
namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AboutController extends Controller
{
    public function __invoke(Request $request, Response $response): Response
    {
        return $this->render($response, 'about.html.twig', [
            'title' => 'About Us',
        ]);
    }
}
```

If your controller requires additional services or repositories, define them directly in the constructor without needing to pass `Twig` to a parent constructor:

```php
namespace App\Controller;

use App\Repository\UserRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class TeamController extends Controller
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $members = $this->userRepository->findAll();

        return $this->render($response, 'team.html.twig', [
            'members' => $members,
        ]);
    }
}
```

Register your controller route in `config/Routes.php`:

```php
$app->get('/about', App\Controller\AboutController::class)->setName('about');
```

### Protecting Routes with Authentication
To protect routes with authentication, apply `App\Middleware\AuthMiddleware`:

```php
$app->group('/admin', function (RouteCollectorProxy $group) {
    $group->get('/dashboard', App\Controller\Admin\DashboardController::class);
})->add(App\Middleware\AuthMiddleware::class);
```

When an unauthenticated request is received, `AuthMiddleware` automatically redirects browser requests to `/login` (302) or returns a `401 Unauthorized` JSON response for API requests.

The authenticated user and auth state are also automatically available:
- In Twig views via `{{ auth.check() }}` and `{{ auth.user().name }}`
- In request attributes via `$request->getAttribute('user')`
- Injected via `App\Auth\AuthInterface`

### Adding a Doctrine Entity
Create a new entity with PHP 8 attributes in `src/Entity/`:
```php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'posts')]
class Post
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title;

    public function __construct(string $title)
    {
        $this->title = $title;
    }
    
    // getters and setters...
}
```
Update your database schema:
```bash
php bin/console orm:schema-tool:update --force
```

---

## 🧪 Testing

The starter is designed to be **extremely testable**:
- Includes an isolated base `App\Tests\TestCase` class.
- Uses an in-memory SQLite database automatically during tests (`:memory:`).
- Provides helper methods for testing PSR-7 requests (`createRequest()`, `createJsonRequest()`, `createFormRequest()`, `handleRequest()`).
- Allows overriding container definitions per-test.

Run all tests with:
```bash
./vendor/bin/phpunit
```
