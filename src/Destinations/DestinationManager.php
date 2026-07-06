<?php

namespace Whilesmart\Forms\Destinations;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Whilesmart\Forms\Contracts\FormDestination;

class DestinationManager
{
    public function __construct(private Container $container)
    {
    }

    /**
     * Resolve a destination key to its driver instance.
     */
    public function driver(string $key): FormDestination
    {
        $map = config('eloquent-forms.drivers', []);

        if (! isset($map[$key])) {
            throw new InvalidArgumentException("Form destination [{$key}] is not registered.");
        }

        $instance = $this->container->make($map[$key]);

        if (! $instance instanceof FormDestination) {
            throw new InvalidArgumentException("Form destination [{$key}] must implement FormDestination.");
        }

        return $instance;
    }
}
