<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    //
    protected $fillable = ['amount', 'reference', 'customer_id', 'status'];

    public function customer()
    {
        return $this->belongsTo('App\Customer');
    }
}
