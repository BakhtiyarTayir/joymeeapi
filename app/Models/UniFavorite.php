<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UniFavorite extends Model
{
    use HasFactory;
    protected $table = 'uni_favorites';
    protected $primaryKey = 'favorites_id';
    public $timestamps = false;


    public function ad()
    {
        return $this->belongsTo(UniAd::class, 'favorites_id_ad', 'ads_id');
    }
}
