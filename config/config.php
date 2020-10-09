<?php

return [

    /*
    |--------------------------------------------------------------------------
    | User database config.
    |--------------------------------------------------------------------------
     */
    'database' => [

        /*
        |--------------------------------------------------------------------------
        | User model class.
        |--------------------------------------------------------------------------
        */
        'model' => \App\Models\User::class,

        /*
        |--------------------------------------------------------------------------
        | User column name using for foreign key.
        |--------------------------------------------------------------------------
        */
        'id'    => 'id',

        /*
        |--------------------------------------------------------------------------
        | User column type using for foreign key. (Laravel migration type)
        |--------------------------------------------------------------------------
        */
        'type'  => 'unsignedBigInteger',

        /*
        |--------------------------------------------------------------------------
        | Format using to show users. BelongsToWidget using for formating data.
        |--------------------------------------------------------------------------
        */
        'format' => '%id. %name',

        /*
        |--------------------------------------------------------------------------
        | Extra users database fields that showing on ticket page.
        |--------------------------------------------------------------------------
        |
        | Format: 'data base column' => 'Show label in ticket panel'
        |
        */
        'fields' => [
            // 'email' => 'Email',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Place to hold ticket files.
    |--------------------------------------------------------------------------
    */
    'files' => [

        /*
        |--------------------------------------------------------------------------
        | Disk using to save ticket files.
        |--------------------------------------------------------------------------
        */
        'disk' => 'public',

        /*
        |--------------------------------------------------------------------------
        | Path of tickets. You can use `{TICKET_ID}` to make seperate directory for each ticket.
        |--------------------------------------------------------------------------
        */
        'directory' => 'tickets/{TICKET_ID}'
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications.
    |--------------------------------------------------------------------------
    */
    'notifications' => [

        /*
        |--------------------------------------------------------------------------
        | Notification for new ticket messages.
        |--------------------------------------------------------------------------
        */
        'new_ticket' => [

            /*
            |--------------------------------------------------------------------------
            | New ticket message notification for all admins that has permissions to ticketing.
            |--------------------------------------------------------------------------
            */
            'admin' => null,

            /*
            |--------------------------------------------------------------------------
            | New ticket message notification for client that created ticket.
            |--------------------------------------------------------------------------
            */
            'client' => null,
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Models table names.
    |--------------------------------------------------------------------------
    */
    'tables' => [
        'tickets'           => 'sanjab_tickets',
        'ticket_categories' => 'sanjab_ticket_categories',
        'ticket_messages'   => 'sanjab_ticket_messages',
        'ticket_priorities' => 'sanjab_ticket_priorities',
    ],
];
