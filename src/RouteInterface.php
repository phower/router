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
interface RouteInterface
{

    /**
     * Factory
     *
     * @param array $config
     * @return \Phower\Router\RouteInterface
     * @throws \Phower\Router\Exception\InvalidArgumentException
     */
    public static function factory(array $config);

    /**
     * Set methods
     *
     * @param array|string|bool $methods
     * @return \Phower\Router\RouteInterface
     * @throws \Phower\Router\Exception\InvalidArgumentException
     */
    public function setMethods($methods);

    /**
     * Get methods
     *
     * @return array|bool
     */
    public function getMethods();

    /**
     * Set name
     *
     * @param string
     * @return \Phower\Router\RouteInterface
     */
    public function setName($name);

    /**
     * Get name
     *
     * @return string
     */
    public function getName();

    /**
     * Get definition
     *
     * @return string
     */
    public function getDefinition();

    /**
     * Get constraints
     *
     * @return array
     */
    public function getConstraints();

    /**
     * Get defaults
     *
     * @return array
     */
    public function getDefaults();

    /**
     * Get regex
     *
     * @return string
     */
    public function getRegex();

    /**
     * Match
     *
     * @param \Psr\Http\Message\RequestInterface $request
     * @param int $basePath
     * @return bool
     */
    public function match(RequestInterface $request, $basePath = null);

    /**
     * Params
     *
     * @return array|null
     */
    public function params();

    /**
     * Allow
     *
     * @param \Psr\Http\Message\RequestInterface $request
     * @return boolean
     */
    public function allow(RequestInterface $request);

    /**
     * Assemble
     *
     * @param array $params
     * @return string
     */
    public function assemble(array $params = []);
}
