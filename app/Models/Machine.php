<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Machine extends Model
{
    protected $table = 'machines';
    protected $guarded = ['id'];

    public function FunctionName()
    {
        return $this->hasMany(Reading::class);
    }
}
