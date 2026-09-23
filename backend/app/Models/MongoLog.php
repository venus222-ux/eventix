<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class MongoLog extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'activity_logs';

    protected $fillable = [
        'user_id', 'email', 'action', 'status', 'ip_address',
        'user_agent', 'device', 'browser', 'failure_reason',
        'country', 'city', 'page', 'session_id', 'created_at',
    ];

    public $timestamps = false;
}
