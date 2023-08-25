<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UniAd extends Model
{
    use HasFactory;

    protected $guarded = false;

    protected $table = 'uni_ads';
    public const UPDATED_AT = 'ads_update';
    public const CREATED_AT = 'ads_datetime_add';

    protected $primaryKey = 'ads_id';

    protected $casts = [
        'ads_images' => 'array', // Укажите, что это поле должно быть преобразовано в массив
    ];

    public function category()
    {
        return $this->belongsTo(UniCategoryBoard::class, 'category_board_id', 'ads_id_cat');

    }

    public function uniCategoryBoard() {
        return $this->belongsTo(UniCategoryBoard::class,  'category_board_id', 'ads_id_cat');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'ads_city_id', 'city_id');
    }

    public function filters()
    {
        return $this->belongsToMany(AdsFilter::class, 'uni_ads_filters_variants', 'ads_filters_variants_product_id', 'ads_filters_variants_id');
    }
}
