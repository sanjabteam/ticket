<?php

/*
 * You can place your custom package configuration in here.
 */
return [
    'database' => [
        'model' => \App\User::class,
        'id'    => 'id',
        'type'  => 'unsignedBigInteger',
    ],

    'user' => [
        'format' => '%id. %first_name %last_name'
    ],

    'files' => [
        'disk' => 'public',
        'directory' => 'tickets/{TICKET_ID}'
    ],

    'fields' => [
        'mobile' => 'شماره همراه',
    ],
];
