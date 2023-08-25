<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReelsResource;
use App\Models\Reels;
use Illuminate\Http\Request;

class ReelsController extends Controller
{
    public function index()
    {
        $reels = Reels::all();
        $reels = $reels->map(function ($item) {
            $item->reels_favorite = $item->reels_favorite == 1 ? true : false;
            if(isset($item->blog_articles_image)) {
                $item->blog_articles_image = "https://joymee.uz/media/images_blog/medium/" . $item->blog_articles_image;
            } else {
                $item->blog_articles_image = "https://joymee.uz/media/images_blog/medium/big/64c4d0836634d.jpg";
            }
            $item->media_file = "https://joymee.uz/" .$item->media_file;
            return $item;
        });

        return ReelsResource::collection($reels);
    }
}
