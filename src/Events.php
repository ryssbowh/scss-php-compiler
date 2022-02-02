<?php

namespace Ryssbowh\ScssPhp;

use Ryssbowh\ScssPhp\helpers\Configure;
use Symfony\Component\Filesystem\Filesystem;

class Events
{
    /**
     * @var array
     */
    protected $definedEvents;

    /**
     * @var array
     */
    protected $events = [];

    /**
     * @var Compiler
     */
    protected $compiler;

    /**
     * Constructor
     * 
     * @param Compiler $compiler
     * @param array    $definedEvents
     */
    public function __construct(Compiler $compiler, array $definedEvents = [])
    {
        $this->compiler = $compiler;
        $this->definedEvents = $definedEvents;
    }

    /**
     * Define new events
     * 
     * @param  string|array $events
     */
    public function define($events)
    {
        if (!$events) {
            return;
        }
        $events = is_array($events) ? $events : [$events];
        foreach ($events as $event) {
            if (in_array($event, $this->definedEvents)) {
                $this->compiler->warn("The event '$event' is already defined");
                continue;
            }
            $this->definedEvents[] = $event;
        }
    }

    /**
     * Is an event defined
     * 
     * @param  string  $event
     * @return boolean
     */
    public function isDefined(string $event): bool
    {
        return in_array($event, $this->definedEvents);
    }

    /**
     * Register a callback to an event
     * 
     * @param  string         $event
     * @param  array|callable $callable
     * @param  int            $order
     */
    public function register(string $event, $callable, int $order = 10)
    {
        if (!$this->isDefined($event)) {
            $this->compiler->warn("Events: tried to register to an unkown event '$event'");
            return;
        }
        if (!isset($this->events[$event])) {
            $this->events[$event] = [];
        }
        $this->events[$event][] = [
            'callable' => $callable,
            'order' => $order
        ];
    }

    /**
     * Trigger an event
     * 
     * @param  string  $name
     * @param  array   $params Parameters for the callback
     * @param  mixed   $returnValue Return value
     * @param  boolean $stopOnValue Return if a value (not null) is returned from a callback
     * @return mixed
     */
    public function trigger(string $name, array $params = [], $returnValue = null, bool $stopOnValue = false)
    {
        if (!$this->isDefined($name)) {
            $this->compiler->warn("Events: tried to trigger an unkown event '$name'");
            return $returnValue;
        }
        if (!isset($this->events[$name])) {
            return $returnValue;
        }
        $events = $this->events[$name];
        uasort($events, function ($a, $b) {
            return ($a['order'] < $b['order']) ? (($a['order'] == $b['order']) ? 0 : -1) : 1;
        });
        foreach ($events as $event) {
            $value = call_user_func($event['callable'], ...$params);
            if ($value !== null) {
                if ($stopOnValue) {
                    return $value;
                }
                $returnValue = $value;
            }
        }
        return $returnValue;
    }
}