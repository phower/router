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
 * Route
 *
 * @author Pedro Ferreira <pedro@phower.com>
 */
class Route implements RouteInterface
{

    /**
     * Valid HTTP methods
     * @link https://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html
     */
    const HTTP_METHODS = ['OPTIONS', 'HEAD', 'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'TRACE', 'CONNECT'];

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $definition;

    /**
     * @var array
     */
    protected $constraints;

    /**
     * @var array
     */
    protected $defaults;

    /**
     * @var array|bool
     */
    protected $methods;

    /**
     * @var array
     */
    protected $parts;

    /**
     * @var string
     */
    protected $regex;

    /**
     * @var array
     */
    protected $paramMap;

    /**
     * @var array
     */
    protected $params;

    /**
     * Class constructor
     *
     * @param string $name
     * @param string $definition
     * @param array $constraints
     * @param array $defaults
     * @param array|string|bool $methods
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($name, $definition, array $constraints = [], array $defaults = [], $methods = true)
    {
        if (!is_string($definition)) {
            $type = is_object($definition) ? get_class($definition) : gettype($definition);
            $message = sprintf('Argument "definition" in "%s" is expected to be a string: '
                    . '"%s" was given.', __METHOD__, $type);
            throw new Exception\InvalidArgumentException($message);
        }

        $this->setName($name);
        $this->definition = $definition;
        $this->constraints = $constraints;
        $this->defaults = $defaults;

        $this->setMethods($methods);

        $this->parts = $this->parseDefinition($definition);
        $this->regex = $this->buildRegex($this->parts, $constraints);
    }

    /**
     * Factory
     *
     * @param array $config
     * @return \Phower\Router\Route
     * @throws Exception\InvalidArgumentException
     */
    public static function factory(array $config)
    {
        if (!isset($config['definition'])) {
            $message = 'Missing "definition" config option.';
            throw new Exception\InvalidArgumentException($message);
        }

        $name = isset($config['name']) ? $config['name'] : $config['definition'];
        $definition = $config['definition'];
        $constraints = isset($config['constraints']) ? $config['constraints'] : [];
        $defaults = isset($config['defaults']) ? $config['defaults'] : [];
        $methods = isset($config['methods']) ? $config['methods'] : true;

        return new static($name, $definition, $constraints, $defaults, $methods);
    }

    /**
     * Set methods
     *
     * @param array|string|bool $methods
     * @return \Phower\Router\Route
     * @throws Exception\InvalidArgumentException
     */
    public function setMethods($methods)
    {
        if ($methods === true) {
            $this->methods = true;
            return $this;
        }

        if (is_string($methods)) {
            $methods = [$methods];
        }

        if (!is_array($methods)) {
            $type = is_object($methods) ? get_class($methods) : gettype($methods);
            $message = sprintf('Argument "methods" in "%s" must be an array, a string or a boolean true value; '
                    . '"%s" was given.', __METHOD__, $type);
            throw new Exception\InvalidArgumentException($message);
        }

        $this->methods = [];

        foreach ($methods as $method) {
            if (!is_string($method)) {
                $type = is_object($method) ? get_class($method) : gettype($method);
                $message = sprintf('Any elements of "methods" in "%s" must be string; '
                        . '"%s" was given.', __METHOD__, $type);
                throw new Exception\InvalidArgumentException($message);
            }

            $method = strtoupper($method);

            if (!in_array($method, self::HTTP_METHODS)) {
                $allowed = implode(', ', self::HTTP_METHODS);
                $message = sprintf('Invalid method "%s" in "%s"; '
                        . 'please use only these "%s".', $method, __METHOD__, $allowed);
                throw new Exception\InvalidArgumentException($message);
            }

            $this->methods[] = $method;
        }

        return $this;
    }

    /**
     * Get methods
     *
     * @return array|bool
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * Parse definition
     *
     * @param string $definition
     * @return array
     * @throws Exception\RuntimeException
     */
    protected function parseDefinition($definition)
    {
        $pos = 0;
        $length = strlen($definition);
        $parts = [];
        $stack = [&$parts];
        $level = 0;

        while ($pos < $length) {
            preg_match('(\G(?P<literal>[^:{\[\]]*)(?P<token>[:{\[\]]|$))', $definition, $matches, 0, $pos);
            $pos += strlen($matches[0]);

            if (!empty($matches['literal'])) {
                $stack[$level][] = ['literal', $matches['literal']];
            }

            if ($matches['token'] === ':') {
                $pattern = '(\G(?P<name>[^:/{\[\]]+)(?:{(?P<delimiters>[^}]+)})?:?)';
                if (!preg_match($pattern, $definition, $matches, 0, $pos)) {
                    throw new Exception\RuntimeException('Found empty parameter name');
                }

                $stack[$level][] = [
                    'parameter',
                    $matches['name'],
                    isset($matches['delimiters']) ? $matches['delimiters'] : null,
                ];
                $pos += strlen($matches[0]);
            } elseif ($matches['token'] === '[') {
                $stack[$level][] = ['optional', []];
                $stack[$level + 1] = &$stack[$level][count($stack[$level]) - 1][1];
                $level++;
            } elseif ($matches['token'] === ']') {
                unset($stack[$level]);
                $level--;

                if ($level < 0) {
                    $message = 'Found closing bracket without matching opening bracket';
                    throw new Exception\RuntimeException($message);
                }
            } else {
                break;
            }
        }

        if ($level > 0) {
            throw new Exception\RuntimeException('Found unbalanced brackets');
        }

        return $parts;
    }

    /**
     * Build regex
     *
     * @param array $parts
     * @param array $constraints
     * @param int $groupIndex
     * @return string
     */
    protected function buildRegex(array $parts, array $constraints, &$groupIndex = 1)
    {
        $regex = '';

        foreach ($parts as $part) {
            switch ($part[0]) {
                case 'literal':
                    $regex .= preg_quote($part[1]);
                    break;
                case 'parameter':
                    $groupName = '?P<param' . $groupIndex . '>';

                    if (isset($constraints[$part[1]])) {
                        $regex .= '(' . $groupName . $constraints[$part[1]] . ')';
                    } elseif ($part[2] === null) {
                        $regex .= '(' . $groupName . '[^/]+)';
                    } else {
                        $regex .= '(' . $groupName . '[^' . $part[2] . ']+)';
                    }

                    $this->paramMap['param' . $groupIndex++] = $part[1];
                    break;
                case 'optional':
                    $regex .= '(?:' . $this->buildRegex($part[1], $constraints, $groupIndex) . ')?';
                    break;
            }
        }

        return $regex;
    }

    /**
     * Set name
     *
     * @param string
     * @return \Phower\Router\Route
     * @throws Exception\InvalidArgumentException
     */
    public function setName($name)
    {
        if (!is_string($name)) {
            $type = is_object($name) ? get_class($name) : gettype($name);
            $message = sprintf('Argument "name" in "%s" is expected to be a string;'
                    . ' "%s" was given.', __METHOD__, $type);
            throw new Exception\InvalidArgumentException($message);
        }

        if (preg_match('/[\\/]+/', $name)) {
            $message = 'Argument "name" must not contain any "\" or "/" characters.';
            throw new Exception\InvalidArgumentException($message);
        }

        $this->name = $name;
        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get definition
     *
     * @return string
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * Get constraints
     *
     * @return array
     */
    public function getConstraints()
    {
        return $this->constraints;
    }

    /**
     * Get defaults
     *
     * @return array
     */
    public function getDefaults()
    {
        return $this->defaults;
    }

    /**
     * Get regex
     *
     * @return string
     */
    public function getRegex()
    {
        return $this->regex;
    }

    /**
     * Match
     *
     * @param RequestInterface $request
     * @param int $basePath
     * @return bool
     */
    public function match(RequestInterface $request, $basePath = null)
    {
        $regex = '(^' . $this->regex . '$)';
        $path = $request->getUri()->getPath();

        if ($basePath !== null) {
            $length = strlen($basePath);

            if (substr($path, 0, $length) !== $basePath) {
                return false;
            }

            $path = substr($path, $length);
        }

        $result = (bool) preg_match($regex, $path, $matches, null, (int) $basePath);
        $this->params = null;

        if ($result) {
            $params = [];
            foreach ($matches as $name => $value) {
                if (isset($this->paramMap[$name])) {
                    $params[$this->paramMap[$name]] = $value;
                }
            }
            $this->params = array_merge($this->defaults, $params);
        }

        return $result;
    }

    /**
     * Params
     *
     * @return array|null
     */
    public function params()
    {
        return $this->params;
    }

    /**
     * Allow
     *
     * @param RequestInterface $request
     * @return boolean
     */
    public function allow(RequestInterface $request)
    {
        if ($this->methods !== true) {
            $method = $request->getMethod();
            if (!in_array($method, $this->methods)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Assemble
     *
     * @param array $params
     * @return string
     */
    public function assemble(array $params = [])
    {
        $parts = $this->parts;
        $merged = array_merge($this->defaults, $params);

        $path = $this->buildPath($parts, $merged);
        $this->params = $merged;

        return $path;
    }

    /**
     * Build path
     *
     * @param array $parts
     * @param array $params
     * @param boolean $optional
     * @return string
     * @throws Exception\InvalidArgumentException
     */
    protected function buildPath(array $parts, array $params, $optional = false)
    {
        $path = '';

        foreach ($parts as $part) {
            switch ($part[0]) {
                case 'literal':
                    $path .= $part[1];
                    break;
                case 'parameter':
                    if (!isset($params[$part[1]])) {
                        if (!$optional) {
                            $message = sprintf('Missing parameter "%s"', $part[1]);
                            throw new Exception\InvalidArgumentException($message);
                        }
                        return '';
                    }
                    $path .= rawurlencode($params[$part[1]]);
                    break;
                case 'optional':
                    $segment = $this->buildPath($part[1], $params, true);
                    if ($segment !== '') {
                        $path .= $segment;
                    }
                    break;
            }
        }

        return $path;
    }
}
