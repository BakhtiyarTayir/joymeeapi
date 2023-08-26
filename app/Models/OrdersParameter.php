<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class OrdersParameter extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $guarded = false;

    protected $table = 'uni_orders_parameters';

    protected $primaryKey = 'orders_parameters_id';
    public $timestamps = false;
    protected $fillable = ['orders_parameters_param', 'orders_parameters_id_uniq', 'orders_parameters_date'];
}
