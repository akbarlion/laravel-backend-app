<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    //
    protected $fillable = ['name', 'production_price', 'selling_price'];

    public function orderItems()
    {
        return $this->hasMany(SalesOrderItem::class);
    }
}
