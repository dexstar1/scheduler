<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    //
    protected $fillable = ['fullName', 'email', 'authCode', 
                            'due_date', 'billable', 'due_amount', 
                            'charge_status'
                            ];

    public function transactions()
    {
        return $this->hasMany('App\Transaction');
    }
}
