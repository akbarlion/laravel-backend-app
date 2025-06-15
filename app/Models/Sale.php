<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    //
    protected $fillable = ['user_id', 'area_id'];


    public function users()
    {
        return $this->belongsTo(User::class);
    }

    public function area()
    {
        return $this->belongsTo(SalesArea::class, 'area_id');
    }

    public function orders()
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function target()
    {
        return $this->hasMany(SalesTarget::class);
    }

}
