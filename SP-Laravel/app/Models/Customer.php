<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'id',
        'name',
        'email',
        'phone',
    ];


    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id', 'id');
    }
}
