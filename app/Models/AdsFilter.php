<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdsFilter extends Model
{
    protected $table = 'uni_ads_filters';

    protected $primaryKey = 'ads_filters_id';
    public function variants()
    {
        return $this->hasMany(AdsFiltersVariant::class, 'ads_filters_variants_id_filter', 'ads_filters_id');
    }

    public function filterItems()
    {
        return $this->hasMany(AdsFiltersItem::class, 'ads_filters_items_id_filter');
    }
    public function filterCategory()
    {
        return $this->hasMany(AdsFilterCategory::class, 'ads_filters_category_id_filter', 'ads_filters_id');
    }

    public function categories()
    {
        return $this->belongsToMany(AdsFilterCategory::class, 'uni_ads_filters_category', 'ads_filters_category_id_filter', 'ads_filters_category_id_cat');
    }
}
