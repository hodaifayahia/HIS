<?php

namespace App\Models\Coffre;

use Illuminate\Database\Eloquent\Model;

use App\Models\User;


class Coffre extends Model
{
    protected $fillable = ['name', 'current_balance','location' ,'responsible_user_id'];


    public function user()
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }


}
