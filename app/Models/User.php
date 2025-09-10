<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Doctor;
use App\Models\Specialization;
use App\RoleSystemEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable , HasRoles;

    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
        'phone',
        'role',
        'is_active',
        'created_at',
        'updated_at',
        'avatar',
        'fichenavatte_max',
        'salary',
        'created_by',
        'background',
    ];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function doctor()
    {
        return $this->hasOne(Doctor::class);
    }

    // If doctors have specializations
    public function specialization()
    {
        return $this->hasOneThrough(
            Specialization::class,
            Doctor::class,
            'user_id', // Foreign key on doctors table
            'id', // Local key on specializations table
            'id', // Local key on users table
            'specialization_id' // Foreign key on doctors table
        );
    }
      public function paymentAccesses()
    {
        return $this->hasMany(UserPaymentMethod::class);
    }

    // You can add an accessor if you want a simple array of allowed method keys (strings)
    // similar to the previous setup, but derived from the pivot table.
    public function getAllowedMethodsAttribute()
    {
        // This will return an array of the PaymentMethodEnum cases (objects)
        return $this->paymentAccesses
                    ->where('status', 'active') // Only get active ones, or whatever logic you need
                    ->map(fn($access) => $access->payment_method_key->value)
                    ->toArray();
    }

    // public function role() : Attribute {
    //     return Attribute::make(
    //         get: fn ($value) =>RoleSystemEnum::from($value)->name,
    //     );}
    
}
