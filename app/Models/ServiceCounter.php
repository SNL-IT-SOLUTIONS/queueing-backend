<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceCounter extends Model
{
    use HasFactory;

    // Table name (optional if it follows Laravel's naming convention)
    protected $table = 'service_counters';

    // Fields that are mass assignable
    protected $fillable = [
        'counter_name',
        'prefix',
        'status',
        'queue_waiting',
        'queue_serving',
        'is_prioritylane',
        'is_archived',
    ];

    // Optional: cast queue numbers as integers
    protected $casts = [
        'queue_waiting' => 'integer',
        'queue_serving' => 'integer',
    ];

    // Optional: default attributes
    protected $attributes = [
        'status' => 'Active',
        'queue_waiting' => 0,
        'queue_serving' => 0,
    ];

    // App\Models\ServiceCounter.php

    public function queues()
    {
        return $this->hasMany(ServiceQueue::class, 'service_counter_id');
    }
}
