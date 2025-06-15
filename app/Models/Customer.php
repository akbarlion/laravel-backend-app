<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    //
    protected $fillable = ['name', 'address', 'phone'];

    public function orders()
    {
        return $this->hasMany(SalesOrder::class, 'customer_id');
    }
}
