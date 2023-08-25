<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryBoardResource;
use App\Http\Resources\UniAd\UniAdResource;
use App\Models\UniAd;
use App\Models\UniCategoryBoard;
use Illuminate\Http\Request;

class CategoryBoardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
//        $categories = UniCategoryBoard::all();
        $categories = UniCategoryBoard::where('category_board_id_parent', 0)->get();

//        $data['main_image'] = Storage::put('/images', $data['main_image']);

        $response = [];
        foreach ($categories as $category) {
            $data = [
                'category_id' => $category->category_board_id ,
                'name' => $category->category_board_name,
                'image_url' =>  $category->category_board_image ?  'https://joymee.uz/media/others/' . $category->category_board_image : null,
                'category_board_id_parent' => $category->category_board_id_parent,
            ];

            $subcategories = UniCategoryBoard::where('category_board_id_parent', $category->category_board_id)->get();
            if ($subcategories->isNotEmpty()) {
                $subcategoryData = [];
                foreach ($subcategories as $subcategory) {
                    $subcategoryData[] = [
                        'category_id' => $subcategory->category_board_id,
                        'name' => $subcategory->category_board_name,
                        'image_url' => $subcategory->category_board_image ?  'https://dev.joymee.uz/media/others/' . $subcategory->category_board_image : null,
                        'category_board_id_parent' => $subcategory->category_board_id_parent,
                    ];
                }
                $data['subcategories'] = $subcategoryData;
            } else {
                $data['subcategories'] = null;
            }

            $response[] = $data;
        }

//     return CategoryBoardResource::collection($response);
        return  response()->json(['data' => $response]);
    }

    public function ads(Request $request)
    {
        $categoryIds = $request->input('category_ids');

        $ads = UniAd::whereIn('ads_id_cat', $categoryIds)->get();

//        return response()->json(['data' => $ads]);
        return UniAdResource::collection($ads);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(UniCategoryBoard $uniCategoryBoard)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UniCategoryBoard $uniCategoryBoard)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UniCategoryBoard $uniCategoryBoard)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UniCategoryBoard $uniCategoryBoard)
    {
        //
    }
}
