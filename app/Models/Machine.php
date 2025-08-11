<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Machine extends Model
{
    protected $table = 'machines';
    protected $guarded = ['id'];

    public function reading()
    {
        return $this->hasMany(Reading::class);
    }
}
