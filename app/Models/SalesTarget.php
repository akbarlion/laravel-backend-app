<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesTarget extends Model
{
    //
    protected $fillable = ['active_date', 'amount', 'sales_id'];

    public function sales()
    {
        return $this->belongsTo(Sale::class);
    }

}
