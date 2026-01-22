# AGENTS.md - Platform Parameter Bundle

## 🎯 Core Concept

**Platform Parameter Bundle** is a Symfony bundle for managing **global platform parameters** stored in the database with type-safe access and caching system.

### Problem Solved

- Avoid hard-coded values in code
- Enable dynamic configuration without redeployment
- Centralize business parameters (e.g., limits, feature flags, external URLs)
- Provide a type-safe API to prevent type errors

### Solution

A simple and robust system: **Doctrine Entity + Service Provider + PSR-6 Cache**

---

## 🏗️ Architecture

### Overview

The architecture follows a layered approach:

**Application Layer** (Controllers, Services, Commands) interacts with the **PlatformParameterProvider** service through dependency injection. This provider implements the `PlatformParameterProviderInterface` and acts as the single entry point for parameter access.

The provider coordinates three underlying components:
- **PSR-6 Cache**: First line of defense for fast parameter retrieval
- **Doctrine ORM**: Database persistence layer for parameter storage
- **Validator Logic**: Type conversion and validation mechanisms

All parameters are stored in the `platform_parameter` database table and accessed through the ORM.

### Main Components

#### 1. **AbstractPlatformParameter** (Entity)
- **MappedSuperclass** allowing extension
- Fields: `id`, `key`, `value`, `type`, `label`, `description`, timestamps
- Auto-generated UUID
- Lifecycle callbacks for `updatedAt`

```php
// src/Entity/AbstractPlatformParameter.php
#[ORM\MappedSuperclass]
abstract class AbstractPlatformParameter
{
    private Uuid $id;
    private string $key;      // Unique identifier
    private string $value;    // Stored as TEXT
    private ParameterType $type;
    private string $label;
    private ?string $description;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;
}
```

**Design Choice**: `MappedSuperclass` allows projects to extend the entity to add custom fields (category, icon, sortOrder, etc.)

#### 2. **PlatformParameter** (Concrete Entity)
- Inherits from `AbstractPlatformParameter`
- Standard Doctrine entity used by default
- Table `platform_parameter`

```php
#[ORM\Entity]
#[ORM\Table(name: 'platform_parameter')]
class PlatformParameter extends AbstractPlatformParameter
{
    // Ready to use, no custom fields
}
```

#### 3. **ParameterType** (Enum)
7 supported types with built-in validation:

```php
enum ParameterType: string
{
    case STRING   = 'string';    // Simple text
    case INTEGER  = 'integer';   // Integer number
    case BOOLEAN  = 'boolean';   // true/false/1/0/yes/no
    case JSON     = 'json';      // JSON object/array
    case LIST     = 'list';      // Multi-line or delimiter-separated
    case FLOAT    = 'float';     // Decimal number
    case DATETIME = 'datetime';  // Date/time with multiple formats
}
```

#### 4. **PlatformParameterProviderInterface** (Contract)
Service interface exposing the public API:

```php
interface PlatformParameterProviderInterface
{
    public function getString(string $key, ?string $default = null): string;
    public function getInt(string $key, ?int $default = null): int;
    public function getBool(string $key, ?bool $default = null): bool;
    public function getJson(string $key, ?array $default = null): array;
    public function getList(string $key, ?array $default = null, string $separator = "\n"): array;
    public function getFloat(string $key, ?float $default = null): float;
    public function getDateTime(string $key, ?\DateTimeImmutable $default = null, ?string $format = null): \DateTimeImmutable;
    public function has(string $key): bool;
    public function clearCache(?string $key = null): void;
}
```

**Design Pattern**: Interface injection for testability and decoupling

#### 5. **PlatformParameterProvider** (Service)
Provider implementation with business logic:

**Responsibilities**:
- Fetch from cache or DB
- Type validation and conversion
- Error handling (exceptions or defaults)
- Smart cache invalidation

**Execution Flow**:

When a parameter is requested, the provider follows this sequence:
1. **Cache Check**: Look for the parameter in cache using the format `{prefix}.{key}`
2. **Cache HIT**: If found in cache, return the cached value immediately
3. **Cache MISS**: If not in cache, query the database using Doctrine EntityManager
4. **Database Result**: If found in DB, store it in cache and return the value
5. **Not Found**: If the parameter doesn't exist, either return the provided default value or throw a `ParameterNotFoundException`

#### 6. **Symfony Configuration**
```yaml
# config/packages/platform_parameter.yaml
platform_parameter:
    entity_class: 'Ecourty\PlatformParameterBundle\Entity\PlatformParameter'
    cache_ttl: 3600
    cache_key_prefix: 'platform_parameter'
```

---

## 🔄 Data Flow

### Reading a Parameter

When reading a parameter, for example `$provider->getInt('max_uploads', 10)`, the system follows these steps:

1. **Key Generation**: The provider generates a cache key by prefixing the parameter key (e.g., `'platform_parameter.max_uploads'`)
2. **Cache Lookup**: The PSR-6 cache is queried with this key
3. **Cache Hit Path**: If the item exists in cache, the cached `AbstractPlatformParameter` object is returned directly
4. **Cache Miss Path**: If not in cache, Doctrine's EntityManager performs a `findOneBy(['key' => 'max_uploads'])` query
5. **Database Hit**: If found in the database, the parameter is cached for future requests and the value is returned
6. **Database Miss**: If not found in the database, the default value (10) is returned, or a `ParameterNotFoundException` is thrown if no default was provided

### Writing/Updating

To create or update a parameter, you work directly with Doctrine entities:

```php
// In a service or controller
$parameter = new PlatformParameter();
$parameter->setKey('max_uploads');
$parameter->setValue('20');
$parameter->setType(ParameterType::INTEGER);

$em->persist($parameter);
$em->flush();

// ⚠️ IMPORTANT: Clear cache manually
$provider->clearCache('max_uploads');
```

**Important**: Manual cache invalidation is required after modifying parameters. The bundle doesn't automatically hook Doctrine events to keep the logic simple and give you explicit control over cache management.

---

## 💡 Design Patterns Used

### 1. **Provider Pattern**
The `PlatformParameterProvider` acts as a uniform facade for retrieving parameters.

### 2. **Type Safety**
Each method returns a strict PHP 8.3 type, preventing type errors.

### 3. **Fail-Safe with Defaults**
```php
$value = $provider->getInt('unknown_key', 100); // Returns 100 if not found
$value = $provider->getInt('unknown_key');      // Throws ParameterNotFoundException
```

### 4. **Cache-Aside (Lazy Loading)**
The cache is checked first, the DB is only consulted in case of MISS.

### 5. **Tagged Cache Support**
If `TagAwareCacheInterface` is available, the bundle uses tags for efficient invalidation.

```php
// Clear all parameters
$provider->clearCache();

// With TagAwareCache: 1 operation
→ invalidateTags(['platform_parameter'])

// Without TagAwareCache: N operations (fetch all + delete each)
→ findAll() → deleteItem() for each key
```

### 6. **Template Method (Extension Point)**
`AbstractPlatformParameter` allows inheritance for extension without modifying the core.

---

## 🚀 Typical Use Cases

### 1. Feature Flags
```php
if ($provider->getBool('feature_new_dashboard', false)) {
    return $this->render('dashboard_v2.html.twig');
}
```

### 2. Dynamic Limits
```php
$maxFileSize = $provider->getInt('max_upload_size_mb', 10) * 1024 * 1024;
if ($file->getSize() > $maxFileSize) {
    throw new UploadException('File too large');
}
```

### 3. External Configuration
```php
$apiConfig = $provider->getJson('stripe_config', [
    'public_key' => '',
    'secret_key' => '',
    'webhook_url' => '',
]);

$stripe = new StripeClient($apiConfig['secret_key']);
```

### 4. Dynamic Lists
```php
$allowedEmails = $provider->getList('beta_testers_emails', []);
if (!in_array($user->getEmail(), $allowedEmails)) {
    throw new AccessDeniedException();
}
```

### 5. Maintenance Mode
```php
// In an EventSubscriber
if ($provider->getBool('maintenance_mode', false)) {
    throw new ServiceUnavailableHttpException(3600, 'Under maintenance');
}
```

---

## 🧩 Extension: Custom Entity

### Why Extend?
To add custom metadata: category, sort order, icon, tags, etc.

### How?

**Step 1**: Create a custom entity
```php
namespace App\Entity;

#[ORM\Entity]
#[ORM\Table(name: 'platform_parameter')]
class CustomPlatformParameter extends AbstractPlatformParameter
{
    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $sortOrder = null;

    // Getters/setters...
}
```

**Step 2**: Configure the bundle
```yaml
platform_parameter:
    entity_class: 'App\Entity\CustomPlatformParameter'
```

**Step 3**: Migration
```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

✅ **The provider continues to work as is** - autowiring automatically uses your custom entity.

---

## 🎨 EasyAdmin Integration (Optional)

The bundle provides a **ready-to-use CRUD controller** if EasyAdmin is installed.

### Usage

```php
// src/Controller/Admin/PlatformParameterCrudController.php
namespace App\Controller\Admin;

use Ecourty\PlatformParameterBundle\Controller\PlatformParameterCrudController as BaseCrudController;

class PlatformParameterCrudController extends BaseCrudController
{
    // Ready to use!
}
```

### Included Features
- Complete CRUD with validation
- Colored badges for types
- Help text on each field
- Role-based permissions
- Truncated values in list view

---

## ⚡ Performance & Caching

### Cache Strategy

1. **Cache Key Format**: `{prefix}.{parameter_key}` (e.g., `platform_parameter.max_uploads`)
2. **Configurable TTL**: Default 3600s (1h)
3. **Per-parameter cache**: Each key is cached individually
4. **Granular invalidation**: Clear by key or globally

### Optimizations

#### Using TagAwareCache
```yaml
# config/packages/cache.yaml
framework:
    cache:
        pools:
            platform_parameter.cache:
                adapter: cache.adapter.redis.tag_aware
                default_lifetime: 3600
```

Benefits:
- Bulk invalidation in O(1) instead of O(n)
- Avoids fetching all parameters during `clearCache()`

#### Cache Warmup (Optional)
If you have many critical parameters:

```php
// Command or EventListener
foreach ($criticalKeys as $key) {
    try {
        $provider->getString($key);
    } catch (ParameterNotFoundException) {
        // Log missing parameter
    }
}
```

---

## 🛡️ Error Handling

### Exceptions

#### ParameterNotFoundException
Thrown when a parameter doesn't exist and no default is provided.

```php
try {
    $value = $provider->getString('missing_key');
} catch (ParameterNotFoundException $e) {
    // Log or handle
    $value = 'fallback';
}
```

#### InvalidArgumentException
Thrown during type conversion if the value is invalid.

```php
// DB value: "not_a_number"
$provider->getInt('bad_value'); // throws InvalidArgumentException
```

### Best Practices

1. **Always provide a default in production**
```php
// ❌ Risky
$limit = $provider->getInt('rate_limit');

// ✅ Safe
$limit = $provider->getInt('rate_limit', 100);
```

2. **Validate in dev, default in prod**
```php
if ($this->environment === 'dev' && !$provider->has('critical_param')) {
    throw new \RuntimeException('Missing critical parameter');
}
$value = $provider->getString('critical_param', 'default');
```

---

## 🧪 Testing

### Mocking the Provider

```php
use Ecourty\PlatformParameterBundle\Contract\PlatformParameterProviderInterface;

class MyServiceTest extends TestCase
{
    public function testWithMockProvider(): void
    {
        $provider = $this->createMock(PlatformParameterProviderInterface::class);
        $provider->method('getInt')
            ->willReturnMap([
                ['max_uploads', null, 20],
                ['timeout', null, 30],
            ]);

        $service = new MyService($provider);
        // Test service behavior
    }
}
```

### Functional Tests

```php
// Create test parameters
$parameter = new PlatformParameter();
$parameter->setKey('test_key');
$parameter->setValue('42');
$parameter->setType(ParameterType::INTEGER);

$this->entityManager->persist($parameter);
$this->entityManager->flush();

// Test provider
$result = $this->parameterProvider->getInt('test_key');
$this->assertSame(42, $result);
```

---

## 📋 Developer Checklist

### Adding a New Parameter

1. ✅ Create the entity in DB (manually or via EasyAdmin)
2. ✅ Define: key, value, type, label, description
3. ✅ Test access via provider
4. ✅ Document the parameter in the project (README, confluence, etc.)

### Modifying a Parameter

1. ✅ Update in DB via EasyAdmin or SQL
2. ✅ **Clear the cache**: `$provider->clearCache('key')` or via console
3. ✅ Verify that the new value is active

### Debugging an Issue

1. ❓ Does the parameter exist in DB? → `SELECT * FROM platform_parameter WHERE key = '...'`
2. ❓ Is the cache stale? → `$provider->clearCache('key')` or `php bin/console platform-parameter:cache:clear key`
3. ❓ Is the type correct? → Check `type` column and called method
4. ❓ Is there a silent exception? → Check logs

---

## 🎛️ CLI Commands

The bundle provides 5 Symfony commands for managing parameters via the console.

### 1. List all parameters

```bash
php bin/console platform-parameter:list
```

**Features**:
- Displays a formatted table with columns: Key, Value, Type, Label
- Values longer than 50 characters are truncated with "..."
- Shows a warning if no parameters exist
- Output: "Found X parameter(s)."

**Use case**: Quick overview of all platform parameters

---

### 2. Get parameter details

```bash
php bin/console platform-parameter:get <key>
```

**Arguments**:
- `key` (required): The parameter key to display

**Features**:
- Shows all metadata: ID (UUID), Key, Value (full), Type, Label, Description, Created At, Updated At
- Returns error if parameter doesn't exist

**Examples**:
```bash
php bin/console platform-parameter:get max_uploads
php bin/console platform-parameter:get site_name
```

**Use case**: Inspect complete parameter details including timestamps

---

### 3. Create or update a parameter

```bash
php bin/console platform-parameter:set <key> <value> [options]
```

**Arguments**:
- `key` (required): The parameter key
- `value` (required): The parameter value

**Options**:
- `--type`: Parameter type (required for creation in non-interactive mode)
- `--label`: Parameter label (required for creation in non-interactive mode)
- `--description`: Parameter description (optional)

**Behavior**:

**If parameter exists**:
- Updates only the value
- Other metadata (type, label, description) remain unchanged
- Cache is automatically cleared

**If parameter doesn't exist**:

*Interactive mode (default)*:
- Prompts for type (choice from: string, integer, boolean, json, list, float, datetime)
- Prompts for label (text input)
- Prompts for description (optional, press Enter to skip)

*Non-interactive mode (`--no-interaction`)*:
- Requires `--type` and `--label` options
- `--description` is optional
- Returns error if required options are missing

**Examples**:

```bash
# Update existing parameter
php bin/console platform-parameter:set max_uploads 50

# Create new parameter (interactive mode)
php bin/console platform-parameter:set new_feature_flag true
# → Will ask: type? label? description?

# Create new parameter (non-interactive mode)
php bin/console platform-parameter:set api_timeout 30 \
    --type=integer \
    --label="API Timeout" \
    --description="Timeout in seconds for API calls" \
    --no-interaction

# Create boolean parameter
php bin/console platform-parameter:set maintenance_mode false \
    --type=boolean \
    --label="Maintenance Mode" \
    --no-interaction
```

**Use case**: 
- Quickly modify parameter values in scripts or deployments
- Seed initial parameters during installation
- Update configuration without accessing database or EasyAdmin

---

### 4. Delete a parameter

```bash
php bin/console platform-parameter:delete <key> [--force]
```

**Arguments**:
- `key` (required): The parameter key to delete

**Options**:
- `--force` or `-f`: Skip confirmation prompt

**Behavior**:
- Displays parameter details (key, value, type, label) before deletion
- Asks for confirmation in interactive mode (unless `--force` is used)
- Removes the parameter from database
- Clears the cache for the deleted key
- Returns error if parameter doesn't exist

**Examples**:

```bash
# Delete with confirmation prompt
php bin/console platform-parameter:delete old_feature_flag

# Delete without confirmation (useful in scripts)
php bin/console platform-parameter:delete old_feature_flag --force
php bin/console platform-parameter:delete old_feature_flag -f
```

**Use case**: 
- Clean up deprecated parameters
- Remove test parameters after debugging
- Automated cleanup scripts

---

### 5. Clear parameter cache

```bash
php bin/console platform-parameter:cache:clear [key]
```

**Arguments**:
- `key` (optional): Specific parameter key to clear (leave empty to clear all)

**Behavior**:
- If `key` is provided: clears cache for that specific parameter
- If `key` is omitted: clears cache for all parameters
- Uses tag-based invalidation if `TagAwareCache` is available (O(1))
- Otherwise fetches all parameters and deletes individually (O(n))

**Examples**:

```bash
# Clear cache for specific parameter
php bin/console platform-parameter:cache:clear site_name

# Clear cache for all parameters
php bin/console platform-parameter:cache:clear
```

**Use case**:
- Manual cache invalidation if automatic cache clearing is disabled
- Force cache refresh after direct database modifications
- Debugging cache-related issues

**Note**: If `clear_cache_on_parameter_update` is enabled (default), cache is automatically cleared when using `set` or `delete` commands, so this command is rarely needed.

---

### Commands Integration

All commands are automatically registered via Symfony's service autowiring. No manual registration needed.

**Command naming convention**: `platform-parameter:<action>`

**Return codes**:
- `0` (Command::SUCCESS): Operation completed successfully
- `1` (Command::FAILURE): Error occurred (parameter not found, validation failed, etc.)

**Dependencies injected**:
- `EntityManagerInterface`: For database operations
- `PlatformParameterProviderInterface`: For cache clearing
- `$entityClass` parameter: Configured entity class from bundle config

---

## 🔮 Future Improvements (Not Implemented)

### Event System
```php
// ParameterUpdatedEvent dispatched after flush
// → Auto-clear cache via EventSubscriber
// ✅ Now implemented: clear_cache_on_parameter_update configuration option
```

---

## 📚 References

- **Source code**: `/src`
- **Tests**: `/tests`
- **README**: User documentation
- **Symfony Docs**: https://symfony.com/doc/current/bundles.html
- **PSR-6 Cache**: https://www.php-fig.org/psr/psr-6/
