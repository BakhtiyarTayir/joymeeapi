<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *      schema="Country",
 *      required={"country_name","country_alias"},
 *      @OA\Property(
 *          property="country_name",
 *          description="",
 *          readOnly=false,
 *          nullable=false,
 *          type="string",
 *      ),
 *      @OA\Property(
 *          property="country_alias",
 *          description="",
 *          readOnly=false,
 *          nullable=false,
 *          type="string",
 *      ),
 *      @OA\Property(
 *          property="created_at",
 *          description="",
 *          readOnly=true,
 *          nullable=true,
 *          type="string",
 *          format="date-time"
 *      ),
 *      @OA\Property(
 *          property="updated_at",
 *          description="",
 *          readOnly=true,
 *          nullable=true,
 *          type="string",
 *          format="date-time"
 *      )
 * )
 */class Country extends Model
{
    public $table = 'uni_country';

    protected $guarded = false;
    protected $primaryKey = "country_id";


//    public $fillable = [
//        'country_name',
//        'country_alias'
//    ];

    protected $casts = [
        'country_name' => 'string',
        'country_alias' => 'string'
    ];

    public static array $rules = [
        'country_name' => 'required',
        'country_alias' => 'required'
    ];

    public function regions()
    {
        return $this->hasMany(UniRegion::class, 'country_id', 'country_id');
    }
}
