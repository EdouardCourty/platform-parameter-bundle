# Platform Parameter Bundle

[![CI](https://github.com/EdouardCourty/platform-parameter-bundle/actions/workflows/ci.yml/badge.svg)](https://github.com/EdouardCourty/platform-parameter-bundle/actions/workflows/ci.yml)

A Symfony bundle for managing global platform parameters with type-safe access and caching.  
Supports PHP 8.3+, Symfony 7.x and 8.x.

## Table of Contents

- [Features](#features)
- [Installation](#installation)
  - [Optional: EasyAdmin Integration](#optional-easyadmin-integration)
  - [Register the bundle](#register-the-bundle)
  - [Configuration](#configuration)
  - [Database Setup](#database-setup)
- [Configuration Reference](#configuration-reference)
- [Usage](#usage)
  - [Basic Usage](#basic-usage)
  - [Available Methods](#available-methods)
  - [Cache Management](#cache-management)
  - [Parameter Types](#parameter-types)
  - [Updating Parameters](#updating-parameters)
  - [Creating Parameters](#creating-parameters)
  - [Error Handling](#error-handling)
  - [CLI Commands](#cli-commands)
  - [EasyAdmin CRUD Interface](#easyadmin-crud-interface)
  - [Twig Integration](#twig-integration)
- [Extending the Entity](#extending-the-entity)
  - [Step 1: Create Your Custom Entity](#step-1-create-your-custom-entity)
  - [Step 2: Configure the Bundle](#step-2-configure-the-bundle)
  - [Step 3: Update Database Schema](#step-3-update-database-schema)
  - [Step 4: (Optional) Create Custom EasyAdmin CRUD Controller](#step-4-optional-create-custom-easyadmin-crud-controller)
  - [Benefits of Extending](#benefits-of-extending)
- [Development](#development)
  - [Running Tests](#running-tests)
  - [Code Quality](#code-quality)
- [License](#license)

## Features

- 🎯 **Type-safe parameter access** - Methods for STRING, INTEGER, BOOLEAN, JSON, LIST, FLOAT, and DATETIME types
- ✍️ **Type-safe parameter updates** - Writer service for updating values from code with validation
- ⚡ **PSR-6 caching** - Built-in cache support for optimal performance
- 🔧 **Easy integration** - Simple Symfony bundle with simple configuration
- 📦 **Doctrine ORM** - Entity-based storage
- 🎪 **Event system** - React to parameter changes with Symfony events
- 🛠️ **CLI commands** - Manage parameters from the command line
- 🧩 **EasyAdmin support** - Optional CRUD controller for EasyAdmin integration
- 🌿 **Twig integration** - Optional Twig functions for direct use in templates
- 🧱 **Extensible entity** - Create custom entities with additional fields

## Installation

```bash
composer require ecourty/platform-parameter-bundle
```

### Optional: EasyAdmin Integration

If your project uses EasyAdmin, the bundle includes a ready-to-use CRUD controller:

```bash
composer require easycorp/easyadmin-bundle
```

### Register the bundle

If you're not using Symfony Flex, add the bundle to `config/bundles.php`:

```php
return [
    // ...
    Ecourty\PlatformParameterBundle\PlatformParameterBundle::class => ['all' => true],
];
```

**Important:** The bundle automatically configures Doctrine ORM mappings for the entity class specified in your configuration. **No manual Doctrine mapping configuration is required** - just configure `entity_class` in your bundle settings, and the mapping is handled automatically.

### Configuration

Create a configuration file `config/packages/platform_parameter.yaml`:

```yaml
platform_parameter:
    entity_class: 'Ecourty\PlatformParameterBundle\Entity\PlatformParameter'  # FQCN (default)
    cache_ttl: 3600   # Cache TTL in seconds (default: 3600)
    cache_key_prefix: 'platform_parameter'  # Cache key prefix (default: 'platform_parameter')
    clear_cache_on_parameter_update: true   # Auto-clear cache on parameter changes (default: true)
    cache_adapter: null  # Cache adapter service ID (default: auto-detect, see below)
```

#### Cache Adapter Resolution

The bundle automatically creates a **TagAware cache pool** (`platform_parameter.cache`) for optimal performance out-of-the-box.

**Cache adapter priority:**

1. **Custom adapter** (if `cache_adapter` is specified in config)
2. **Auto-created TagAware pool** (`platform_parameter.cache` - created by default)
3. **Default** (`cache.app` as ultimate fallback)

**No configuration needed!** The bundle works optimally by default with TagAware support.

**Example - Using a custom Redis cache pool:**

```yaml
# config/packages/cache.yaml
framework:
    cache:
        pools:
            my_custom_cache:
                adapter: cache.adapter.redis
                default_lifetime: 7200

# config/packages/platform_parameter.yaml
platform_parameter:
    cache_adapter: 'my_custom_cache'
```

**Example - Overriding the default pool:**

```yaml
# config/packages/cache.yaml
framework:
    cache:
        pools:
            platform_parameter.cache:
                adapter: cache.adapter.redis.tag_aware
                default_lifetime: 3600

# The bundle will automatically use this pool instead of creating its own
```

### Database Setup

Run Doctrine migrations to create the `platform_parameter` table:

```bash
# Create a migration
php bin/console doctrine:migrations:diff

# Apply the migration
php bin/console doctrine:migrations:migrate
```

**Note:** This works seamlessly with custom entities extending `AbstractPlatformParameter`. Doctrine will automatically detect all fields (base + custom) when generating migrations.

## Configuration Reference

All available configuration options with their default values:

```yaml
platform_parameter:
    # Entity class to use for storing parameters
    # Must extend Ecourty\PlatformParameterBundle\Model\AbstractPlatformParameter
    entity_class: 'Ecourty\PlatformParameterBundle\Model\PlatformParameter'

    # Cache time-to-live in seconds
    # How long parameters are cached before needing to be refreshed
    cache_ttl: 3600

    # Prefix for cache keys
    # All cached parameters will use this prefix (e.g., "platform_parameter.my_key")
    cache_key_prefix: 'platform_parameter'

    # Automatically clear cache when parameters are created/updated/deleted
    # Uses a Doctrine listener to automatically invalidate cache on entity changes
    clear_cache_on_parameter_update: true

    # Custom cache adapter service ID (optional)
    # If not specified, uses 'platform_parameter.cache' (auto-created) or 'cache.app' as fallback
    cache_adapter: null
```

### Configuration Options Details

| Option                            | Type           | Default                                                    | Description                                                                                                                                                          |
|-----------------------------------|----------------|------------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `entity_class`                    | `string`       | `Ecourty\PlatformParameterBundle\Entity\PlatformParameter` | FQCN of the entity class. Must extend `AbstractPlatformParameter`. Allows you to use a custom entity with additional fields.                                         |
| `cache_ttl`                       | `int`          | `3600`                                                     | Cache TTL in seconds. Controls how long parameters are cached. Set to `0` to disable TTL (cache until manually cleared).                                             |
| `cache_key_prefix`                | `string`       | `platform_parameter`                                       | Prefix for all cache keys. Useful if you have multiple parameter systems or want to avoid key collisions.                                                            |
| `clear_cache_on_parameter_update` | `bool`         | `true`                                                     | When enabled, automatically clears parameter cache on entity changes (create/update/delete) via a Doctrine listener. Disable if you prefer manual cache management.  |
| `cache_adapter`                   | `string\|null` | `null`                                                     | Service ID of a custom cache adapter. If `null`, the bundle automatically uses `platform_parameter.cache` (auto-created TagAware pool) or falls back to `cache.app`. |

## Usage

### Basic Usage

Inject the `PlatformParameterProviderInterface` into your services:

```php
use Ecourty\PlatformParameterBundle\Contract\PlatformParameterProviderInterface;

class MyService
{
    public function __construct(
        private readonly PlatformParameterProviderInterface $parameterProvider
    ) {
    }

    public function doSomething(): void
    {
        // Get a string parameter
        $siteName = $this->parameterProvider->getString('site_name');

        // Get an integer with a default value
        $maxUploads = $this->parameterProvider->getInt('max_uploads', 10);

        // Get a boolean
        $maintenanceMode = $this->parameterProvider->getBool('maintenance_mode', false);

        // Get a JSON array
        $config = $this->parameterProvider->getJson('api_config', ['timeout' => 30]);

        // Get a list (multi-line string split by newlines)
        $allowedEmails = $this->parameterProvider->getList('allowed_emails', []);

        // Get a list with custom separator (e.g., comma-separated)
        $tags = $this->parameterProvider->getList('tags', [], ',');
    }
}
```

### Available Methods

```php
// Get a string parameter
getString(string $key, ?string $default = null): string

// Get an integer parameter
getInt(string $key, ?int $default = null): int

// Get a boolean parameter
getBool(string $key, ?bool $default = null): bool

// Get a JSON parameter (returns array)
getJson(string $key, ?array $default = null): array

// Get a list parameter (returns string[])
getList(string $key, ?array $default = null, string $separator = "\n"): array

// Get a float parameter
getFloat(string $key, ?float $default = null): float

// Get a datetime parameter (returns DateTimeImmutable)
getDateTime(string $key, ?\DateTimeImmutable $default = null, ?string $format = null): \DateTimeImmutable

// Check if a parameter exists
has(string $key): bool

// Clear cached parameters
clearCache(?string $key = null): void  // Clear specific key or all parameters
```

#### DateTime Format Support

The `getDateTime()` method automatically tries these formats:
- ISO 8601: `2026-01-21T15:30:00+00:00`
- MySQL datetime: `2026-01-21 15:30:00`
- Date only: `2026-01-21`
- French format: `21/01/2026` or `21/01/2026 15:30:00`
- Unix timestamp: `1737472200`
- Native parser: `next monday`, `+1 week`, etc.

Or specify a custom format:
```php
$date = $parameterProvider->getDateTime('custom_date', null, 'd-m-Y');
```

### Cache Management

**The bundle automatically caches all parameters** to minimize database queries. When you access a parameter (e.g., `getString('site_name')`), it's fetched from the database once and cached for subsequent requests. This significantly reduces database load, especially for frequently accessed parameters. The cache TTL is configurable (default: 3600 seconds / 1 hour).

#### Automatic Cache Clearing (Default Behavior)

By default, the bundle **automatically clears the cache** when you create, update, or delete a parameter using a Doctrine entity listener:

```php
$parameter = new PlatformParameter();
$parameter->setKey('site_name');
$parameter->setValue('My Site');
$parameter->setType(ParameterType::STRING);

$entityManager->persist($parameter);
$entityManager->flush(); // ✅ Cache is automatically cleared for 'site_name'

// No need to call $parameterProvider->clearCache('site_name')!
```

This is enabled by default via the `clear_cache_on_parameter_update: true` configuration option. The bundle uses a Doctrine listener that automatically wipes the cache for the parameter whenever the entity is persisted, updated, or removed.

### Reacting to Parameter Changes with Events

The bundle dispatches **Symfony events** whenever parameters are created, updated, or deleted. This provides a simple way to react to parameter changes without configuring Doctrine listeners.

**Available Events:**

```php
use Ecourty\PlatformParameterBundle\Event\PlatformParameterCreatedEvent;
use Ecourty\PlatformParameterBundle\Event\PlatformParameterUpdatedEvent;
use Ecourty\PlatformParameterBundle\Event\PlatformParameterDeletedEvent;
```

**Example: Send notifications when parameters change**

```php
// src/EventSubscriber/ParameterChangeSubscriber.php
namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Ecourty\PlatformParameterBundle\Event\PlatformParameterUpdatedEvent;

class ParameterChangeSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private NotificationService $notifier,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PlatformParameterUpdatedEvent::class => 'onParameterUpdated',
        ];
    }

    public function onParameterUpdated(PlatformParameterUpdatedEvent $event): void
    {
        $parameter = $event->getParameter();
        
        // Send notification for critical parameters
        if ($parameter->getKey() === 'maintenance_mode') {
            $this->notifier->send(sprintf(
                'Maintenance mode changed from "%s" to "%s"',
                $event->getOldValue(),
                $event->getNewValue()
            ));
        }
    }
}
```

**Event Details:**

- **PlatformParameterCreatedEvent**: Contains the newly created parameter entity
  - `getParameter(): AbstractPlatformParameter`

- **PlatformParameterUpdatedEvent**: Contains the parameter entity, old value, and new value
  - `getParameter(): AbstractPlatformParameter`
  - `getOldValue(): string` - The value before the update
  - `getNewValue(): string` - The value after the update

- **PlatformParameterDeletedEvent**: Contains the parameter entity (before deletion)
  - `getParameter(): AbstractPlatformParameter`

**Events are always dispatched**, regardless of the `clear_cache_on_parameter_update` configuration. They work with:
- Console commands (`platform-parameter:set`, `platform-parameter:delete`)
- Direct Doctrine operations (`$em->persist()`, `$em->flush()`, `$em->remove()`)
- EasyAdmin CRUD operations
- Any other method of modifying parameters (as long as Doctrine ORM is used)

**To disable automatic cache clearing (but keep event dispatching):**

```yaml
# config/packages/platform_parameter.yaml
platform_parameter:
    clear_cache_on_parameter_update: false  # Disable auto-clear, events still dispatched
```

**Note**: Disabling cache clearing means you'll need to manually call `$parameterProvider->clearCache()` or handle cache invalidation via event subscribers.

#### Manual Cache Clearing

You can still manually clear the cache when needed:

```php
// Clear specific parameter cache
$parameterProvider->clearCache('site_name');

// Clear all parameter caches (only parameters, not entire Symfony cache)
$parameterProvider->clearCache();
```

**Note**: When clearing all caches, the bundle:
1. If using `TagAwareAdapter`: Uses tags for efficient invalidation
2. Otherwise: Fetches all parameters from DB and deletes their cache items individually

#### Optimizing Cache Performance

The bundle automatically creates a **TagAware cache pool** for optimal performance. No additional configuration is needed!

**Benefits:**
- ✅ Efficient bulk cache invalidation via tags
- ✅ `clearCache()` without parameters is O(1) instead of O(n)
- ✅ Works out-of-the-box without manual configuration

**Advanced - Using Redis for better performance:**

```yaml
# config/packages/cache.yaml
framework:
    cache:
        pools:
            platform_parameter.cache:
                adapter: cache.adapter.redis.tag_aware
                default_lifetime: 3600
                provider: 'redis://localhost'
```

The bundle will automatically use your custom `platform_parameter.cache` pool if defined.

### Parameter Types

The bundle supports 7 parameter types:

| Type       | Description                  | Example                             |
|------------|------------------------------|-------------------------------------|
| `STRING`   | Simple text value            | `"Hello World"`                     |
| `INTEGER`  | Numeric integer              | `42`                                |
| `BOOLEAN`  | Boolean value                | `"true"`, `"1"`, `"yes"`            |
| `JSON`     | JSON object/array            | `{"key": "value"}`                  |
| `LIST`     | Multi-line or separated list | `"line1\nline2"` or `"a,b,c"`       |
| `FLOAT`    | Decimal number               | `19.99`, `-15.5`, `1.5e-3`          |
| `DATETIME` | Date/time value              | `2026-01-21`, `2026-01-21 15:30:00` |

### Updating Parameters

The bundle provides a **PlatformParameterWriter** service for updating parameter values from your code with type safety:

```php
use Ecourty\PlatformParameterBundle\Contract\PlatformParameterWriterInterface;

class MyService
{
    public function __construct(
        private readonly PlatformParameterWriterInterface $parameterWriter
    ) {
    }

    public function updateConfiguration(): void
    {
        // Update parameters with type-safe methods
        $this->parameterWriter->setString('site_name', 'New Site Name');
        $this->parameterWriter->setInt('max_uploads', 100);
        $this->parameterWriter->setBool('maintenance_mode', true);
        $this->parameterWriter->setJson('api_config', ['timeout' => 60]);
        $this->parameterWriter->setList('allowed_ips', ['192.168.1.1', '10.0.0.1']);
        $this->parameterWriter->setFloat('tax_rate', 19.5);
        $this->parameterWriter->setDateTime('last_sync', new \DateTimeImmutable());
        
        // Delete a parameter
        $this->parameterWriter->delete('old_parameter');
    }
}
```

**Writer Methods:**

```php
setString(string $key, string $value): void
setInt(string $key, int $value): void
setBool(string $key, bool $value): void
setJson(string $key, array $value): void
setList(string $key, array $value, string $separator = "\n"): void
setFloat(string $key, float $value): void
setDateTime(string $key, \DateTimeImmutable $value, ?string $format = null): void
delete(string $key): void
```

**Important Notes:**

- The Writer **only updates existing parameters** - it does not create new ones
- Type validation: throws `ParameterTypeMismatchException` if you try to set a value with the wrong type (e.g., `setInt()` on a STRING parameter)
- Throws `ParameterNotFoundException` if the parameter doesn't exist

**Why no auto-creation?** If you use a custom entity extending `AbstractPlatformParameter` with additional required fields (category, icon, etc.), the Writer doesn't know how to populate them. Parameter creation must be explicit.

### Creating Parameters

**Parameters must be created using Doctrine entities directly:**

```php
use Ecourty\PlatformParameterBundle\Entity\PlatformParameter;
use Ecourty\PlatformParameterBundle\Enum\ParameterType;
use Doctrine\ORM\EntityManagerInterface;

class ParameterSeeder
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function createParameter(): void
    {
        $parameter = new PlatformParameter();
        $parameter->setKey('site_name');
        $parameter->setValue('My Awesome Site');
        $parameter->setType(ParameterType::STRING);
        $parameter->setLabel('Site Name');
        $parameter->setDescription('The name of the website');

        $this->entityManager->persist($parameter);
        $this->entityManager->flush();
    }
}
```

**This is the only way to create parameters.** It ensures:
- All required fields are populated (key, value, type, label)
- Custom entity fields are properly initialized
- You have full control over parameter metadata

### Error Handling

If a parameter doesn't exist and no default value is provided, a `ParameterNotFoundException` is thrown:

```php
use Ecourty\PlatformParameterBundle\Exception\ParameterNotFoundException;

try {
    $value = $parameterProvider->getString('non_existent_key');
} catch (ParameterNotFoundException $e) {
    // Handle missing parameter
}

// Or use a default value to avoid exceptions
$value = $parameterProvider->getString('non_existent_key', 'default value');
```

### CLI Commands

The bundle provides convenient console commands to manage parameters:

#### List all parameters

```bash
php bin/console platform-parameter:list
```

Displays a table with all parameters showing their key, value (truncated if too long), type, and label.

#### View a specific parameter

```bash
php bin/console platform-parameter:get site_name
```

Shows all metadata for a parameter including ID, key, value, type, label, description, and timestamps.

#### Update a parameter value

```bash
# Update an existing parameter
php bin/console platform-parameter:set max_uploads 50
php bin/console platform-parameter:set site_name "New Site Name"
php bin/console platform-parameter:set maintenance_mode true
```

**Behavior:**
- Updates the value of an existing parameter
- Automatically detects parameter type and converts value accordingly
- Returns error if parameter doesn't exist

**Note:** This command **only updates existing parameters**. To create new parameters, use Doctrine entities directly (see "Creating Parameters" section).

#### Delete a parameter

```bash
# Delete with confirmation prompt
php bin/console platform-parameter:delete old_param

# Delete without confirmation
php bin/console platform-parameter:delete old_param --force
```

Asks for confirmation before deletion (unless `--force` is used).

#### Clear parameter cache

```bash
# Clear cache for a specific parameter
php bin/console platform-parameter:cache:clear site_name

# Clear cache for all parameters
php bin/console platform-parameter:cache:clear
```

Manually clears the cache for one or all parameters. Useful if automatic cache clearing is disabled.

### EasyAdmin CRUD Interface

If you have EasyAdmin installed, you can use the included CRUD controller to manage parameters.

Register the controller in your EasyAdmin dashboard:

```php
// src/Controller/Admin/DashboardController.php
use Ecourty\PlatformParameterBundle\Controller\PlatformParameterCrudController;
use Ecourty\PlatformParameterBundle\Entity\PlatformParameter;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;

public function configureMenuItems(): iterable
{
    yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
    // ...
    yield MenuItem::linkToCrud('Platform Parameters', 'fa fa-cog', PlatformParameter::class)
        ->setController(PlatformParameterCrudController::class);
}
```

The CRUD controller includes:
- ✅ All CRUD operations (Create, Read, Update, Delete)
- ✅ Field validation and help text
- ✅ Badge colors for parameter types
- ✅ Truncated value display in list view

**Optional - Customizing the Controller:**

If you need to customize the controller behavior (e.g., change titles, add custom fields, modify permissions), you can extend it:

```php
// src/Controller/Admin/CustomPlatformParameterCrudController.php
namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Ecourty\PlatformParameterBundle\Controller\PlatformParameterCrudController;

class CustomPlatformParameterCrudController extends PlatformParameterCrudController
{
    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setPageTitle(Crud::PAGE_INDEX, 'My Custom Parameters');
    }
}
```

Then reference your custom controller in the dashboard instead:

```php
yield MenuItem::linkToCrud('Platform Parameters', 'fa fa-cog', PlatformParameter::class)
    ->setController(CustomPlatformParameterCrudController::class);
```

### Twig Integration

If Twig is installed in your project, the bundle automatically registers a Twig extension providing functions to read parameters directly in templates. No additional configuration is needed.

**Available Twig functions:**

| Function                                            | Return type               |
|-----------------------------------------------------|---------------------------|
| `platform_parameter_string(key, default)`           | `string\|null`            |
| `platform_parameter_int(key, default)`              | `int\|null`               |
| `platform_parameter_bool(key, default)`             | `bool\|null`              |
| `platform_parameter_float(key, default)`            | `float\|null`             |
| `platform_parameter_json(key, default)`             | `array\|null`             |
| `platform_parameter_list(key, default, separator)`  | `array\|null`             |
| `platform_parameter_datetime(key, default, format)` | `DateTimeImmutable\|null` |

All functions return `null` (instead of throwing an exception) when the parameter is not found and no default is provided.

**Examples:**

```twig
{# Display a string parameter #}
<h1>{{ platform_parameter_string('site_name', 'My App') }}</h1>

{# Conditional rendering based on a boolean flag #}
{% if platform_parameter_bool('maintenance_mode', false) %}
    <div class="alert">The site is under maintenance.</div>
{% endif %}

{# Iterate over a list parameter #}
<ul>
{% for email in platform_parameter_list('beta_testers_emails', []) %}
    <li>{{ email }}</li>
{% endfor %}
</ul>

{# Display a formatted datetime #}
<p>Last sync: {{ platform_parameter_datetime('last_sync')|date('Y-m-d H:i') }}</p>
```

## Extending the Entity

The bundle is designed to be extensible. You can create your own entity that extends the base `AbstractPlatformParameter` to add custom fields.

### Step 1: Create Your Custom Entity

```php
// src/Entity/CustomPlatformParameter.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ecourty\PlatformParameterBundle\Model\AbstractPlatformParameter;

#[ORM\Entity]
#[ORM\Table(name: 'platform_parameter')]
class CustomPlatformParameter extends AbstractPlatformParameter
{
    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $sortOrder = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $icon = null;

    // Getters and setters...

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(?int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }
}
```

### Step 2: Configure the Bundle

Update your `platform_parameter.yaml` configuration:

```yaml
platform_parameter:
    entity_class: 'App\Entity\CustomPlatformParameter'
    cache_ttl: 3600
    cache_key_prefix: 'platform_parameter'
```

### Step 3: Update Database Schema

```bash
# Generate migration for your custom fields
php bin/console doctrine:migrations:diff

# Run migration
php bin/console doctrine:migrations:migrate
```

### Step 4: (Optional) Create Custom EasyAdmin CRUD Controller

If you added custom fields and want them in EasyAdmin:

```php
// src/Controller/Admin/CustomParameterCrudController.php
namespace App\Controller\Admin;

use App\Entity\CustomPlatformParameter;
use Ecourty\PlatformParameterBundle\Controller\PlatformParameterCrudController as BaseCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CustomParameterCrudController extends BaseCrudController
{
    public static function getEntityFqcn(): string
    {
        return CustomPlatformParameter::class;
    }

    public function configureFields(string $pageName): iterable
    {
        // Get base fields from parent
        yield from parent::configureFields($pageName);

        // Add your custom fields
        yield TextField::new('category', 'Category')
            ->setHelp('Parameter category for organization');

        yield IntegerField::new('sortOrder', 'Sort Order')
            ->setHelp('Display order (lower numbers first)');

        yield TextField::new('icon', 'Icon')
            ->setHelp('Icon name (e.g., "fa-cog", "bi-gear")');
    }
}
```

### Benefits of Extending

- ✅ **Add custom metadata**: category, tags, sort order, icon, etc.
- ✅ **Keep core functionality**: All bundle methods work with your custom entity
- ✅ **Type-safe access**: The provider continues to work seamlessly
- ✅ **No code duplication**: Inherit all base fields and methods
- ✅ **EasyAdmin compatible**: Custom fields integrate naturally

## Development

### Running Tests

```bash
composer test
```

### Code Quality

```bash
# Run PHPStan
composer phpstan

# Fix code style
composer cs-fix

# Check code style
composer cs-check

# Run all QA checks
composer qa
```

## License

MIT License - see [LICENSE](LICENSE) file for details.
