<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UserProfile extends Model
{
    use HasFactory;
    protected $table = 'user_profile';
    protected $fillable = [
        'avatar',
        'phone',
        'address',
        'city',
        'userable_type',
        'userable_id'
    ];

    public function userDetailable(): MorphTo
    {
        return $this->morphTo();
    }
}
