<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'order_id', 'merchant_order_id', 'verify_order_id', 'amount',
        'ref_id','sale_order_id','sale_reference_id','status','raw_response'
    ];

    public function order()
    {
        return $this->belongsTo(\App\Models\Order::class);
    }
}
