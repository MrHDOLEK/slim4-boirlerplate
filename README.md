
# Slim 4 Framework skeleton

![Slim](slim.webp)

[![CI](https://github.com/MrHDOLEK/slim4-boirlerplate/actions/workflows/php.yml/badge.svg?branch=main)](https://github.com/MrHDOLEK/slim4-boirlerplate/actions/workflows/php.yml)
[![Codecov.io](https://codecov.io/gh/MrHDOLEK/slim4-boirlerplate/graph/badge.svg?token=KKBMW5HJVM)](https://codecov.io/gh/MrHDOLEK/slim4-boirlerplate)
[![License](https://img.shields.io/github/license/robiningelbrecht/slim-skeleton-ddd-amqp?color=428f7e&logo=open%20source%20initiative&logoColor=white)](https://github.com/MrHDOLEK/slim4-boirlerplate/blob/master/LICENSE)
[![PHPStan Enabled](https://img.shields.io/badge/PHPStan-level%205-succes.svg?logo=php&logoColor=white&color=31C652)](https://phpstan.org/)
[![PHP](https://img.shields.io/packagist/php-v/mrhdolek/slim4-boirlerplate/dev-main?color=%23777bb3&logo=php&logoColor=white)](https://php.net/)


---
#### An Slim 4 Framework skeleton using AMQP and DDD

I was inspired to create this skeleton from: [robiningelbrecht](https://github.com/robiningelbrecht).

## Project setup

### Development
If you have problems with permissions please add sudo before make example:
- `sudo make install`
- `sudo make start`
### Run env for Mac/Linux

- `make install`
- `make start`
- `make db-create`

### Run env for Windows
Please install packages makefile for [Windows](http://gnuwin32.sourceforge.net/packages/make.htm)
- `make install`
- `make start`
- `make db-create`

### Address where the environment is available
- `http://localhost`
## Documentation for a Rest Api
- `http://localhost/docs/v1`
## RabbitMq dashboard
- `http://localhost:15672`
## All commands

-  `make help`

## Some examples

### Registering a new route

```php
namespace App\Application\Actions\User;

class GetAllUsersAction extends UserAction
{
    public function __construct(
        private readonly UserService $userService,
        protected LoggerInterface $logger,
    ) {
        parent::__construct($logger);
    }

    protected function action(): Response
    {
        $user = $this->userService->getAllUsers();

        return $this->respondWithJson(new UsersResponseDto($user));
    }
}
```

Head over to `config/routes.php` and add a route for your RequestHandler:

```php
return function (App $app) {
        $group->get("/users", GetAllUsersAction::class)
            ->setName("getAllUsers");
};
```

### Console commands

The console application uses the Symfony console component to leverage CLI functionality.


```php
#[AsCommand(name: 'app:user:create')]
class CreateUserConsoleCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // ...
        return Command::SUCCESS;
    }
}
```

### Scheduling Commands

To schedule a command, we use the GO\Scheduler class. This class allows us to define the timing and frequency of command execution. Here's an example of how to schedule a command to run daily:

```php
$scheduler = new GO\Scheduler();
$scheduler->php('/path/to/command app:user:create')->daily();
$scheduler->run();
```
In this example, the app:user:create command is scheduled to run every day.

#### Running the Scheduler
The scheduler should be triggered by a system cron job to ensure it runs at regular intervals. Typically, you would set up a cron job to execute a PHP script that initializes and runs the scheduler.

For instance, a cron job running every minute might look like this:

```bash
* * * * * ./bin/console.php schedule
```

This setup ensures that your scheduled commands are executed reliably and on time.


### Domain event and event handlers

The framework implements the amqp protocol with handlers that allow events to be easily pushed onto the queue.
Each event must have a handler implemented that consumes the event.

#### Creating a new event

```php
class UserWasCreated extends DomainEvent
{
 
}
```

#### Creating the corresponding event handler

```php
namespace App\Domain\Entity\User\DomainEvents;

#[AsEventHandler]
class UserWasCreatedEventHandler implements EventHandler
{
    public function __construct(
    ) {
    }

    public function handle(DomainEvent $event): void
    {
        assert($event instanceof UserWasCreated);

        // Do stuff.
    }
}
```

### Eventing

#### Create a new event

```php
class UserWasCreated extends DomainEvent
{
    public function __construct(
        private UserId $userId,
    ) {
    }

    public function getUserId(): UserId
    {
        return $this->userId;
    }
}
```

### Async processing of commands with RabbitMQ

The chosen AMQP implementation for this project is RabbitMQ, but it can be easily switched to for example Amazon's AMQP solution.

#### Registering new queues

```php
#[AsAmqpQueue(name: "user-command-queue", numberOfWorkers: 1)]
class UserEventQueue extends EventQueue
{
}
```

#### Queueing events

```php
final readonly class UserEventsService
{
    public function __construct(
        private UserEventQueue $userEventQueue,
    ) {}

    public function userWasCreated(User $user): void
    {
        $this->userEventQueue->queue(new UserWasCreated($user));
    }
}
```

#### Consuming your queue

```bash
> docker-compose run --rm php bin/console.php app:amqp:consume user-command-queue
```

### Create new entity

If you have created a new entity and want to map it to a database you must create a xml in src/Infrastructure/Persistence/Doctrine/Mapping . 
It must be named so as to indicate where exactly the entity to be mapped is located.

### Binding of interfaces/Registry global objects

To register a dependency or create a single configured global instance, you need to go to config/container.php

### Mapping database data to custom objects

To map data from a database to a custom object you need to extend something from Doctrine/DBAL/Types .

```php
use App\Domain\ValueObject\UserType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

class UserTypeType extends StringType
{
    const TYPE_NAME = 'UserType';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?UserType
    {
        return null !== $value ? UserType::fromString($value) : null;
    }

    public function getName()
    {
        return self::TYPE_NAME;
    }
}
```

After extending, you need to add a note in the xml that you are mapping a field to an object.

```xml
    <field name="type" type="UserType" column="type" nullable="true"/>
```

Finally, you must add the new type in config/container.php where doctrine is configured

```php
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Setup;

return [
...
    EntityManager::class => function (Settings $settings): EntityManager {
        $config = Setup::createXMLMetadataConfiguration(
            $settings->get("doctrine.metadata_dirs"),
            $settings->get("doctrine.dev_mode"),
        );
        if (!Type::hasType('UserType')) {
          Type::addType('UserType', UserTypeType::class);
        }
        return EntityManager::create($settings->get("doctrine.connection"), $config);
    },
 ...
];

```


### Database migrations

To manage database migrations, the doctrine/migrations package is used.

```xml
<doctrine-mapping
xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
https://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">
<entity name="App\Domain\Entity\User\User" table="users">
<id name="id" type="integer" column="id">
<generator strategy="SEQUENCE"/>
<sequence-generator sequence-name="user_id_seq" allocation-size="1" initial-value="1"/>
</id>
<field name="username" type="string" column="name" length="64" nullable="true"/>
<field name="firstName" type="string" column="surname" length="64" nullable="true"/>
<field name="lastName" type="string" column="email" length="64" nullable="true"/>
<options>
<option name="collate">utf8mb4_polish_ci</option>
</options>
</entity>
</doctrine-mapping>
```

The mapping is done using a yaml which maps your entities from the domain to a structure in the database . 
If you change something in yaml, you can use the commands below to generate a migration based on the difference.

```bash
> docker-compose run --rm php vendor/bin/doctrine-migrations diff
> docker-compose run --rm php vendor/bin/doctrine-migrations migrate
```

### Swoole

To use swoole, just set `DOCKER_TARGET_APP=swoole` in .env and rebuild the application container.

## Documentations

Learn more at these links:

- [Slim framework](https://www.slimframework.com)
- [PHP-DI](https://php-di.org/)
- [Symfony Console Commands](https://symfony.com/doc/current/console.html)
- [Doctrine migrations](https://www.doctrine-project.org/projects/doctrine-migrations/en/3.6/)
- [Doctrine reference](https://www.doctrine-project.org/projects/doctrine-bundle/en/latest/configuration.html)
- [Twig](https://twig.symfony.com/)
- [Swoole](https://openswoole.com/docs)