<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Notification Email
    |--------------------------------------------------------------------------
    |
    | Address that receives a notification whenever a user signs up for a
    | course. The email is queued, so a queue worker must be running.
    |
    */

    'notification_email' => env('COURSES_NOTIFICATION_EMAIL', 'akreditovane.zkousky@mycomm.cz'),

    /*
    |--------------------------------------------------------------------------
    | Certificate Issuer
    |--------------------------------------------------------------------------
    |
    | Organization and signatory printed on the generated PDF certificates.
    |
    */

    'organization' => env('COURSES_ORGANIZATION', env('APP_NAME', 'Následné vzdělávání')),

    'signatory' => env('COURSES_SIGNATORY'),

    /*
    |--------------------------------------------------------------------------
    | Categories
    |--------------------------------------------------------------------------
    |
    | Categories an administrator can assign to a course. A course may have
    | several of them.
    |
    */

    'categories' => [
        'Obecné',
        'Životní pojištění',
        'Neživotní pojištění',
        'Spotřebitelské úvěry',
        'Investice',
    ],

];
