<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class UniAdvertising extends Model
{
    use HasFactory;

    protected $guarded = false;

    protected $table = 'uni_advertising';
    public $timestamps = false;

    protected $primaryKey = 'advertising_id';


}
