<?php

namespace UnstoppableCarl\GateCrasherExamples;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Support\ServiceProvider;
use UnstoppableCarl\GateCrasher\GateCrasher;
use Illuminate\Config\Repository;

class GateCrasherServiceProvider extends ServiceProvider
{
    protected $configFilePath = __DIR__ . '/gate-crasher.php';

    /**
     * @var Repository
     */
    protected $config;

    public function boot(Gate $gate)
    {
        $config = $this->config;

        $superUserChecker = $config->get('super_user_checker', $this->superUserChecker());
        $contextDefaults  = $config->get('context_defaults', $this->contextDefaults());
        $abilityOverrides = $config->get('ability_overrides', $this->abilityOverrides());

        $gateCrasher = new GateCrasher($superUserChecker, $contextDefaults, $abilityOverrides);
        $gateCrasher->register($gate);
    }

    public function register()
    {
        $this->config = $this->registerConfig($this->configFilePath);
    }

    /**
     * @return \Closure
     */
    protected function superUserChecker()
    {
        return function ($user) {
            //@TODO check for superuser
            return false;
        };
    }

    /**
     * @return array
     */
    protected function contextDefaults()
    {
        return [
            GateCrasher::SUPER_USER__TARGETING__SELF           => null,
            GateCrasher::SUPER_USER__TARGETING__SUPER_USER     => null,
            GateCrasher::NON_SUPER_USER__TARGETING__SUPER_USER => null,
        ];
    }

    /**
     * @return array
     */
    protected function abilityOverrides()
    {
        return [
            GateCrasher::SUPER_USER__TARGETING__SELF           => [
                'update' => null,
                'delete' => null,
            ],
            GateCrasher::SUPER_USER__TARGETING__SUPER_USER     => [
                'update' => null,
                'delete' => null,
            ],
            GateCrasher::NON_SUPER_USER__TARGETING__SUPER_USER => [
                'update' => null,
                'delete' => null,
            ],
        ];
    }

    protected function registerConfig($filePath = null)
    {
        $fileName = basename($filePath);
        $key      = basename($fileName, '.php');

        $this->publishes([
            $filePath => config_path($fileName),
        ]);

        $this->mergeConfigFrom($filePath, $key);
        $config = $this->app['config']->get($key);

        return new Repository($config);
    }
}
