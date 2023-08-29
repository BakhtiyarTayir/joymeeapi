<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use Illuminate\Support\Str;

class HistoryBalanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'name' => $this->name,
            'summa' => $this->summa,
            'action' => $this->action,
            'datetime' => $this->datetime,

        ];
    }
}

