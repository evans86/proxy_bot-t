<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Вход в веб-панель из .env (без таблицы users)
    |--------------------------------------------------------------------------
    |
    | Логин: поле username в форме сверяется с ADMIN_USERNAME.
    | Пароль: ADMIN_PASSWORD_BCRYPT — через password_verify().
    | Иначе ADMIN_PASSWORD: если значение похоже на bcrypt ($2y$…), тоже password_verify(); иначе — открытый текст (только dev).
    | В .env хеш с $ лучше задавать в ADMIN_PASSWORD_BCRYPT или в кавычках: ADMIN_PASSWORD="$2y$10$..."
    |
    */

    'session_key' => 'admin_env_authenticated',

    'username' => env('ADMIN_USERNAME'),

    'password_bcrypt' => env('ADMIN_PASSWORD_BCRYPT'),

    'password_plain' => env('ADMIN_PASSWORD'),

];
