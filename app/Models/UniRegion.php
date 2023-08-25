<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UniRegion extends Model
{
    use HasFactory;
    protected $guarded = false;
    protected $table = "uni_region";

    protected $primaryKey = 'region_id';


    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id', 'region_id');
    }

    public function cities()
    {
        return $this->hasMany(City::class, 'region_id', 'region_id');
    }

}
