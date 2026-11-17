<?php

$node = 'D:\Project\laragon\bin\nodejs\node-v18\node.exe';

$entry = 'D:\Project\web-base\sisteminformasipesilat\vendor\kstmostofa\laravel-whatsapp\sidecar\index.js';

$cwd = 'D:\Project\web-base\sisteminformasipesilat\vendor\kstmostofa\laravel-whatsapp\sidecar';

$descriptors = [
    0 => ['file', 'NUL', 'r'],
    1 => ['file', 'test-node.log', 'a'],
    2 => ['file', 'test-node-error.log', 'a'],
];

$pipes = [];

$proc = proc_open(
    [$node, $entry],
    $descriptors,
    $pipes,
    $cwd,
    getenv(),
    [
        'bypass_shell' => true,
        'create_new_console' => true,
    ],
);

var_dump($proc);

if (is_resource($proc)) {
    var_dump(proc_get_status($proc));
}