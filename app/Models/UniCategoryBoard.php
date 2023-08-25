<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UniCategoryBoard extends Model
{
    use HasFactory;
    protected $guarded = false;

    protected $table = 'uni_category_board';

    protected $primaryKey = 'category_board_id';

    public function ads()
    {
        return $this->hasMany(UniAd::class, 'ads_id_cat', 'category_board_id');
    }

}
