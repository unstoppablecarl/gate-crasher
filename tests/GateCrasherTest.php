<?php

namespace UnstoppableCarl\GateCrasher\Tests;

use Illuminate\Auth\Access\Gate;
use Illuminate\Auth\GenericUser;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Authenticatable;
use UnstoppableCarl\GateCrasher\GateCrasher;
use PHPUnit\Framework\TestCase;

class GateCrasherTest extends TestCase
{
    protected function freshUser($role, $id = 1)
    {
        $user = new GenericUser([
            'id'   => $id,
            'role' => $role,
        ]);

        $this->assertTrue($user instanceof Authenticatable, 'Generic user is valid');

        return $user;
    }

    protected function freshGate(
        array $contextDefaults = [],
        array $abilityOverrides = [],
        array $definedAbilities = []
    ) {
        $container = new Container();

        $userResolver = function () {
        };

        $gate = new Gate($container, $userResolver);

        foreach ($definedAbilities as $key => $value) {
            $gate->define($key, $value);
        }

        $checker = function ($user) {
            return $user->role === 'superuser';
        };

        $gateBefore = new GateCrasher($checker, $contextDefaults, $abilityOverrides);

        $gateBefore->register($gate);

        return $gate;
    }

    protected function makeExpectedCallbackThatReturns($value)
    {
        $callback = $this->getMockBuilder(\stdClass::class)
                         ->setMethods(['__invoke'])
                         ->getMock();

        $callback->expects($this->once())
                 ->method('__invoke')
                 ->willReturn($value);

        return $callback;
    }

    public function testDefaultSuperUserBehavior()
    {
        $superuser = $this->freshUser('superuser', 1);
        $admin     = $this->freshUser('admin', 3);

        $gate    = $this->freshGate()->forUser($superuser);
        $ability = 'foo';

        $msg      = 'By default: Super User is allowed to target non-model ability';
        $expected = true;
        $actual   = $gate->allows($ability);
        $this->assertEquals($expected, $actual, $msg);

        $msg      = 'By Default: Super User is allowed to target Non-Super User';
        $expected = true;
        $this->assertSuperUserTargetingNonSuperUserEquals($gate, $ability, $expected, $msg);

        $msg      = 'By default: Super User is not allowed to target self';
        $expected = false;
        $this->assertSuperUserTargetingSelfEquals($gate, $ability, $expected, $msg);

        $msg      = 'By default: Super User is not allowed to target Super User';
        $expected = false;
        $this->assertSuperUserTargetingSuperUserEquals($gate, $ability, $expected, $msg);

        $msg      = 'By default: Non-Super User is not allowed to target Super User';
        $expected = false;
        $this->assertNonSuperUserTargetingSuperUserEquals($gate, $ability, $expected, $msg);

        $gate     = $gate->forUser($admin);
        $msg      = 'By default: non-Super User is not allowed to target non-model ability';
        $expected = false;
        $actual   = $gate->allows($ability);
        $this->assertEquals($expected, $actual, $msg);
    }

    public function contextDefaultsProvider()
    {
        return [
            [
                [
                    GateCrasher::SUPER_USER__TARGETING__SELF           => true,
                    GateCrasher::SUPER_USER__TARGETING__SUPER_USER     => true,
                    GateCrasher::NON_SUPER_USER__TARGETING__SUPER_USER => true,
                ],
            ],
            [
                [
                    GateCrasher::SUPER_USER__TARGETING__SELF           => false,
                    GateCrasher::SUPER_USER__TARGETING__SUPER_USER     => false,
                    GateCrasher::NON_SUPER_USER__TARGETING__SUPER_USER => false
                    ,
                ],

            ],
            [
                [
                    GateCrasher::SUPER_USER__TARGETING__SELF           => true,
                    GateCrasher::SUPER_USER__TARGETING__SUPER_USER     => false,
                    GateCrasher::NON_SUPER_USER__TARGETING__SUPER_USER => true,
                ],
            ],
        ];
    }

    /**
     * @dataProvider contextDefaultsProvider
     * @param array $contextDefaults
     */
    public function testContextDefaults(array $contextDefaults)
    {
        $superuser = $this->freshUser('superuser', 1);
        $admin     = $this->freshUser('admin', 3);

        $gate = $this->freshGate($contextDefaults)->forUser($superuser);

        $key         = GateCrasher::SUPER_USER__TARGETING__SELF;
        $expected    = $contextDefaults[$key];
        $expectedStr = $expected ? 'true' : 'false';
        $msg         = 'Can use context default ' . $key . ' with ' . $expectedStr;
        $this->assertSuperUserTargetingSelfEquals($gate, 'foo', $expected, $msg);

        $key         = GateCrasher::SUPER_USER__TARGETING__SUPER_USER;
        $expected    = $contextDefaults[$key];
        $expectedStr = $expected ? 'true' : 'false';
        $msg         = 'Can use context default ' . $key . ' with ' . $expectedStr;
        $this->assertSuperUserTargetingSuperUserEquals($gate, 'foo', $expected, $msg);

        $gate = $gate->forUser($admin);

        $key         = GateCrasher::NON_SUPER_USER__TARGETING__SUPER_USER;
        $expected    = $contextDefaults[$key];
        $expectedStr = $expected ? 'true' : 'false';
        $msg         = 'Can use context default ' . $key . ' with ' . $expectedStr;
        $this->assertNonSuperUserTargetingSuperUserEquals($gate, 'foo', $expected, $msg);
    }

    protected function assertSuperUserTargetingSelfEquals(Gate $gate, $ability, $expected, $msg = null)
    {
        $user   = $this->freshUser('superuser', 1);
        $gate   = $gate->forUser($user);
        $actual = $gate->allows($ability, $user);
        $this->assertEquals($expected, $actual, $msg);
    }

    protected function assertSuperUserTargetingSuperUserEquals(Gate $gate, $ability, $expected, $msg = null)
    {
        $user   = $this->freshUser('superuser', 1);
        $user2  = $this->freshUser('superuser', 2);
        $gate   = $gate->forUser($user);
        $actual = $gate->allows($ability, $user2);

        $this->assertEquals($expected, $actual, $msg);
    }

    protected function assertNonSuperUserTargetingSuperUserEquals(Gate $gate, $ability, $expected, $msg = null)
    {
        $user   = $this->freshUser('admin', 1);
        $user2  = $this->freshUser('superuser', 2);
        $gate   = $gate->forUser($user);
        $actual = $gate->allows($ability, $user2);

        $this->assertEquals($expected, $actual, $msg);
    }

    protected function assertSuperUserTargetingNonSuperUserEquals(Gate $gate, $ability, $expected, $msg)
    {
        $user   = $this->freshUser('superuser', 1);
        $gate   = $gate->forUser($user);
        $actual = $gate->allows($ability);

        $this->assertEquals($expected, $actual, $msg);
    }

    public function fallbacksProvider($key)
    {
        $ability = 'foo';
        $fail    = function () use ($key) {
            $msg = $key . ', should not call gate defined ability when ability override or context default present';
            $this->fail($msg);
        };

        $baseCase = [
            'key'     => $key,
            'ability' => $ability,
        ];

        $cases = [
            [
                'msg'      => 'uses true ability override, ignores false context default',
                'expected' => true,

                'ability_override'     => true,
                'context_default'      => false,
                'gate_defined_ability' => $fail,
            ],
            [
                'key'      => $key,
                'ability'  => $ability,
                'msg'      => 'uses false ability override, ignores true context default',
                'expected' => false,

                'ability_override'     => false,
                'context_default'      => true,
                'gate_defined_ability' => $fail,
            ],
            [
                'key'      => $key,
                'ability'  => $ability,
                'msg'      => 'ignores null ability override, uses true context default',
                'expected' => true,

                'ability_override'     => null,
                'context_default'      => true,
                'gate_defined_ability' => $fail,
            ],
            [
                'key'      => $key,
                'ability'  => $ability,
                'msg'      => 'ignores null ability override, uses false context default',
                'expected' => false,

                'ability_override'     => null,
                'context_default'      => false,
                'gate_defined_ability' => $fail,
            ],
            [
                'key'      => $key,
                'ability'  => $ability,
                'msg'      => 'ignores not set ability override, uses true context default',
                'expected' => true,

                'context_default'      => true,
                'gate_defined_ability' => $fail,
            ],
            [
                'key'      => $key,
                'ability'  => $ability,
                'msg'      => 'ignores not set ability override, uses false context default',
                'expected' => false,

                'context_default'      => false,
                'gate_defined_ability' => $fail,
            ],
            [
                'key'      => $key,
                'ability'  => $ability,
                'msg'      => 'ignores not set ability override, 
                ignores not set context default, uses true gate ability',
                'expected' => true,

                'gate_defined_ability' => $this->makeExpectedCallbackThatReturns(true),
            ],
            [
                'key'      => $key,
                'ability'  => $ability,
                'msg'      => 'ignores not set ability override, 
                ignores not set context default, uses false gate ability',
                'expected' => false,

                'gate_defined_ability' => $this->makeExpectedCallbackThatReturns(false),
            ],
        ];

        return collect($cases)
            ->map(function ($case) use ($baseCase) {
                $case = array_merge($baseCase, $case);
                return [$case];
            })
            ->toArray();
    }

    public function fallbacksCaseProvider()
    {
        $keys = [
            GateCrasher::SUPER_USER__TARGETING__SELF,
            GateCrasher::SUPER_USER__TARGETING__SUPER_USER,
            GateCrasher::NON_SUPER_USER__TARGETING__SUPER_USER,
        ];

        $out = [];
        foreach ($keys as $key) {
            $cases = $this->fallbacksProvider($key);
            $out   = array_merge($cases);
        }
        return $out;
    }

    /**
     * @dataProvider fallbacksCaseProvider
     * @param array $case
     */
    public function testFallbacks(array $case)
    {
        $key      = $case['key'];
        $ability  = $case['ability'];
        $expected = $case['expected'];
        $msg      = $case['msg'];

        $gate = $this->gateCascadeFactory($key, $ability, $case);

        $msg = $key . ',' . $msg;
        if ($key === GateCrasher::SUPER_USER__TARGETING__SELF) {
            $this->assertSuperUserTargetingSelfEquals($gate, $ability, $expected, $msg);
        } elseif ($key === GateCrasher::SUPER_USER__TARGETING__SUPER_USER) {
            $this->assertSuperUserTargetingSuperUserEquals($gate, $ability, $expected, $msg);
        } elseif ($key === GateCrasher::NON_SUPER_USER__TARGETING__SUPER_USER) {
            $this->assertNonSuperUserTargetingSuperUserEquals($gate, $ability, $expected, $msg);
        }
    }

    public function gateCascadeFactory($key, $ability, array $args)
    {
        $contextDefaults  = [];
        $abilityOverrides = [];

        if (array_key_exists('context_default', $args)) {
            $contextDefaults[$key] = $args['context_default'];
        }

        if (array_key_exists('ability_override', $args)) {
            $abilityOverrides[$key][$ability] = $args['ability_override'];
        }

        $superuser = $this->freshUser('superuser');
        $gate      = $this->freshGate($contextDefaults, $abilityOverrides)->forUser($superuser);


        $gateDefinedAbilityValue = array_get($args, 'gate_defined_ability');

        if ($gateDefinedAbilityValue) {
            $gate->define($ability, $gateDefinedAbilityValue);
        }

        return $gate;
    }
}
