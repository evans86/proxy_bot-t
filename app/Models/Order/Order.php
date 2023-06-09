<?php

namespace App\Models\Order;

use App\Models\Country\Country;
use App\Models\Proxy\Proxy;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    const ORDER_DELETE = 0;
    const ORDER_ACTIVE = 1;
    const ORDER_FINISH = 2;

    use HasFactory;

    protected $guarded = false;
    protected $table = 'order';

    public function bot()
    {
        return $this->hasOne(User::class, 'id', 'bot_id');
    }

    public function country()
    {
        return $this->hasOne(Country::class, 'id', 'country_id');
    }

    public function proxy()
    {
        return $this->hasOne(Proxy::class, 'id', 'proxy_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
