<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Вход в веб-панель из .env (без таблицы users)
    |--------------------------------------------------------------------------
    |
    | Логин: поле username в форме сверяется с ADMIN_USERNAME.
    | Пароль: если задан ADMIN_PASSWORD_BCRYPT — проверка через password_verify();
    | иначе используется ADMIN_PASSWORD (только для разработки).
    |
    */

    'session_key' => 'admin_env_authenticated',

    'username' => env('ADMIN_USERNAME'),

    'password_bcrypt' => env('ADMIN_PASSWORD_BCRYPT'),

    'password_plain' => env('ADMIN_PASSWORD'),

];
