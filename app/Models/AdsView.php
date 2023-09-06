<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdsView extends Model
{
    use HasFactory;

    protected $table = 'uni_ads_views';

    protected $primaryKey = 'ads_views_id';

    public function ad()
    {
        return $this->belongsTo(UniAd::class, 'ads_views_id_ad', 'ads_views_id');
    }
}
