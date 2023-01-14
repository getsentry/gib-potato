<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     3.3.0
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App;

use App\Middleware\SentryMiddleware;
use App\Middleware\SentryUserMiddleware;
use Authentication\AuthenticationService;
use Authentication\AuthenticationServiceInterface;
use Authentication\AuthenticationServiceProviderInterface;
use Authentication\Middleware\AuthenticationMiddleware;
use Cake\Core\Configure;
use Cake\Core\ContainerInterface;
use Cake\Datasource\FactoryLocator;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\BaseApplication;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Http\Middleware\CspMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\ORM\Locator\TableLocator;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;
use Cake\Routing\Router;
use ParagonIE\CSPBuilder\CSPBuilder;
use Psr\Http\Message\ServerRequestInterface;
use Sentry\SentrySdk;

/**
 * Application setup class.
 *
 * This defines the bootstrapping logic and middleware layers you
 * want to use in your application.
 */
class Application extends BaseApplication implements AuthenticationServiceProviderInterface
{
    /**
     * Load all the application configuration and bootstrap logic.
     *
     * @return void
     */
    public function bootstrap(): void
    {
        // Call parent to load bootstrap from files.
        parent::bootstrap();

        if (PHP_SAPI === 'cli') {
            $this->bootstrapCli();
        } else {
            FactoryLocator::add(
                'Table',
                (new TableLocator())->allowFallbackClass(false)
            );
        }

        /*
         * Only try to load DebugKit in development mode
         * Debug Kit should not be installed on a production system
         */
        if (Configure::read('debug')) {
            /**
             * We have to use forceEnable to be able to work with
             * ngrok hosts, like gipotato.eu.ngrok.io
             */
            Configure::write('DebugKit.forceEnable', true);
            $this->addPlugin('DebugKit', [
                'forceEnable' => true,
            ]);
        }

        $this->addPlugin('Authentication');
    }

    /**
     * Setup the middleware queue your application will use.
     *
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue to setup.
     * @return \Cake\Http\MiddlewareQueue The updated middleware queue.
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        $middlewareQueue
             ->add(new SentryMiddleware())
            // Catch any exceptions in the lower layers,
            // and make an error page/response
            ->add(new ErrorHandlerMiddleware(Configure::read('Error')))

            // Handle plugin/theme assets like CakePHP normally does.
            ->add(new AssetMiddleware([
                'cacheTime' => Configure::read('Asset.cacheTime'),
            ]))

            // Add routing middleware.
            // If you have a large number of routes connected, turning on routes
            // caching in production could improve performance. For that when
            // creating the middleware instance specify the cache config name by
            // using it's second constructor argument:
            // `new RoutingMiddleware($this, '_cake_routes_')`
            ->add(new RoutingMiddleware($this))

            // Parse various types of encoded request bodies so that they are
            // available as array through $request->getData()
            // https://book.cakephp.org/4/en/controllers/middleware.html#body-parser-middleware
            ->add(new BodyParserMiddleware())

            ->add(new CspMiddleware($this->getCspPolicy()))

            ->add(new AuthenticationMiddleware($this))

            ->add(new SentryUserMiddleware());

        return $middlewareQueue;
    }

    /**
     * Register application container services.
     *
     * @param \Cake\Core\ContainerInterface $container The Container to update.
     * @return void
     * @link https://book.cakephp.org/4/en/development/dependency-injection.html#dependency-injection
     */
    public function services(ContainerInterface $container): void
    {
    }

    /**
     * Bootstrapping for CLI application.
     *
     * That is when running commands.
     *
     * @return void
     */
    protected function bootstrapCli(): void
    {
        $this->addOptionalPlugin('Cake/Repl');
        $this->addOptionalPlugin('Bake');

        $this->addPlugin('Migrations');

        // Load more plugins here
    }

    public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface
    {
        $config = [
            'unauthenticatedRedirect' => Router::url([
                'prefix' => false,
                'plugin' => null,
                'controller' => 'Login',
                'action' => 'login',
            ]),
            'queryParam' => 'redirect',
        ];
        // Do not respond with a redirect in case an api token is provided
        if ($request->hasHeader('Authorization')) {
            $config = [];
        }
        $service = new AuthenticationService($config);

        $service->loadIdentifier('Authentication.Callback', [
            'callback' => function($data) {

                $token = $data['token'] ?? null;
                if ($token === env('API_TOKEN')) {
                    $usersTable = FactoryLocator::get('Table')->get('Users');

                    return $usersTable->get('c729751e-f8c1-4b40-aeb5-01ce39a62bd3');
                }
        
                return null;
            },
        ]);

        $service->loadAuthenticator('Authentication.Session');
        $service->loadAuthenticator('Authentication.Token', [
            'header' => 'Authorization',
            'tokenPrefix' => 'Bearer',
        ]);

        return $service;
    }

    protected function getCspPolicy()
    {
        $allow = [];
        if (Configure::read('debug')) {
            $allow = [
                'localhost:5173',
            ];
        }

        $csp = new CSPBuilder([
            'font-src' => ['self' => true],
            'form-action' => ['self' => true],
            'img-src' => ['self' => true, 'allow' => ['*.gravatar.com', '*.wp.com', '*.slack-edge.com']],
            'script-src' => ['self' => true, 'unsafe-inline' => true, 'allow' => $allow],
            'style-src' => ['self' => true, 'unsafe-inline' => true, 'allow' => $allow],
            'object-src' => [],
            'plugin-types' => [],
            'report-uri' => SentrySdk::getCurrentHub()->getClient()->getCspReportUrl(),
        ]);

        return $csp;
    }
}
