<?php

declare(strict_types=1);

namespace App\Application\Console\Utility;

use App\Infrastructure\Environment\Settings;
use AvroSchema;
use FlixTech\SchemaRegistryApi\Registry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Throwable;

#[AsCommand(name: "app:kafka:schema:register", description: "Register every Avro schema from resources/avro in the schema registry")]
class KafkaSchemaRegisterConsoleCommand extends ConsoleCommand
{
    public function __construct(
        private readonly Registry $registry,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $directory = Settings::getAppRoot() . "/resources/avro";

        if (!is_dir($directory)) {
            $output->writeln(sprintf("<error>Schema directory %s does not exist</error>", $directory));

            return Command::FAILURE;
        }

        $files = (new Finder())->files()->in($directory)->name("*.avsc")->sortByName();

        if (!$files->hasResults()) {
            $output->writeln("<comment>No .avsc files found, nothing to register</comment>");

            return Command::SUCCESS;
        }

        $failed = 0;

        foreach ($files as $file) {
            $subject = $file->getBasename(".avsc");

            try {
                $schema = AvroSchema::parse($file->getContents());
                $this->registry->register($subject, $schema);

                $output->writeln(sprintf(
                    "<info>%s</info> registered as id %d, version %d",
                    $subject,
                    $this->registry->schemaId($subject, $schema),
                    $this->registry->schemaVersion($subject, $schema),
                ));
            } catch (Throwable $exception) {
                $failed++;

                $output->writeln(sprintf(
                    "<error>%s failed: %s</error>",
                    $subject,
                    $exception->getMessage(),
                ));
            }
        }

        return $failed === 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
