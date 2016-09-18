Phower Router
=============

PHP routing package compliant with [PSR-7](http://www.php-fig.org/psr/psr-7/).

Requirements
------------

Phower Router requires:

-   [PHP 5.6](http://php.net/releases/5_6_0.php) or above; 
    version [7.0](http://php.net/releases/7_0_0.php) is recommended

Instalation
-----------

Add Phower Router to any PHP project using [Composer](https://getcomposer.org/):

```bash
composer require phower/router
```

Usage
-----

Routing is the process to match and drive an HTTP request to a previously given
route. This process requires a Router instance with one or more Routes to evaluate
against the request.

### Routes

Each Route must have a name and a definition. Optionally it may also have constraints, 
default values for optional segments and allowed methods.

A simple Route example to match requests to the root path (```/```):

```php
use Phower\Router\Route;

$route = new Route('home', '/');
```

Another example for a Route matching requests to ```/some/path``` path, with a required
```id``` argument and an optional ```name``` argument:

```php
use Phower\Router\Route;

$route = new Route('some-name', '/some/path/:id[/:name]');
```

> Note that arguments are preceeded with a colon (```:```) and to make them optional
> we must use squared brackets.

To force an argument to some pattern we can specify a third argument in form of an
associative array containing a regular expression for each constraint.

In previous example we may wish to constrain ```id``` argument to only accept digits:

```php
use Phower\Router\Route;

$route = new Route('some-name', '/some/path/:id[/:name]', ['id' => '\d+']);
```

We can also provide default values for optional arguments. In case they are not
specified in the the original request then the default value will take their place.
It is also possible to attach other values to a Route using the defaults array:

```php
use Phower\Router\Route;

$route = new Route('some-name', '/some/path/:id[/:name]', ['id' => '\d+'], [
    'name' => 'Phower', // default value for name argument
    'type' => 'route',  // aditional value attached to this Route
]);
```

Some routes are also expected to match against a request with a specific method.
For example to just match ```POST``` requests we can tell that using a fifth argument
in the Route signature

```php
use Phower\Router\Route;

$route = new Route('some-name', '/some/path/:id[/:name]', ['id' => '\d+'], [
    'name' => 'Phower', // default value for name argument
    'type' => 'route',  // aditional value attached to this Route
], 'POST');
```

A static factory method is available to create routes from a configuration array:

```php
use Phower\Router\Route;

$route = Route::factory([
    'name' = > 'some-name',
    'definition' = > '/some/path/:id[/:name]',
    'constraints' = > [
        'id' => '\d+',
    ],
    'defaults' = > [
        'name' => 'Phower',
        'type' => 'route',
    ], 
    'methods' = > 'POST',
]);
```

Assembling a Route instance means to build its URL from the required arguments:

```php
use Phower\Router\Route;

$route = Route::factory([
    'name' = > 'some-name',
    'definition' = > '/some/path/:id[/:name]',
    'constraints' = > [
        'id' => '\d+',
    ],
    'defaults' = > [
        'name' => 'Phower',
        'type' => 'route',
    ], 
    'methods' = > 'POST',
]);

$url = $route->assemble([
    'id' => 123,
    'name' => 'my-name',
]);
// $url equals to: "/some/path/123/my-name"
```

### Router

Router is a stack of routes with methods to match a given HTTP request and to
assemble URLs for a named route.

Creating a Router instance just requires an array containing weither Route instances
or configuration arrays to instantiate routes:

```php
use Phower\Router\Router;
use Phower\Router\Route;

$router = new Router([
    new Route('home', '/'),
    [
        'name' => 'page',
        'definition' => '/page',
    ],
]);
```

Matching an HTTP request requires an instance of [RequestInterface](https://github.com/php-fig/http-message/blob/master/src/RequestInterface.php)
as argument:

```php
use Phower\Router\Router;
use Phower\Router\Route;

$router = new Router([
    new Route('home', '/'),
    [
        'name' => 'page',
        'definition' => '/page',
    ],
]);

/* @var $request \Psr\Http\Message\RequestInterface */
if ($router->match($request)) {
    $matchedRoute = $router->getMatched();
}
```

We can also assemble a route by name from a Router instance containing that named
route:

```php
use Phower\Router\Router;
use Phower\Router\Route;

$router = new Router([
    new Route('home', '/'),
    new Route('profiles', '/profiles'),
    new Route('profile', '/profiles/:id'),
]);

$url = $router->assemble('profiles', ['id' => 123]);
// $url equals to: "/profiles/123"
```

Running Tests
-------------

Tests are available in a separated namespace and can run with [PHPUnit](http://phpunit.de/)
in the command line:

```bash
vendor/bin/phpunit
```

Coding Standards
----------------

Phower code is written under [PSR-2](http://www.php-fig.org/psr/psr-2/) coding style standard.
To enforce that [CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) tools are also 
provided and can run as:

```bash
vendor/bin/phpcs
```

Reporting Issues
----------------

In case you find issues with this code please open a ticket in Github Issues at
[https://github.com/phower/router/issues](https://github.com/phower/router/issues).

Contributors
------------

Open Source is made of contribuition. If you want to contribute to Phower please
follow these steps:

1.  Fork latest version into your own repository.
2.  Write your changes or additions and commit them.
3.  Follow PSR-2 coding style standard.
4.  Make sure you have unit tests with full coverage to your changes.
5.  Go to Github Pull Requests at [https://github.com/phower/router/pulls](https://github.com/phower/router/pulls)
    and create a new request.

Thank you!

Changes and Versioning
----------------------

All relevant changes on this code are logged in a separated [log](CHANGELOG.md) file.

Version numbers follow recommendations from [Semantic Versioning](http://semver.org/).

License
-------

Phower code is maintained under [The MIT License](https://opensource.org/licenses/MIT).