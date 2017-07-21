<?php

use UnstoppableCarl\GateCrasher\GateCrasher;

return [
    'super_user_checker' => function ($user) {
        return false;
    },
    'context_defaults'   => [
        GateCrasher::SUPER_USER__TARGETING__SELF           => null,
        GateCrasher::SUPER_USER__TARGETING__SUPER_USER     => null,
        GateCrasher::NON_SUPER_USER__TARGETING__SUPER_USER => null,
    ],
    'ability_overrides'  => [
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
    ],
];
