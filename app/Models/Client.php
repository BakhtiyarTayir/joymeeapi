<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Client extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $guarded = false;

    protected $table = 'uni_clients';

    protected $primaryKey = 'clients_id';



    const CREATED_AT = 'clients_datetime_add';
    const UPDATED_AT =  'clients_datetime_view';


    protected $fillable = [
        'clients_name',
        'clients_email',
        'clients_pass',
        'clients_id_hash',
        'clients_avatar',
        'clients_phone',
        'clients_surname',
        'clients_balance',
        'clients_type_person',
        'clients_city_id',
        'clients_additional_phones',
        'clients_view_phone',
    ];


    const ROLE_ADMIN = 0;
    const ROLE_READER = 1;

    public static function getRoles() {
        return [
            self::ROLE_ADMIN => 'Админ',
            self::ROLE_READER => 'Читатель',
        ];
    }


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
//        'clients_pass' => 'hashed',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
