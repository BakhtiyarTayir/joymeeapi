<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;
    protected $guarded = false;
    protected  $table = "uni_city_area";
    protected $primaryKey = 'city_area_id';

}
