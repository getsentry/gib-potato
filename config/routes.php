<?php
/**
 * Routes configuration.
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different URLs to chosen controllers and their actions (functions).
 *
 * It's loaded within the context of `Application::routes()` method which
 * receives a `RouteBuilder` instance `$routes` as method argument.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

return static function (RouteBuilder $routes) {
    $routes->setRouteClass(DashedRoute::class);

    // Cross Site Request Forgery (CSRF) Protection Middleware
    // https://book.cakephp.org/4/en/security/csrf.html#cross-site-request-forgery-csrf-middleware
    $routes->registerMiddleware('csrf', new CsrfProtectionMiddleware([
        'httponly' => true,
    ]));

    $routes->scope('/', function (RouteBuilder $builder) {
        $builder->applyMiddleware('csrf');

        $builder->connect('/login', ['controller' => 'Login', 'action' => 'login']);
        $builder->connect('/logout', ['controller' => 'Login', 'action' => 'logout']);

        $builder->connect('/open-id', ['controller' => 'Login', 'action' => 'openId']);

        $builder->connect('/', ['controller' => 'Home', 'action' => 'index']);

        $builder->fallbacks();
    });

    // Routes in this scope don't have CSRF protection.
    $routes->scope('/', function (RouteBuilder $builder) {
        $builder->connect('/events', ['controller' => 'Slack', 'action' => 'index']);
    });
};
