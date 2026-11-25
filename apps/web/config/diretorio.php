<?php

return [

    /*
    | Caminho do seed mascarado do v2. Fica fora do repositório de propósito;
    | sem o arquivo, o DiretorioSeeder cria um conjunto sintético.
    */
    'seed_json' => env('SEED_JSON'),

    'admin' => [
        'email' => env('ADMIN_EMAIL', 'admin@diretorio.test'),
        'senha' => env('ADMIN_SENHA'),
    ],

];
