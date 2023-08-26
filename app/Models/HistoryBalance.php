<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class HistoryBalance extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $guarded = false;

    protected $table = 'uni_history_balance';

    protected $primaryKey = 'id';

}
