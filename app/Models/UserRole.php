<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRole extends Model
{
    //
    protected $fillable = ['name'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

}
