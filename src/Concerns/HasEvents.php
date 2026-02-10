<?php

declare(strict_types=1);

namespace PhpClickHouseLaravel\Concerns;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Events\NullDispatcher;

/**
 * Minimal event dispatching for ClickHouse models.
 *
 * Unlike Eloquent's HasEvents trait, this does not require extending
 * Illuminate\Database\Eloquent\Model, making it compatible with
 * ClickHouse's BaseModel, which is not an Eloquent model.
 *
 * Supports: creating, created, saved events (the events BaseModel actually fires).
 * Does not include: observers, $dispatchesEvents mapping, bootHasEvents, or
 * attribute-based observer registration — these are Eloquent-specific features.
 */
trait HasEvents
{
    /**
     * @var Dispatcher|null
     */
    protected static $dispatcher;

    /**
     * @param string $event
     * @param bool $halt
     *
     * @return mixed
     */
    protected function fireModelEvent(string $event, bool $halt = true): mixed
    {
        if (!isset(static::$dispatcher)) {
            return true;
        }

        $method = $halt ? 'until' : 'dispatch';

        return static::$dispatcher->{$method}(
            "eloquent.{$event}: " . static::class,
            $this
        );
    }

    /**
     * @return Dispatcher|null
     */
    public static function getEventDispatcher(): ?Dispatcher
    {
        return static::$dispatcher;
    }

    /**
     * @param Dispatcher $dispatcher
     *
     * @return void
     */
    public static function setEventDispatcher(Dispatcher $dispatcher): void
    {
        static::$dispatcher = $dispatcher;
    }

    /**
     * @return void
     */
    public static function unsetEventDispatcher(): void
    {
        static::$dispatcher = null;
    }

    /**
     * @param callable $callback
     *
     * @return mixed
     */
    public static function withoutEvents(callable $callback): mixed
    {
        $dispatcher = static::getEventDispatcher();

        if ($dispatcher) {
            static::setEventDispatcher(new NullDispatcher($dispatcher));
        }

        try {
            return $callback();
        } finally {
            if ($dispatcher) {
                static::setEventDispatcher($dispatcher);
            }
        }
    }
}
