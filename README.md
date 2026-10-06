# Slim 4 Starter Skeleton

A modern, robust, easily expandable, and highly testable starter application for PHP 8+, built with:
- **[Slim 4](https://www.slimframework.com/)**: Fast, lightweight PSR-7 / PSR-15 micro-framework.
- **[PHP-DI 7](https://php-di.org/)**: Powerful Dependency Injection container with autowiring.
- **[Doctrine ORM 3](https://www.doctrine-project.org/)**: Entity management with modern PHP 8 Attributes.
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
│   ├── Container.php      # DI Container setup
│   ├── Dependencies.php   # Service definitions & factories
│   ├── Middleware.php     # Global middleware registration
│   ├── Routes.php         # Application routes definition
│   └── Settings.php       # Structured configuration loader
├── public/
│   └── index.php          # Web entry point
├── src/
│   ├── Action/            # Single-Action Controllers (Invokable)
│   │   ├── Api/
│   │   │   └── HealthCheckAction.php
│   │   ├── User/
│   │   │   ├── CreateUserAction.php
│   │   │   └── ListUsersAction.php
│   │   └── HomeAction.php
│   ├── Entity/            # Doctrine ORM Entities (PHP 8 Attributes)
│   │   └── User.php
│   └── Repository/        # Persistence & data query abstractions
│       └── UserRepository.php
├── templates/             # Twig views and layouts
│   ├── layout.html.twig
│   ├── home.html.twig
│   └── users/
│       └── index.html.twig
├── tests/                 # PHPUnit test suite
│   ├── TestCase.php       # Base test case with in-memory DB & PSR-7 test client
│   ├── Functional/        # End-to-end HTTP action tests
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

### 3. Database Schema Setup
Initialize the database schema using the CLI:
```bash
php bin/console orm:schema-tool:create
```

For incremental schema updates:
```bash
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

### Adding a New Route & Action
Create a single invokable Action class in `src/Action/`:
```php
namespace App\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class AboutAction
{
    public function __construct(private readonly Twig $twig) {}

    public function __invoke(Request $request, Response $response): Response
    {
        return $this->twig->render($response, 'about.html.twig');
    }
}
```
Register it in `config/Routes.php`:
```php
$app->get('/about', App\Action\AboutAction::class)->setName('about');
```

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
