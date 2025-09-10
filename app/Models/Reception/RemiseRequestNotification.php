<?php

namespace App\Models\Reception;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
class RemiseRequestNotification extends Model
{
    use HasFactory;

    // Add these constants so services can reference them
    public const TYPE_REQUEST  = 'request';
    public const TYPE_RESPONSE = 'response';

    protected $fillable = [
        'remise_request_id',
        'receiver_id',
        'type',
        'message',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    // Relationships
    public function remiseRequest()
    {
        return $this->belongsTo(RemiseRequest::class);
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    // Query scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('receiver_id', $userId);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }
}
