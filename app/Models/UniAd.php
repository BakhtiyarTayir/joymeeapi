<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class UniAd extends Model
{
    use HasFactory;

    protected $guarded = false;

    protected $table = 'uni_ads';
    public const UPDATED_AT = 'ads_update';
    public const CREATED_AT = 'ads_datetime_add';

    protected $primaryKey = 'ads_id';

    public function getStatusName()
    {
        $statusNames = [
            0 => 'На модерации',
            1 => 'Активно',
            2 => 'Снято с публикации',
            3 => 'Заблокировано',
            4 => 'Зарезервировано',
            5 => 'Продано',
            6 => 'Ждет оплаты',
            7 => 'Отклонено',
            8 => 'Удалено',

        ];
        return $statusNames[$this->ads_status] ?? 'Unknown';

    }


 
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

    

    public function scopeRegionCityDistrict(Builder $query, $regionId = null, $cityId = null, $districtId = null)
    {
        if ($regionId) {
            $query->whereHas('region', function ($q) use ($regionId) {
                $q->where('ads_region_id', $regionId);
            });
        }

        if ($cityId) {
            $query->where('ads_city_id', $cityId);
        }

        if ($districtId) {
            $query->whereHas('district', function ($q) use ($districtId) {
                $q->where('ads_area_ids', $districtId);
            });
        }

        return $query;
    }

    public function scopeCategory(Builder $query, $categoryId)
    {
        return $query->where('ads_id_cat', $categoryId);
    }

    public function scopeActive(Builder $query)
    {
        return $query->where('ads_status', 1);
    }

    public function scopeFilterTag(Builder $query, $filterTag)
    {
        return $query->whereRaw("FIND_IN_SET('{$filterTag}', ads_filter_tags) > 0");
    }

}
