<?php
    require('ElephantIO/Loader.php');
    require('config.php');

    use ElephantIO\Client;
    use ElephantIO\Engine\SocketIO\Version1X;

    $client = new Client(new Version1X("http://$host:$port"));

    $client->initialize();
    $client->of('/api/v.1.2');
    $client->emit('test', ['foo' => 'bar']);
    $client->close();
?>