<?php

if (!function_exists('class_uses_recursive')) {
    /**
     * Returns all traits used by a class, its parent classes and trait of their traits.
     *
     * @param object|string $baseClass
     *
     * @return array
     */
    function class_uses_recursive($baseClass)
    {
        if (is_object($baseClass)) {
            $baseClass = get_class($baseClass);
        }

        $results = [];

        foreach (array_reverse(class_parents($baseClass)) + [$baseClass => $baseClass] as $class) {
            $results += trait_uses_recursive($class);
        }

        return array_unique($results);
    }
}

if (!function_exists('trait_uses_recursive')) {
    /**
     * Returns all traits used by a trait and its traits.
     *
     * @param string $baseTrait
     *
     * @return array
     */
    function trait_uses_recursive($baseTrait)
    {
        $traits = class_uses($baseTrait);

        foreach ($traits as $trait) {
            $traits += trait_uses_recursive($trait);
        }

        return $traits;
    }
}

if (!function_exists('watcher_watch_timeout')) {
    function watcher_watch_timeout(): int
    {
        try {
            return app()->make(app()->make('watcher_watch_timeout'));
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
