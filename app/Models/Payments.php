<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payments extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_type',
        'user_id',
        'amount',
        'payment_date',
        'next_renew_date',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'next_renew_date' => 'date',
    ];

    /**
     * A payment belongs to a user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

}