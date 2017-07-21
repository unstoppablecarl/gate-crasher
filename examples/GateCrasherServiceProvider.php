<?php

namespace UnstoppableCarl\GateCrasherExamples;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Support\ServiceProvider;
use UnstoppableCarl\GateCrasher\GateCrasher;
use UnstoppableCarl\GateCrasherExamples\Concerns\ProviderHasConfig;

class GateCrasherServiceProvider extends ServiceProvider
{
    use ProviderHasConfig;

    protected $configFilePath = __DIR__ . '/gate-crasher.php';

    /**
     * Register any authentication / authorization services.
     * @param Gate $gate
     * @return void
     */
    public function boot(Gate $gate)
    {
        $superUserChecker = $this->config('super_user_checker', $this->superUserChecker());
        $contextDefaults  = $this->config('context_defaults', $this->contextDefaults());
        $abilityOverrides = $this->config('ability_overrides', $this->abilityOverrides());

        $gateCrasher = new GateCrasher($superUserChecker, $contextDefaults, $abilityOverrides);
        $gateCrasher->register($gate);
    }

    public function register()
    {
        $this->registerConfig();
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
}
