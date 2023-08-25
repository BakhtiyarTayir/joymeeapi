<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdsFilterCategory extends Model
{
    protected $table = 'uni_ads_filters_category';

    public function filters()
    {
        return $this->belongsToMany(AdsFilter::class, 'uni_ads_filters_category', 'ads_filters_category_id_cat', 'ads_filters_category_id_filter');
    }

    public function variants()
    {
        return $this->belongsToMany(AdsFiltersVariant::class, 'uni_ads_filters_category', 'ads_filters_category_id_cat', 'ads_filters_category_id_filter');
    }

    public function filter()
    {
        return $this->belongsTo(AdsFilter::class, 'ads_filters_category_id_filter');
    }


}
