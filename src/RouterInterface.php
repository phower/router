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

use Psr\Http\Message\RequestInterface;

/**
 * Router interface
 *
 * @author Pedro Ferreira <pedro@phower.com>
 */
interface RouterInterface
{

    /**
     * Has route
     *
     * @param string|int|\Phower\Router\RouteInterface $route
     * @return bool
     */
    public function has($route);

    /**
     * Set a new route
     *
     * @param string $name
     * @param \Phower\Router\RouteInterface $route
     * @return \Phower\Router\Router
     * @throws \Phower\Router\Exception\InvalidArgumentException
     */
    public function set($name, $route);

    /**
     * Get route
     *
     * @param string|int $name
     * @return \Phower\Router\RouteInterface|null
     */
    public function get($name);

    /**
     * Remove route
     *
     * @param string|int|\Phower\Router\RouteInterface $route
     * @return \Phower\Router\Router
     */
    public function remove($route);

    /**
     * Export routes as an array
     *
     * @return array
     */
    public function toArray();

    /**
     * Match a request
     *
     * @param \Psr\Http\Message\RequestInterface $request
     * @return bool
     */
    public function match(RequestInterface $request);

    /**
     * Has matched route
     *
     * @return bool
     */
    public function hasMatched();

    /**
     * Get matched route
     *
     * @return RouteInterface|null
     */
    public function getMatched();

    /**
     * Assemble an existing route as a string
     *
     * @param string|int $name
     * @param array $params
     * @return string
     * @throws \Phower\Router\Exception\InvalidArgumentException
     */
    public function assemble($name, array $params = []);
}
