<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Attribute;

use App\Infrastructure\Attribute\ClassAttributeCache;
use App\Infrastructure\Environment\Settings;
use App\Infrastructure\Serialization\Json;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Spatie\Snapshots\MatchesSnapshots;
use Tests\Support\Attribute\AsFixture;

class ClassAttributeCacheTest extends TestCase
{
    use MatchesSnapshots;

    private ClassAttributeCache $classAttributeCache;
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = Settings::getAppRoot() . "/tests/Infrastructure/Attribute/cache";
        @unlink($this->dir . "/AsFixture.php");
        @rmdir($this->dir);

        $this->classAttributeCache = new ClassAttributeCache(
            AsFixture::class,
            $this->dir,
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        @unlink($this->dir . "/AsFixture.php");
        @rmdir($this->dir);
    }

    public function testGetItShouldThrowIfNotExists(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Cache not set for AsFixture");

        $this->classAttributeCache->get();
    }

    public function testCompileSuccess(): void
    {
        $this->assertFalse($this->classAttributeCache->exists());
        $this->classAttributeCache->compile(["classOne", "classTwo"]);
        $this->classAttributeCache->compile(["classOne", "classTwo"]);
        $this->assertTrue($this->classAttributeCache->exists());

        $this->assertStringContainsString("tests/Infrastructure/Attribute/cache/AsFixture.php", $this->classAttributeCache->get());
        $this->assertMatchesJsonSnapshot(Json::encode(require $this->classAttributeCache->get()));
    }
}
