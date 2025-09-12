<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LlaveMXState extends Model
{
    use SoftDeletes;
    protected $table = 'llavemx_states';
    protected $fillable = [
        'application_return',
     	'state',
     	'is_used',
        'user_id'
    ];
    protected $dates = ['deleted_at'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
