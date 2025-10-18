<?php

namespace App\Models\CONFIGURATION;

use Illuminate\Database\Eloquent\Model;

class RemiseUser extends Model
{
    protected $table = 'remise_users';
    protected $fillable = ['remise_id', 'user_id'];

    public function remise()
    {
        return $this->belongsTo(Remise::class, 'remise_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
