<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActionStatistic extends Model
{
    use HasFactory;

    protected $table = 'uni_action_statistics';

    protected $primaryKey = 'action_statistics_id';
    // Определите отношение к объявлениям (один ко многим)

    public function ad()
    {
        return $this->belongsTo(Ad::class, 'action_statistics_ad_id', 'action_statistics_id');
    }
}
