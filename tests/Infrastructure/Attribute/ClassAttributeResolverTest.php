<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Attribute;

use App\Infrastructure\Attribute\ClassAttributeResolver;
use App\Infrastructure\Environment\Settings;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Attribute\AsCommand;

class ClassAttributeResolverTest extends TestCase
{
    use MatchesSnapshots;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = Settings::getAppRoot() . "/tests/Infrastructure/Attribute/cache";
        @unlink($this->dir . "/AsCommand.php");
        @rmdir($this->dir);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        @unlink($this->dir . "/AsCommand.php");
        @rmdir($this->dir);
    }

    public function testResolveSuccess(): void
    {
        $resolver = new ClassAttributeResolver();
        $classes = $resolver->resolve(AsCommand::class, ["src/Application/Console"]);
        sort($classes);

        $this->assertMatchesJsonSnapshot($classes);
    }

    public function testResolveWithCacheSuccess(): void
    {
        $resolver = new ClassAttributeResolver();
        $classes = $resolver->resolve(
            AsCommand::class,
            ["src/Application/Console"],
            $this->dir,
        );
        $this->assertEquals($classes, $resolver->resolve(
            AsCommand::class,
            ["src/Application/console"],
            $this->dir,
        ));
        sort($classes);

        $this->assertFileExists($this->dir . "/AsCommand.php");
        $this->assertMatchesJsonSnapshot($classes);
    }
}
