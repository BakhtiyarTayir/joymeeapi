<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reels extends Model
{
    use HasFactory;

    protected $guarded = false;

    protected $table = 'uni_blog_articles';

//    public const UPDATED_AT = 'ads_update';
    public const CREATED_AT = 'blog_articles_date_add';

    protected $primaryKey = 'blog_articles_id';


}
