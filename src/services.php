<?php

declare(strict_types=1);

use App\Doctrine\EventListener\FixPostgreSQLDefaultSchemaListener;
use Doctrine\ORM\Tools\ToolEvents;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    $services
        ->set(FixPostgreSQLDefaultSchemaListener::class)
        ->tag('doctrine.event_listener', ['event' => ToolEvents::postGenerateSchema]);
};