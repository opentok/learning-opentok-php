<?php

use OpenTok\OpenTok;
use ICanBoogie\Storage\FileStorage;
use Psr\Container\ContainerInterface;

// Verify that the API Key and API Secret are defined
if (!(getenv('TOKBOX_API_KEY') && getenv('TOKBOX_SECRET'))) {
    die('You must define an TOKBOX_API_KEY and TOKBOX_SECRET');
}

return [
    'config' => [
        'tokbox' => [
            'api_key' => getenv('TOKBOX_API_KEY'),
            'secret' => getenv('TOKBOX_SECRET'),
        ],
        'views_dir' =>__DIR__ . '/../templates',
        'storage_dir' => __DIR__ . '/../storage'
    ],

    // IMPORTANT: storage is a variable that associates room names with unique unique sesssion IDs. 
    // For simplicty, we use a extension called FileStorage to implement this logic.
    // Generally speaking, a production application chooses a database system like MySQL, MongoDB, or Redis etc.
    // The FileStorage transforms into a file where the name is a room name and its value is session ID.
    'storage' => DI\Factory(function(ContainerInterface $c) {
        return new FileStorage($c->get('config')['storage_dir']);
    }),

    OpenTok::class => function (ContainerInterface $c) {
        $tokboxConfig = $c->get('config')['tokbox'];
        return new OpenTok($tokboxConfig['api_key'], $tokboxConfig['secret']);
    }
];
