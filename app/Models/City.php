<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;
    protected $guarded = false;
    protected  $table = "uni_city";
    protected $primaryKey = 'city_id';

    public function region()
    {
        return $this->belongsTo(UniRegion::class, 'city_id', 'region_id');
    }

    public function areas()
    {
        return $this->hasMany(Area::class, 'city_area_id_city', 'city_id');
    }
}
