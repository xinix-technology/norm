<?php

/**
 * Norm - (not) ORM Framework
 *
 * MIT LICENSE
 *
 * Copyright (c) 2013 PT Sagara Xinix Solusitama
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @author      Ganesha <reekoheek@gmail.com>
 * @copyright   2013 PT Sagara Xinix Solusitama
 * @link        http://xinix.co.id/products/norm
 * @license     https://raw.github.com/xinix-technology/norm/master/LICENSE
 * @package     Norm
 *
 */
namespace Norm\Provider;

use Norm\Norm;

/**
 * Norm provider for Bono web application framework
 *
 * Expects connection configuration:
 *
 * <pre>
 * array (
 *     'norm.connections' => array (
 *         '{connectionName}' => array (
 *             'driver' => '{\\\\Connection\\\\Driver}',
 *             ...
 *         )
 *     )
 * )
 * </pre>
 *
 * example:
 *
 * <pre>
 * array (
 *     'norm.connections' => array (
 *         'mongo' => array (
 *             'driver' => 'Norm\\\\Connection\\\\MongoConnection',
 *             'database' => 'mydatabase'
 *         )
 *     )
 * )
 * </pre>
 *
 * Expects collection schema configuration:
 *
 * <pre>
 * array (
 *     'norm.collections' => array (
 *         'default' => array (
 *             // ...
 *         ),
 *         'mapping' => array (
 *             '{ModuleName}' => array (
 *                 'schema' => array (
 *                     '{field1}' => // ...
 *                 )
 *             ),
 *             // ...
 *         )
 *     )
 * )
 * </pre>
 */
class NormProvider extends \Bono\Provider\Provider
{
    /**
     * Initialize the provider
     */
    public function initialize()
    {
        $app = $this->app;

        $include = $app->request->get('!include');
        if (!empty($include)) {
            Norm::options('include', true);
        }

        $tz = $app->request->get('!tz');
        if (!empty($tz)) {
            Norm::options('tz', $tz);
        }

        if (!isset($this->options['datasources'])) {
            $this->options['datasources'] = $this->app->config('norm.datasources');
        }

        // DEPRECATED: norm.databases deprecated
        if (!isset($this->options['datasources'])) {
            $this->options['datasources'] = $this->app->config('norm.databases');
        }

        if (!isset($this->options['datasources'])) {
            throw new \Exception('[Norm] No data source configuration. Append "norm.datasources" bono configuration!');
        }

        if (!isset($this->options['collections'])) {
            $this->options['collections'] = $this->app->config('norm.collections');
        }

        Norm::init($this->options['datasources'], $this->options['collections']);

        $controllerConfig = $this->app->config('bono.controllers');
        if (!isset($controllerConfig['default'])) {
            $controllerConfig['default'] = 'Norm\\Controller\\NormController';
        }
        $this->app->config('bono.controllers', $controllerConfig);

        if (! class_exists('Norm')) {
            class_alias('Norm\\Norm', 'Norm');
        }

        $d = explode(DIRECTORY_SEPARATOR.'src', __DIR__);
        $this->app->theme->addBaseDirectory($d[0], 10);
    }
}
