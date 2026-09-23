<?php

function route_to_regex(string $route): string
{
    $pattern = preg_replace('#\{(\w+)\}#', '([^/]+)', $route);
    return '#^' . $pattern . '$#';
}

function resolve(string $class, array &$controllers): object {
    if (isset($controllers[$class])) {
        return $controllers[$class];
    }
    $controllers[$class] = new $class();
    return $controllers[$class];
}