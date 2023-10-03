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

use Authentication\AuthenticationService;
use Authentication\Middleware\AuthenticationMiddleware;
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

return static function (RouteBuilder $routes): void {
    $routes->setRouteClass(DashedRoute::class);

    // Cross Site Request Forgery (CSRF) Protection Middleware
    // https://book.cakephp.org/4/en/security/csrf.html#cross-site-request-forgery-csrf-middleware
    $routes->registerMiddleware('csrf', new CsrfProtectionMiddleware());

    $webAuthService = new AuthenticationService([
        'unauthenticatedRedirect' => '/login',
        'queryParam' => 'redirect',
    ]);
    $webAuthService->loadIdentifier('ApiToken');
    $webAuthService->loadAuthenticator('Authentication.Session');
    $webAuthService->loadAuthenticator('Authentication.Token', [
        'header' => 'Authorization',
        'tokenPrefix' => 'Bearer',
    ]);
    $routes->registerMiddleware('web-auth', new AuthenticationMiddleware($webAuthService));

    $routes->scope('/', function (RouteBuilder $builder): void {
        $builder->applyMiddleware('csrf');
        $builder->applyMiddleware('web-auth');

        $builder->connect('/login', ['controller' => 'Login', 'action' => 'login']);
        $builder->connect('/logout', ['controller' => 'Login', 'action' => 'logout']);

        $builder->connect('/open-id/*', ['controller' => 'Login', 'action' => 'openId']);
        $builder->connect('/start-open-id/*', ['controller' => 'Login', 'action' => 'startOpenId']);

        $builder->connect('/', ['controller' => 'Home', 'action' => 'index']);
        $builder->connect('/shop', ['controller' => 'Home', 'action' => 'index']);
        $builder->connect('/collection', ['controller' => 'Home', 'action' => 'index']);
        $builder->connect('/profile', ['controller' => 'Home', 'action' => 'index']);
        $builder->connect('/settings', ['controller' => 'Home', 'action' => 'index']);

        $builder->connect('/terms', ['controller' => 'Terms', 'action' => 'index']);

        $builder->scope('/api', function (RouteBuilder $builder) {    
            $builder->get('/leaderboard', ['prefix' => 'Api', 'controller' => 'LeaderBoard', 'action' => 'get']);

            $builder->get('/users', ['prefix' => 'Api', 'controller' => 'Users', 'action' => 'list']);
            $builder->get('/user', ['prefix' => 'Api', 'controller' => 'Users', 'action' => 'get']);
            $builder->patch('/user', ['prefix' => 'Api', 'controller' => 'Users', 'action' => 'edit']);

            $builder->get('/user/profile', ['prefix' => 'Api', 'controller' => 'Users', 'action' => 'profile']);

            $builder->get('/shop/products', ['prefix' => 'Api', 'controller' => 'Shop', 'action' => 'products']);
            $builder->post('/shop/purchase', ['prefix' => 'Api', 'controller' => 'Shop', 'action' => 'purchase']);

            $builder->get('/collection', ['prefix' => 'Api', 'controller' => 'Collection', 'action' => 'get']);
        });
    });

    $serviceAuthService = new AuthenticationService();
    $serviceAuthService->loadAuthenticator('Authentication.Token', [
        'header' => 'Authorization',
    ]);
    $serviceAuthService->loadIdentifier('Potal');
    $routes->registerMiddleware('service-auth', new AuthenticationMiddleware($serviceAuthService));


    $routes->scope('/', function (RouteBuilder $builder): void {
        $builder->applyMiddleware('service-auth');

        $builder->connect('/events', ['controller' => 'Events', 'action' => 'index']);
    });
};
