<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reading extends Model
{
    protected $table = 'readings';
    protected $guarded = ['id'];

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }
}
