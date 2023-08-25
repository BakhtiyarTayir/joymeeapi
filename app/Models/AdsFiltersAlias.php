<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdsFiltersAlias extends Model
{
    use HasFactory;

    protected $table = 'uni_ads_filters_alias';

    protected $primaryKey = 'ads_filters_alias_id';
}
