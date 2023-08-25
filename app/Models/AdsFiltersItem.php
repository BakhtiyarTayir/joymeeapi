<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdsFiltersItem extends Model
{
    protected $table = 'uni_ads_filters_items';

    protected $primaryKey = 'ads_filters_items_id';

    public function filter()
    {
        return $this->belongsTo(AdsFilter::class, 'ads_filters_variants_id_filter', 'ads_filters_id');
    }

    public function category()
    {
        return $this->belongsToMany(AdsFilterCategory::class, 'uni_ads_filters_category', 'ads_filters_category_id_filter', 'ads_filters_category_id_cat');
    }
}
