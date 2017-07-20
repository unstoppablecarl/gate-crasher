<?php

namespace UnstoppableCarl\GateCrasher;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Support\ServiceProvider;

class GateCrasherServiceProvider extends ServiceProvider
{

    /**
     * Register any authentication / authorization services.
     * @param Gate $gate
     * @return void
     */
    public function boot(Gate $gate)
    {
        $superUserChecker = $this->superUserChecker();
        $contextDefaults  = $this->contextDefaults();
        $abilityOverrides = $this->abilityOverrides();

        $gateCrasher = new GateCrasher($superUserChecker, $contextDefaults, $abilityOverrides);
        $gateCrasher->register($gate);
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
