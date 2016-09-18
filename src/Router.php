<?php

/**
 * Phower Router
 *
 * @version 0.0.0
 * @link https://github.com/phower/router Public Git repository
 * @copyright (c) 2015-2016, Pedro Ferreira <https://phower.com>
 * @license https://opensource.org/licenses/MIT MIT
 */

namespace Phower\Router;

use Phower\Arrays\Stack;
use Phower\Arrays\StackInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Router
 *
 * @author Pedro Ferreira <pedro@phower.com>
 */
class Router implements RouterInterface, StackInterface
{

    /**
     * @var \Phower\Arrays\StackInterface
     */
    private $routes;

    /**
     * @var \Phower\Arrays\StackInterface
     */
    private $names;

    /**
     * @var \Phower\Router\RouteInterface
     */
    private $matched;

    /**
     * Class constructor
     *
     * @param array $routes
     */
    public function __construct(array $routes = [])
    {
        $this->routes = new Stack();
        $this->names = new Stack();

        foreach ($routes as $route) {
            $this->add($route);
        }
    }

    /**
     * Match a request
     *
     * @param \Psr\Http\Message\RequestInterface $request
     * @return bool
     */
    public function match(RequestInterface $request)
    {
        $this->matched = null;

        foreach ($this->routes as $route) {
            if ($route->match($request)) {
                $this->matched = $route;
                return true;
            }
        }

        return false;
    }

    /**
     * Has matched route
     *
     * @return bool
     */
    public function hasMatched()
    {
        return $this->matched instanceof RouteInterface;
    }

    /**
     * Get matched route
     *
     * @return RouteInterface|null
     * @throws Exception\RuntimeException
     */
    public function getMatched()
    {
        if (!$this->matched) {
            $message = 'There isn\'t any matched route.';
            throw new Exception\RuntimeException($message);
        }

        return $this->matched;
    }

    /**
     * Assemble an existing route as a string
     *
     * @param string|int $name
     * @param array $params
     * @return string
     * @throws Exception\InvalidArgumentException
     */
    public function assemble($name, array $params = [])
    {
        if (!$this->names->contains($name)) {
            $message = sprintf('Named route not found: "%s".', $name);
            throw new Exception\InvalidArgumentException($message);
        }

        return $this->get($name)->assemble($params);
    }

    /**
     * Has route
     *
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        return $this->names->contains($name);
    }

    public function contains($route)
    {
        $valid = $this->validateRoute($route);
        return $this->routes->contains($valid);
    }

    /**
     * Set a new route
     *
     * @param string $name
     * @param \Phower\Router\RouteInterface|array $route
     * @return \Phower\Router\Router
     */
    public function set($name, $route)
    {
        $valid = $this->validateRoute($route);
        $valid->setName($name);

        return $this->add($valid);
    }

    /**
     * Add route.
     *
     * @param \Phower\Router\RouteInterface|array $route
     * @return \Phower\Router\Router
     * @throws Exception\RuntimeException
     */
    public function add($route)
    {
        $valid = $this->validateRoute($route);
        $name = $valid->getName();

        if ($this->names->contains($name)) {
            $message = sprintf('A route named "%s" already exists in this router.', $name);
            throw new Exception\RuntimeException($message);
        }

        $this->routes->add($valid);
        $this->names->add($name);

        return $this;
    }

    /**
     * Get route
     *
     * @param string $name
     * @return \Phower\Router\RouteInterface|null
     */
    public function get($name)
    {
        if ($this->names->contains($name)) {
            $index = $this->names->indexOf($name);
            return $this->routes->get($index);
        }

        return null;
    }

    /**
     * Get name of existing route.
     *
     * @param type $route
     * @return string
     */
    public function indexOf($route)
    {
        $valid = $this->validateRoute($route);

        if ($this->contains($valid)) {
            return $valid->getName();
        }

        return null;
    }

    /**
     * Remove existing route
     *
     * @param string $name
     * @return \Phower\Router\Router
     */
    public function remove($name)
    {
        if (null !== $index = $this->names->indexOf($name)) {
            $this->routes->remove($index);
            $this->names->remove($index);
        }

        return $this;
    }

    /**
     * Delete route
     *
     * @param \Phower\Router\RouteInterface $route
     * @return \Phower\Router\Router
     */
    public function delete($route)
    {
        $valid = $this->validateRoute($route);

        if ($this->routes->contains($valid)) {
            $this->routes->delete($valid);
            $this->names->delete($valid->getName());
        }

        return $this;
    }

    /**
     * Export routes as an array
     *
     * @return array
     */
    public function toArray()
    {
        return $this->routes->toArray();
    }

    /**
     * Returns all route names as an array
     *
     * @return array
     */
    public function getKeys()
    {
        return $this->names->toArray();
    }

    /**
     * Returns all routes as an array.
     *
     * @return array
     */
    public function getValues()
    {
        return $this->toArray();
    }

    /**
     * Returns a new router filtered by a callable.
     *
     * @param callable $callback
     * @return \Phower\Router\Router
     */
    public function filter(callable $callback)
    {
        $filtered = $this->routes->filter($callback);
        return new static(array_reverse($filtered->toArray()));
    }

    /**
     * Returns a new sliced router.
     *
     * @param int $offset
     * @param int|null $length
     * @return \Phower\Router\Router
     */
    public function slice($offset, $length = null)
    {
        $sliced = $this->routes->slice($offset, $length);
        return new static(array_reverse($sliced->toArray()));
    }

    /**
     * Applies a callable over all routes of the router.
     *
     * @param callable $callback
     * @return \Phower\Router\Router
     */
    public function map(callable $callback)
    {
        $this->routes->map($callback);
        return $this;
    }

    /**
     * Checks an offset exists.
     *
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * Retrieves an existing route.
     *
     * @param string $offset
     * @return \Phower\Router\RouteInterface
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Sets a new route.
     *
     * @param string $offset
     * @param \Phower\Router\RouteInterface $value
     * @return \Phower\Router\Router
     */
    public function offsetSet($offset, $value)
    {
        return $this->set($offset, $value);
    }

    /**
     * Removes an existing route.
     *
     * @param string $offset
     * @return \Phower\Router\Router
     */
    public function offsetUnset($offset)
    {
        return $this->remove($offset);
    }

    /**
     * Counts how many routes are in the router.
     *
     * @return int
     */
    public function count()
    {
        return $this->routes->count();
    }

    /**
     * Rewinds router internal pointer to the first route.
     *
     * @return \Phower\Router\Router
     */
    public function rewind()
    {
        $this->names->rewind();
        $this->routes->rewind();
        return $this;
    }

    /**
     * Returns the current route.
     *
     * @return \Phower\Router\RouteInterface
     */
    public function current()
    {
        return $this->routes->current();
    }

    /**
     * Returns the current route name.
     *
     * @return string
     */
    public function key()
    {
        return $this->names->current();
    }

    /**
     * Moves internal pointer to the next route.
     *
     * @return \Phower\Router\Router
     */
    public function next()
    {
        $this->names->next();
        $this->routes->next();
        return $this;
    }

    /**
     * Checks if there are more routes in the router.
     *
     * @return bool
     */
    public function valid()
    {
        return $this->routes->valid();
    }

    /**
     * Serialize router
     *
     * @return string
     */
    public function serialize()
    {
        $value = [
            'names' => $this->names->serialize(),
            'routes' => $this->routes->serialize(),
        ];

        return sprintf('%s@%s', __CLASS__, serialize($value));
    }

    /**
     * Unserialize a string representation of a router
     *
     * @param string $serialized
     * @return \Phower\Router\Router
     * @throws Exception\InvalidArgumentException
     * @throws Exception\RuntimeException
     */
    public function unserialize($serialized)
    {
        $identifier = __CLASS__ . '@';

        if (substr($serialized, 0, strlen($identifier)) !== $identifier) {
            $message = sprintf('Serialized value doesn\'t represents an instance.of "%s".', __CLASS__);
            throw new Exception\InvalidArgumentException($message);
        }

        $router = unserialize(substr($serialized, strlen($identifier)));

        if (!is_array($router)) {
            $type = is_object($router) ? get_class($router) : gettype($router);
            $message = sprintf('Unserialized value in "%s" must be an array; "%s" was given.', __METHOD__, $type);
            throw new Exception\InvalidArgumentException($message);
        }

        foreach (['names', 'routes'] as $element) {
            if (!isset($router[$element])) {
                $message = sprintf('Missing "%s" element on the unserialized array in "%s".', $element, __METHOD__);
                throw new Exception\InvalidArgumentException($message);
            }

            $this->$element->unserialize($router[$element]);
        }

        if ($this->names->count() !== $this->routes->count()) {
            $message = 'Number of names doesn\'t match the number of routes.';
            throw new Exception\RuntimeException($message);
        }

        return $this;
    }

    /**
     * Validate route.
     *
     * @param \Phower\Router\RouteInterface\array $route
     * @return \Phower\Router\RouteInterface
     * @throws Exception\InvalidArgumentException
     */
    protected function validateRoute($route)
    {
        if (is_array($route)) {
            $route = Route::factory($route);
        }

        if (!$route instanceof RouteInterface) {
            $type = is_object($route) ? get_class($route) : gettype($route);
            $message = sprintf('Argument "route" in "%s" must be an array or an instance of "%s";'
                    . ' "%s" was given.', __METHOD__, RouteInterface::class, $type);
            throw new Exception\InvalidArgumentException($message);
        }

        return $route;
    }
}
