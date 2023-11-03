<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Events;

use App\Infrastructure\Serialization\Json;
use Spatie\Snapshots\MatchesSnapshots;
use Tests\ContainerTestCase;

class DomainEventTest extends ContainerTestCase
{
    use MatchesSnapshots;

    public function testMetaDataSuccess(): void
    {
        $command = new TestEvent();

        $command->setMetaData(["key" => "value"]);
        $this->assertMatchesJsonSnapshot(Json::encode($command));
        $command->setMetaData(["key" => "value override", "key2" => "value"]);
        $this->assertMatchesJsonSnapshot(Json::encode($command));
    }
}
