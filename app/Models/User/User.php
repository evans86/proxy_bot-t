<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    const LANGUAGE_RU = 'ru';
    const LANGUAGE_ENG = 'eng';

    use HasFactory;

    protected $table = 'user';

    protected $fillable = [
        'telegram_id',
        'language',
    ];
}
