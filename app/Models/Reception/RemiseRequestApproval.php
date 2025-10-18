<?php

namespace App\Models\Reception;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class RemiseRequestApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'remise_request_id',
        'user_id',
        'role',
        'status',
        'comment',
        'acted_at'
    ];

    protected $casts = [
        'acted_at' => 'datetime'
    ];

    public function remiseRequest()
    {
        return $this->belongsTo(RemiseRequest::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
