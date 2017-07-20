<?php

namespace UnstoppableCarl\GateCrasher;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Illuminate\Contracts\Auth\Access\Gate;

class GateCrasher
{

    const SUPER_USER__TARGETING__SELF           = 'SUPER_USER__TARGETING__SELF';
    const SUPER_USER__TARGETING__SUPER_USER     = 'SUPER_USER__TARGETING__SUPER_USER';
    const NON_SUPER_USER__TARGETING__SUPER_USER = 'NON_SUPER_USER__TARGETING__SUPER_USER';

    /**
     * abilities to override the value of in specific cases.
     * @var array
     */
    protected $abilityOverrides = [];

    /**
     * @var Closure
     */
    protected $superUserChecker;

    /**
     * @var array
     */
    private $contextDefaults;

    /**
     * GateCrasher constructor.
     * @param Closure $superUserChecker
     * @param array $contextDefaults
     * @param array $abilityOverrides
     */
    public function __construct(Closure $superUserChecker, array $contextDefaults = [], array $abilityOverrides = [])
    {
        $this->superUserChecker = $superUserChecker;

        $keys = [
            static::SUPER_USER__TARGETING__SELF,
            static::SUPER_USER__TARGETING__SUPER_USER,
            static::NON_SUPER_USER__TARGETING__SUPER_USER,
        ];

        foreach ($keys as $context) {
            $contextDefaultValue = Arr::get($contextDefaults, $context, null);
            $overrides           = Arr::get($abilityOverrides, $context, []);

            $this->setContextDefault($context, $contextDefaultValue);
            $this->setOverrides($context, $overrides);
        }
    }

    /**
     * Register before handler with Gate.
     * @param Gate $gate
     */
    public function register(Gate $gate)
    {
        $gate->before(function ($user, $ability, $args) {
            return $this->before($user, $ability, $args);
        });
    }

    /**
     * Gate before handler method.
     * @param Authenticatable $source
     * @param string $ability
     * @param array $args
     * @return bool|null
     */
    public function before($source, $ability, array $args = [])
    {
        $target = $args ? $args[0] : false;

        $sourceIsSuperUser = $this->isSuperUser($source);
        $targetIsSuperUser = $this->isSuperUser($target);

        if ($sourceIsSuperUser) {
            if ($targetIsSuperUser) {
                if ($this->isSelf($source, $target)) {
                    return $this->checkSuperUserTargetingSelf($source, $ability, $args);
                }

                return $this->checkSuperUserTargetingSuperUser($source, $ability, $args);
            }

            return $this->checkSuperUserTargetingNonSuperUser($source, $ability, $args);
        }

        if ($targetIsSuperUser) {
            return $this->checkNonSuperUserTargetingSuperUser($source, $ability, $args);
        }

        return null;
    }

    /**
     * @param string $context
     * @param bool|null $value
     */
    protected function setContextDefault($context, $value)
    {
        $this->contextDefaults[$context] = $value;
    }

    /**
     * @param string $context
     * @param array $overrides
     */
    protected function setOverrides($context, array $overrides)
    {
        foreach ($overrides as $ability => $value) {
            $this->setOverride($context, $ability, $value);
        }
    }

    /**
     * @param string $context
     * @param string $ability
     * @param bool|null $value
     */
    protected function setOverride($context, $ability, $value)
    {
        $key = $context . '.' . $ability;
        Arr::set($this->abilityOverrides, $key, $value);
    }

    /**
     * @param string $context
     * @param string $ability
     * @return bool|null
     */
    protected function getOverride($context, $ability)
    {
        $key   = $context . '.' . $ability;
        $value = Arr::get($this->abilityOverrides, $key);
        if ($value !== null) {
            return $value;
        }
        $default = Arr::get($this->contextDefaults, $context, null);

        return $default;
    }

    /**
     * Check if $source and $target are the same user.
     * @param Authenticatable $source
     * @param Authenticatable $target
     * @return bool
     */
    protected function isSelf(Authenticatable $source, Authenticatable $target)
    {
        return $source->getAuthIdentifier() == $target->getAuthIdentifier();
    }

    /**
     * Check if $user is a super user.
     * The method can be extended to implement any desired super user identification method.
     * @param $user
     * @return bool
     */
    protected function isSuperUser($user)
    {
        if (!$this->isUser($user)) {
            return false;
        }

        $checker = $this->superUserChecker;

        return $checker($user);
    }

    /**
     * Check if $value should be treated as a valid user model.
     * @param mixed $value
     * @return bool
     */
    protected function isUser($value)
    {
        return $value instanceof Authenticatable;
    }

    /**
     * Handle super user behavior for non-superuser targets.
     * Default super user behavior.
     * @param Authenticatable $source
     * @param string $ability
     * @param array $args
     * @return bool|null
     */
    protected function checkSuperUserTargetingNonSuperUser(Authenticatable $source, $ability, array $args = [])
    {
        return true;
    }

    /**
     * Check for an ability override when a superUser is targeting self.
     * @param Authenticatable $source
     * @param string $ability
     * @param array $args
     * @return bool|null
     */
    protected function checkSuperUserTargetingSelf(Authenticatable $source, $ability, array $args = [])
    {
        return $this->getOverride(static::SUPER_USER__TARGETING__SELF, $ability);
    }

    /**
     * Check for an ability override when a superUser is targeting an other superUser.
     * @param Authenticatable $source
     * @param string $ability
     * @param array $args
     * @return bool|null
     */
    protected function checkSuperUserTargetingSuperUser(Authenticatable $source, $ability, array $args = [])
    {
        return $this->getOverride(static::SUPER_USER__TARGETING__SUPER_USER, $ability);
    }

    /**
     * Check for an ability override when a non-superUser is targeting a superUser.
     * @param Authenticatable $source
     * @param string $ability
     * @param array $args
     * @return bool|null
     */
    protected function checkNonSuperUserTargetingSuperUser(Authenticatable $source, $ability, array $args = [])
    {
        return $this->getOverride(static::NON_SUPER_USER__TARGETING__SUPER_USER, $ability);
    }
}
