<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Machine extends Model
{
    protected $table = 'machines';
    protected $guarded = ['id'];

    public function readings()
    {
        return $this->hasMany(Reading::class);
    }

    public function latestReading()
    {
        return $this->hasOne(Reading::class)->latest();
    }
}
