# Gate Crasher
Safer Laravel superuser auth.

[![Source Code][badge-source]][source]
[![Latest Version][badge-release]][release]
[![Software License][badge-license]][license]
[![Build Status][badge-build]][build]
[![Coverage Status][badge-coverage]][coverage]
[![Total Downloads][badge-downloads]][downloads]

## About

Gate Crasher leverages the Laravel `Gate::before($beforeCallback)` api to authorize superuser abilities skipping the normal Gate ability/policy functionality.
When `Gate::allows()` is called, If the `$beforeCallback` returns a non-null result that result will be considered the result of the check.

See [https://laravel.com/docs/5.2/authorization](https://laravel.com/docs/5.2/authorization)

`Illuminate\Auth\Access\Gate::before()`
See `Illuminate\Contracts\Auth\Access\Gate::before()` in the Laravel framework.

## Requirements

 - PHP >= 5.5.9
 - Laravel >= 5.2

## Installation

The preferred method of installation is via [Packagist][] and [Composer][]. 
Run the following command to install the package and add it as a requirement to your project's `composer.json`:

```bash
composer require unstoppablecarl/gate-crasher
```

## Usage

A Gate Crasher instance should be registered within the `boot()` method of a service provider.

### Minimal gate crasher setup

```php
<?php
use Gate;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Support\ServiceProvider;
use UnstoppableCarl\GateCrasher\GateCrasher;

class GateCrasherServiceProvider extends ServiceProvider
{
    public function boot(GateContract $gate)
    {
        // define a way to identify super users
        $superUserChecker = function ($user) {
            return $user->isSuperUser();
        };

        $gateCrasher = new GateCrasher($superUserChecker);
        
        $beforeCallback = function ($user, $ability, $args) use ($gateCrasher) {
            return $gateCrasher->before($user, $ability, $args);
        };
        
        // get Gate instance via boot() dependency injection or app()
        $gate = app(GateContract::class);
        
        // set before callback to a Gate instance
        $gate->before($beforeCallback);
        
        // set before callback using facade
        Gate::before($beforeCallback);
        
        // PRO TIP: use `GateCrasher::register()` to create and set the before callback on a Gate instance for you
        $gateCrasher->register($gate);
    }
}
```

The default configuration of a Gate Crasher instance creates the following behavior:
 
```php
<?php
// login a super user
$gate = Gate::withUser($mySuperUser);

// allows ALL non-policy abilities
$gate->allows('foo'); // true

// allows ALL non-Super User policy abilities
$gate->allows('update', $someUser); // true

// denies abilities that target self
$gate->allows('delete', $mySuperUser);

// denies abilities that target other Super Users (even when logged in as a Super User)
$gate->allows('delete', $someOtherSuperUser);

```

### Configuring Abilities

```php
<?php
use UnstoppableCarl\GateCrasher\GateCrasher;

$superUserChecker = function ($user) {
    return $user->isSuperUser();
};

// the default result of any ability checks in the given context
$contextDefaults = [
    // ignored if null falls back to default Gate::allows() check
    GateCrasher::SUPER_USER__TARGETING__SELF           => null,

    // deny all abilities from non-super users targeting super users
    GateCrasher::SUPER_USER__TARGETING__SUPER_USER     => false,

    // deny all abilities from non-super users targeting super users
    GateCrasher::NON_SUPER_USER__TARGETING__SUPER_USER => false,
];

// the result of specific ability checks in the given context
$abilityOverrides = [
    // super users can always update but never delete themselves
    GateCrasher::SUPER_USER__TARGETING__SELF           => [
        'update' => true,
        'delete' => false,
        // ...
    ],
    // super users can never update or delete other super users
    GateCrasher::SUPER_USER__TARGETING__SUPER_USER     => [
        'update' => false,
        'delete' => false,
        // ...
    ],
    // non-super users can never update or delete other super users
    GateCrasher::NON_SUPER_USER__TARGETING__SUPER_USER => [
        // ignored if null, falls back to $contextDefaults then to Gate::allows() check
        'view'   => null,
        'update' => false,
        'delete' => false,
        // ...
    ],
];

$gateCrasher = new GateCrasher($superUserChecker, $contextDefaults, $abilityOverrides);
use Illuminate\Contracts\Auth\Access\Gate as GateContract;

$gate = app(GateContract::class);
$gateCrasher->register($gate);

```
## Examples

## How It Works

This is an abstract description of how Gate Crasher works. See the source code for exact details.

```php
<?php
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

Gate::allows('foo', $target);

$targetIsUser = $target instanceof AuthenticatableContract;

// allow ANY non-Super User target
// (target can be a User, another Model, or any other value)
if($sourceIsSuperUser && !$targetIsSuperUser){
    return true;
}

// determine the context
if($sourceIsSuperUser && $targetIsSuperUser){
    $context = GateCrasher::SUPER_USER__TARGETING__SUPER_USER;
}
else if($sourceIsSuperUser && $targetIsSelf){
    $context = GateCrasher::SUPER_USER__TARGETING__SELF;
}
else if(!$sourceIsSuperUser && $targetIsSuperUser){
    $context = GateCrasher::NON_SUPER_USER__TARGETING__SUPER_USER;
}

// check for non-null ability override
$abilityOverrideValue = $abilityOverrides[$context]['foo'];
if ($abilityOverrideValue !== null) {
    return $abilityOverrideValue;
}

// check for non-null context default
$contextDefaultValue = $contextDefaults[$context];
if($contextDefaultValue !== null){
    return $contextDefaultValue;
}

// DEFAULT GATE BEHAVIOR

// if a policy is registered for $target use the policy
// if an ability callback is registered for 'foo' use the registered callback
// return false;
```

## Running the tests

Run Unit Tests

```
$ composer phpunit
```

Run Codesniffer (psr-2)
```
$ composer phpcs
```

Run both

```
$ composer test
```

## Contributing

Contributions and Pull Requests welcome!

Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on our code of conduct, and the process for submitting pull requests to us.
 
## Authors

* **Carl Olsen** - *Initial work* - [Unstoppable Carl](https://github.com/unstoppablecarl)

See also the list of [contributors][] who participated in this project.

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details

[composer]: http://getcomposer.org/
[contributors]: https://github.com/unstoppablecarl/gate-crasher/contributors

[badge-source]: https://img.shields.io/badge/source-unstoppablecarl/gate-crasher-blue.svg?style=flat-square
[badge-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[badge-build]: https://img.shields.io/travis/unstoppablecarl/gate-crasher/master.svg?style=flat-square
[badge-coverage]: https://img.shields.io/coveralls/unstoppablecarl/gate-crasher/master.svg?style=flat-square
[badge-downloads]: https://img.shields.io/packagist/dt/unstoppablecarl/gate-crasher.svg?style=flat-square