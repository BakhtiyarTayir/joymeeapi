<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Reels;
use App\Models\UniAdvertising;
use App\Models\UniCategoryBoard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Redirect;

class BannerController extends Controller
{
    public function click($id)
    {
        $advertising = UniAdvertising::find($id);

        if ($advertising) {
            $advertising->increment('advertising_click');

            $link = $advertising->advertising_link_site;


            if (strpos($link, "http") === false) {
                $link = urldecode($link);
            }

            return Redirect::to($link);
        }
        return Response::make('Реклама не найдена', 404);
    }

    public function category(Request $request)
    {
        // Получаем параметры из запроса
        $param = $request->all();

        // Создаем экземпляры моделей CategoryBoard и Blog
        $categoryBoard = new UniCategoryBoard();
        $blog = new Reels();

        // Определяем массивы позиций категорий
        $posCategoryDisplayBoard = ["result", "catalog_sidebar", "catalog_top", "catalog_bottom", "ad_view_top", "ad_view_sidebar", "ad_view_bottom"];
        $posCategoryDisplayBlog = ["blog_sidebar", "blog_top", "blog_bottom", "blog_view_sidebar", "blog_view_top", "blog_view_bottom"];

        if (isset($param["ids_cat"])) {
            $ids = [];

            if (isset($param["current_id_cat"])) {
                if ($param["out_podcat"] == 1) {
                    foreach (explode(",", $param["ids_cat"]) as $id) {
                        if (in_array($param["position_name"], $posCategoryDisplayBoard)) {
                            $explode = explode(",", $categoryBoard->idsBuild($id, $param["categories"]));
                        } elseif (in_array($param["position_name"], $posCategoryDisplayBlog)) {
                            $explode = explode(",", $blog->idsBuild($id, $param["categories"]));
                        }

                        if (count($explode)) {
                            foreach ($explode as $value) {
                                $ids[$value] = $value;
                            }
                        } else {
                            $ids[$id] = $id;
                        }
                    }
                } else {
                    $ids = explode(",", $param["ids_cat"]);
                }

                if (in_array($param["current_id_cat"], $ids)) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    public function results(Request $request, $param = [])
    {
        $get = DB::table('uni_advertising')
            ->where('advertising_banner_position', $param['position_name'])
            ->where('advertising_visible', 1)
            ->inRandomOrder()
            ->first();

        if ($get) {
            // Проверки аналогичны оригинальной функции, вы можете вставить их здесь.

            // Возвращаем JSON-ответ с данными баннера.
            return response()->json(['banner' => $get]);
        }

        // Если не найден баннер, вернуть JSON-ответ с пустыми данными или нужную вам структуру.
        return response()->json(['banner' => null]);
    }

    public function out(Request $request, $param = [])
    {


        $get = DB::table('uni_advertising')
            ->where('advertising_banner_position', $param['position_name'])
            ->where('advertising_visible', 1)
            ->inRandomOrder()
            ->first();

        if ($get) {
            // Проверки аналогичны оригинальной функции, вы можете вставить их здесь.

            if ($get['advertising_var_out'] == 1) {
                if (!Auth::check()) {
                    return response()->json(['banner' => null]);
                }
            } elseif ($get['advertising_var_out'] == 2) {
                if (Auth::check()) {
                    return response()->json(['banner' => null]);
                }
            }

            if ((in_array($request->route()->getName(), explode(",", $get['advertising_pages'])) || !$get['advertising_pages']) &&
                ( (now() >= strtotime($get['advertising_date_start']) || strtotime($get['advertising_date_start']) == "0000-00-00 00:00:00") &&
                    (now() < strtotime($get['advertising_date_end']) || $get['advertising_date_end'] == "0000-00-00 00:00:00") ) &&
                $this->category(["ids_cat" => $get['advertising_ids_cat'], "current_id_cat" => $param['current_id_cat'], "position_name" => $param['position_name'], "out_podcat" => $get['advertising_out_podcat'], "categories" => $param['categories']]) &&
                $this->geo(["geo" => $get['advertising_geo']])
            ) {
                if ($param['position_name'] == "stretching") {
                    if ($get['advertising_type_banner'] == 1) {
                        if (Auth::check()) {
                            return response()->json(['banner' => $get]);
                        } else {
                            return response()->json(['banner' => null]);
                        }
                    } elseif ($get['advertising_type_banner'] == 2) {
                        if (Auth::check()) {
                            return response()->json(['banner' => $get]);
                        } else {
                            return response()->json(['banner' => null]);
                        }
                    }
                } else {
                    if ($get['advertising_type_banner'] == 1) {
                        if (Auth::check()) {
                            return response()->json(['banner' => $get]);
                        } else {
                            return response()->json(['banner' => null]);
                        }
                    } elseif ($get['advertising_type_banner'] == 2) {
                        if (Auth::check()) {
                            return response()->json(['banner' => $get]);
                        } else {
                            return response()->json(['banner' => null]);
                        }
                    }
                }
            }
        } else {
            if (Auth::check() && in_array($request->route()->getName(), explode(",", $settings['advertising_pages'])) && $settings['banner_markup']) {
                // Возвращаем JSON-ответ с информацией о месте для баннера.
                return response()->json(['banner' => ['place_for_banner' => true]]);
            }
        }

        // Возвращаем JSON-ответ с пустыми данными, если ни одно из условий не выполнено.
        return response()->json(['banner' => null]);
    }

    public function bannersPositions($id_key = "")
    {
        $option = [];

        $bannersPositions = $this->positions(); // Предположим, что метод positions уже определен в вашем контроллере.

        foreach ($bannersPositions as $key => $array) {
            if ($id_key && $id_key == $key) {
                $selected = 'selected=""';
            } else {
                $selected = "";
            }
            $option[$array["title"]][] = [
                'title' => $array["title"],
                'name' => $array["name"],
                'value' => $key,
                'selected' => $selected,
            ];
        }

        $return = [];

        if (count($option) > 0) {
            foreach ($option as $group_name => $option_val) {
                $return[] = [
                    'group_name' => $group_name,
                    'options' => $option_val,
                ];
            }
        }

        // Возвращаем JSON-ответ
        return response()->json(['data' => $return]);
    }

    public function positions()
    {
        return [
            "stretching" => ["title" => "Растяжка", "name" => "Баннер под шапкой"],
            "result" => ["title" => "Каталог объявлений", "name" => "Реклама в результате выдачи"],
            "catalog_sidebar" => ["title" => "Каталог объявлений", "name" => "Боковая панель"],
            "catalog_top" => ["title" => "Каталог объявлений", "name" => "Верхняя позиция"],
            "catalog_bottom" => ["title" => "Каталог объявлений", "name" => "Нижняя позиция"],
            "ad_view_top" => ["title" => "Карточка объявления", "name" => "Верхняя позиция"],
            "ad_view_sidebar" => ["title" => "Карточка объявления", "name" => "Боковая панель"],
            "ad_view_bottom" => ["title" => "Карточка объявления", "name" => "Нижняя позиция"],
            "index_center" => ["title" => "Главная страница", "name" => "Средняя позиция"],
            "index_top" => ["title" => "Главная страница", "name" => "Верхняя позиция"],
            "index_bottom" => ["title" => "Главная страница", "name" => "Нижняя позиция"],
            "index_sidebar" => ["title" => "Главная страница", "name" => "Боковая панель"],
            "blog_top" => ["title" => "Блог", "name" => "Верхняя позиция"],
            "blog_bottom" => ["title" => "Блог", "name" => "Нижняя позиция"],
            "blog_sidebar" => ["title" => "Блог", "name" => "Боковая панель"],
            "blog_view_sidebar" => ["title" => "Карточка статьи", "name" => "Боковая панель"],
            "blog_view_top" => ["title" => "Карточка статьи", "name" => "Верхняя позиция"],
            "blog_view_bottom" => ["title" => "Карточка статьи", "name" => "Нижняя позиция"],
        ];
    }


}
