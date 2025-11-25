<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceQueue extends Model
{
    use HasFactory;

    // Table name (optional if it follows Laravel naming convention)
    protected $table = 'service_queue';

    // Fields that are mass assignable
    protected $fillable = [
        'service_counter_id',
        'queue_number',
        'customer_name',
        'status',
        'served_at',
        'is_priority',
    ];

    // Cast attributes
    protected $casts = [
        'served_at' => 'datetime',
    ];

    // Default attributes
    protected $attributes = [
        'status' => 'waiting',
    ];

    /**
     * Relationship: Queue belongs to a service counter
     */
    public function counter()
    {
        return $this->belongsTo(ServiceCounter::class, 'service_counter_id');
    }
}
