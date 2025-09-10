<?php

namespace App\Models\Reception;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;


class RemiseRequestPrestationContribution extends Model
{
    protected $table = 'remise_request_prestation_contributions';

    protected $fillable = [
        'remise_request_prestation_id',
        'user_id',
        'role',
        'proposed_amount',
        'approved_amount',
        'approved_by',
    ];

    public function prestationRequest()
        {
            return $this->belongsTo(RemiseRequestPrestation::class, 'remise_request_prestation_id');
        }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
