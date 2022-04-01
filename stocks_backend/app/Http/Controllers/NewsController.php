<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NewsController extends Controller
{
    public function getCountPage(Request $request)
    {
        $mack = strtoupper($request->input('mack'));
        $from_date = $request->input('from_date');
        $to_date = $request->input('to_date');
        $data =  DB::connection('stocks_backend_pgsql')
            ->table('cf_news')
            ->addSelect('id')
            ->where('mack', $mack);
        if ($from_date)
            $data = $data->where('date', '>=', $from_date);
        if ($to_date)
            $data = $data->where('date', '<=', $to_date);
        return $data->count();
    }

    public function getListNews(Request $request)
    {
        $mack = strtoupper($request->input('mack'));
        $from_date = $request->input('from_date');
        $to_date = $request->input('to_date');
        $page = $request->input('page');
        $item_per_page = $request->input('item_per_page') ? $request->input('item_per_page') : 20;
        $page -= 1;
        $data = DB::connection('stocks_backend_pgsql')
            ->table('cf_news')
            ->addSelect('id')
            ->addSelect('mack')
            ->addSelect('title')
            ->addSelect('image')
            ->addSelect('slug')
            ->addSelect('date')
            ->where('mack', $mack)
            ->orderBy('date', 'desc')
            ->offset($page * 20)
            ->take($item_per_page);
        if ($from_date)
            $data = $data->where('date', '>=', $from_date);
        if ($to_date)
            $data = $data->where('date', '<=', $to_date);
        return $data->get();
    }

    public function getContentNews(Request $request)
    {
        $id = $request->input('id');
        $data = DB::connection('stocks_backend_pgsql')
            ->table('cf_news')
            ->addSelect('content')
            ->where('id', $id)
            ->first();
        $content = $data->content;
        $content = preg_replace("/a href/", "a target='blank' href", $content);
        return $content;
    }

    public function getListNewsRelated(Request $request)
    {
        $limit_count_news_each_mack = 3;
        $arrMack = $request->input('mack');
        if (isset($arrMack)) {
            $data_news =  DB::connection('stocks_backend_pgsql')
                ->table('cf_news')
                ->addSelect('id')
                ->addSelect('mack')
                ->addSelect('title')
                ->addSelect('image')
                ->addSelect('slug')
                ->addSelect('date')
                ->where('mack', $arrMack[0])
                ->orderBy('date', 'desc')
                ->take($limit_count_news_each_mack);
            for ($i = 1; $i < count($arrMack); $i++) {
                $data_news = $data_news->unionAll(
                    DB::connection('stocks_backend_pgsql')
                        ->table('cf_news')
                        ->addSelect('id')
                        ->addSelect('mack')
                        ->addSelect('title')
                        ->addSelect('image')
                        ->addSelect('slug')
                        ->addSelect('date')
                        ->where('mack', $arrMack[$i])
                        ->orderBy('date', 'desc')
                        ->take($limit_count_news_each_mack)
                );
            }
            return $data_news->get();
        }
        return "";
    }
}
