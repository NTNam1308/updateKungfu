<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\EmailTemplate;
use \Exception;
use Mail;
use Datetime;
use Tymon\JWTAuth\Facades\JWTAuth;


// set_error_handler(function () {
//     throw new Exception('Ach!');
// });

class DashboardController extends Controller
{
  public function getDataEOD(Request $request)
  {
    $last_date =  DB::connection('pgsql')
      ->table('hoticker')
      ->addSelect(DB::raw('MAX(tradingdate) as tradingdate'))
      ->first();
    $last_date = $last_date->tradingdate;
    $last_date = substr($last_date, 0, 10);
    $list_mack = DB::table('danh_sach_mack')
      ->addSelect('mack')
      ->distinct()
      ->pluck('mack')
      ->toArray();
    $arr_index = ["VNINDEX", "HNXINDEX", "UPCOMINDEX", "VN30", "HNX30"];
    $data_eod = DB::connection('pgsql')
      ->table('stock_live')
      ->addSelect('stockcode')
      // ->addSelect('openprice')
      ->addSelect('lastprice')
      ->addSelect('totalvol')
      ->addSelect('totalvalue as totalval')
      ->addSelect('change')
      ->addSelect('changepercent as perchange')
      ->addSelect('cl')
      ->whereNotIn('stockcode', $arr_index)
      ->where(DB::raw('LENGTH(stockcode)'), '3')
      ->orderBy('stockcode')
      ->get();

    foreach ($data_eod as $key => $value) {
      $data_eod[$value->stockcode] = $value;
      if ($data_eod[$value->stockcode]->cl == "d" || $data_eod[$value->stockcode]->cl == "f") {
        $data_eod[$value->stockcode]->change = -$data_eod[$value->stockcode]->change;
        $data_eod[$value->stockcode]->perchange = -$data_eod[$value->stockcode]->perchange;
      }
      unset($data_eod[$key]);
    }
    foreach ($list_mack as $mack) {
      if (!isset($data_eod[$mack])) {
        $data_eod[$mack] = [
          "change" => 0,
          "lastprice" => 0,
          "perchange" => 0,
          "stockcode" => $mack,
          "totalvol" => 0
        ];
      }
    }
    return $data_eod;
  }

  public function getDataEODByMack(Request $request)
  {
    $list_mack = $request->input('mack');
    if ($list_mack) {
      for ($i = 0; $i < count($list_mack); $i++) {
        $list_mack[$i] = strtoupper($list_mack[$i]);
      }
      $data_eod = DB::connection('pgsql')
        ->table('stock_live')
        ->addSelect('stockcode')
        // ->addSelect('openprice')
        ->addSelect('lastprice')
        ->addSelect('totalvol')
        ->addSelect('totalvalue as totalval')
        ->addSelect('change')
        ->addSelect('changepercent as perchange')
        ->addSelect('cl')
        ->whereIn('stockcode', $list_mack)
        ->orderBy('stockcode')
        ->where(DB::raw('LENGTH(stockcode)'), '3')
        ->get();

      foreach ($data_eod as $key => $value) {
        $data_eod[$value->stockcode] = $value;
        if ($data_eod[$value->stockcode]->cl == "d" || $data_eod[$value->stockcode]->cl == "f") {
          $data_eod[$value->stockcode]->change = -$data_eod[$value->stockcode]->change;
          $data_eod[$value->stockcode]->perchange = -$data_eod[$value->stockcode]->perchange;
        }
        unset($data_eod[$key]);
      }
      return $data_eod;
    }
    return [];
  }

  public function getDataTradeExchange()
  {

    $data_total_change = DB::connection('pgsql')
      ->table('index_live')
      ->addSelect('stockcode')
      ->addSelect('lastprice as price')
      ->addSelect('changepercent as perchange')
      ->addSelect('change')
      ->addSelect('totalvol')
      ->addSelect('totalvalue')
      ->addSelect('advances')
      ->addSelect('nochange')
      ->addSelect('declines')
      ->addSelect('time')
      ->get();
    $max_date = substr($data_total_change[0]->time, 0, 10);
    $data_yesterday = DB::connection('pgsql')
      ->table('index_eod')
      ->where('tradingdate', function ($query) use ($max_date) {
        $query->select(DB::raw('MAX(tradingdate)'))
          ->from('index_eod')
          ->where('tradingdate', '<', $max_date);
      })
      ->addSelect('stockcode')
      ->addSelect('closeindex')
      ->addSelect('tradingdate')
      ->get();
    $data_total_change = json_decode(json_encode($data_total_change), true);
    $data_yesterday = json_decode(json_encode($data_yesterday), true);
    foreach ($data_yesterday as $key => $value) {
      $data_yesterday[$value['stockcode']] = $value;
      unset($data_yesterday[$key]);
    }
    foreach ($data_total_change as $key => $value) {
      $data_total_change[$value['stockcode']] = $value;
      if ($data_yesterday[$value['stockcode']]['closeindex'] > $data_total_change[$value['stockcode']]['price']) {
        $data_total_change[$value['stockcode']]['perchange'] = -$data_total_change[$value['stockcode']]['perchange'];
        $data_total_change[$value['stockcode']]['change'] = -$data_total_change[$value['stockcode']]['change'];
      }
      unset($data_total_change[$key]);
    }
    return $data_total_change;
  }

  public function getDataIntradayIndex(Request $request)
  {
    $mack = strtoupper($request->input('mack'));
    $last_date =  DB::connection('pgsql')
      ->table('hoticker')
      ->addSelect(DB::raw('MAX(tradingdate) as tradingdate'))
      ->first();
    $last_date = $last_date->tradingdate;
    $last_date = substr($last_date, 0, 10);
    $arr_catid = ["VNINDEX" => "1", "HNXINDEX" => "2", "UPCOMINDEX" => "3", "VN30" => "1", "HNX30" => "2"];
    $table_trade = [
      "hoticker",
      "hoticker",
      "haticker",
      "upcomticker",
      "hoticker",
      "haticker"
    ];
    $data_trading = DB::connection('pgsql')
      ->table($table_trade[$arr_catid[$mack]])
      ->addSelect('stockcode')
      ->addSelect('tradingdate')
      ->addSelect('price')
      ->addSelect('vol')
      ->where('tradingdate', '>=', $last_date)
      ->where('stockcode', $mack)
      ->orderBy('tradingdate', 'asc')
      ->get();
    $data_trading_filter_by_minutes = [];
    $minute = substr($data_trading[0]->tradingdate, 14, 2);
    array_push($data_trading_filter_by_minutes, $data_trading[0]);
    foreach ($data_trading as $value) {
      if (substr($value->tradingdate, 14, 2) != $minute) {
        array_push($data_trading_filter_by_minutes, $value);
        $minute = substr($value->tradingdate, 14, 2);
      }
    }
    return [
      "mack" => $mack,
      "data_trading" => [
        "price" => array_column($data_trading_filter_by_minutes, 'price'),
        "vol" => array_column($data_trading_filter_by_minutes, 'vol'),
        "tradingdate" => array_column($data_trading_filter_by_minutes, 'tradingdate'),
      ]
    ];
  }

  public function getListMackMarketVolatility(Request $request)
  {
    $type = $request->input("type");
    $board_id = $request->input("board");
    switch ($board_id) {
      case 1:
        $board = "HOSE";
        break;
      case 2:
        $board = "HNX";
        break;
      case 3:
        $board = "UPCOM";
        break;
      default:
        $board = "";
        break;
    }
    $limit = $request->input("limit");
    if (!$limit) {
      $limit = 10;
    }
    $list_mack = DB::connection('pgsql')
      ->table('stock_live')
      ->join('stock_list', 'stock_list.stockcode', '=', 'stock_live.stockcode')
      ->addSelect('stock_list.stockcode')
      ->where(DB::raw('LENGTH(stock_list.stockcode)'), '3')
      ->where('stock_list.san', $board);
    switch ($type) {
      case 'volume_desc':
        $list_mack = $list_mack
          ->orderBy("totalvol", "desc");
        break;
      case 'perchange_desc':
        $list_mack = $list_mack
          ->where("cl", "!=", "d")
          ->where("cl", "!=", "f")
          ->orderBy("changepercent", "desc");
        break;
      case 'perchange_asc':
        $list_mack = $list_mack
          ->where("cl", "!=", "c")
          ->where("cl", "!=", "i")
          ->orderBy("changepercent", "desc");
        break;
      case 'change_desc':
        $list_mack = $list_mack
          ->where("cl", "!=", "d")
          ->where("cl", "!=", "f")
          ->orderBy("change", "desc");
        break;
      case 'change_asc':
        $list_mack = $list_mack
          ->where("cl", "!=", "c")
          ->where("cl", "!=", "i")
          ->orderBy("change", "desc");
        break;
      default:
        return [];
        break;
    }
    $list_mack = $list_mack
      ->pluck('stockcode')
      ->take($limit)
      ->toArray();
    return $list_mack;
  }

  public function getListTopBuySell(Request $request)
  {
    $board = $request->input("board");
    $limit = $request->input("limit");
    if (!$limit) {
      $limit = 10;
    }
    $top_sell = DB::connection('pgsql')
      ->table("stock_foreign_live")
      ->join('stock', 'stock.stockcode', '=', 'stock_foreign_live.stockcode')
      ->addSelect("stock.stockcode")
      ->addSelect("fsvol as value_sell")
      ->where(DB::raw('LENGTH(stock.stockcode)'), '3')
      ->where("catid", $board)
      ->orderBy("fsvol", "desc")
      ->take($limit)
      ->get();
    $top_buy = DB::connection('pgsql')
      ->table("stock_foreign_live")
      ->join('stock', 'stock.stockcode', '=', 'stock_foreign_live.stockcode')
      ->addSelect("stock.stockcode")
      ->addSelect("fbvol as value_buy")
      ->where(DB::raw('LENGTH(stock.stockcode)'), '3')
      ->where("catid", $board)
      ->orderBy("fbvol", "desc")
      ->take($limit)
      ->get();
    return [
      "top_sell" => $top_sell,
      "top_buy" => $top_buy
    ];
    return [];
  }

  public function getListTopUpDownPercent(Request $request)
  {
    $type = $request->input("type");
    $type_board = $request->input("type_board");
    switch ($type_board) {
      case 1:
        $board = "HOSE";
        break;
      case 2:
        $board = "HNX";
        break;
      case 3:
        $board = "UPCOM";
        break;
      default:
        $board = "";
        break;
    }
    $type_date = $request->input("type_date");
    $date_before = null;
    if ($type_date == "month") {
      $date_before = date("Y-m-d", strtotime(date("Y-m-d", strtotime(date("Y-m-d"))) . "-1 month"));
    } else {
      $date_before = date("Y-m-d", strtotime(date("Y-m-d", strtotime(date("Y-m-d"))) . "-7 day"));
    }
    $data_before = DB::connection('pgsql')
      ->table('stock_list')
      ->leftJoin('stock_eod', 'stock_list.stockcode', 'stock_eod.stockcode')
      ->addSelect('stock_list.stockcode')
      ->addSelect(DB::raw('coalesce(stock_eod.closeprice*stock_eod.totaladjustrate,0) as closeprice'))
      ->addSelect('tradingdate')
      ->where(DB::raw('LENGTH(stock_list.stockcode)'), '3')
      ->where('stock_list.san', $board)
      ->where('tradingdate', function ($query) use ($date_before) {
        $query->addSelect(DB::raw('max(tradingdate)'))
          ->from('index_eod')
          ->where('stockcode', 'VNINDEX')
          ->where('tradingdate', '<=', $date_before);
      })
      ->get();
    $data_current = DB::connection('pgsql')
      ->table('stock_list')
      ->join('stock_live', 'stock_list.stockcode', 'stock_live.stockcode')
      ->addSelect('stock_list.stockcode')
      ->addSelect(DB::raw('coalesce(stock_live.lastprice,0) as lastprice'))
      ->where('stock_list.san', $board)
      ->where(DB::raw('LENGTH(stock_list.stockcode)'), '3')
      ->get();
    foreach ($data_before as $key => $value) {
      $data_before[$value->stockcode] = $value;
      unset($data_before[$key]);
    }
    foreach ($data_current as $key => $value) {
      if (isset($data_before[$value->stockcode])) {
        $data_current[$key]->perchange = ($value->lastprice / $data_before[$value->stockcode]->closeprice - 1) * 100;
      } else {
        unset($data_current[$key]);
      }
    }
    $data_current = json_decode(json_encode($data_current), true);
    usort($data_current, function ($a, $b) {
      return $a['perchange'] < $b['perchange'] ? 1 : -1;
    });
    if ($type == "up") {
      return [
        "list_top_up" => array_slice($data_current, 0, 10),
      ];
    } else if ($type == "down") {
      return [
        "list_top_down" => array_slice($data_current, -10, 10, true)
      ];
    }
    return [
      "list_top_up" => array_slice($data_current, 0, 10),
      "list_top_down" => array_slice($data_current, -10, 10, true)
    ];
  }

  public function getAllDataDashboard(Request $request)
  {
    $list_mack_eod = $request->input('mack');
    $board_heat_map = $request->input('board_heat_map');
    $board_top_trading = $request->input('board_top_trading');

    if ($list_mack_eod) {
      for ($i = 0; $i < count($list_mack_eod); $i++) {
        $list_mack_eod[$i] = strtoupper($list_mack_eod[$i]);
      }
    }
    $data_eod = DB::connection('pgsql')
      ->table('stock_live')
      ->leftJoin('stock', 'stock.stockcode', 'stock_live.stockcode')
      ->addSelect('stock.stockcode')
      // ->addSelect('openprice')
      ->addSelect('lastprice')
      ->addSelect('totalvol')
      ->addSelect('totalvalue as totalval')
      ->addSelect('change')
      ->addSelect('changepercent as perchange')
      ->addSelect('cl')
      ->whereIn('stock.stockcode', $list_mack_eod)
      ->orWhere('stock.catid', $board_heat_map)
      ->orderBy('stock.stockcode')
      ->get();

    foreach ($data_eod as $key => $value) {
      $data_eod[$value->stockcode] = $value;
      if ($data_eod[$value->stockcode]->cl == "d" || $data_eod[$value->stockcode]->cl == "f") {
        $data_eod[$value->stockcode]->change = -$data_eod[$value->stockcode]->change;
        $data_eod[$value->stockcode]->perchange = -$data_eod[$value->stockcode]->perchange;
      }
      unset($data_eod[$key]);
    }
    $top_sell = DB::connection('pgsql')
      ->table("stock_foreign_live")
      ->join('stock', 'stock.stockcode', '=', 'stock_foreign_live.stockcode')
      ->addSelect("stock.stockcode")
      ->addSelect("fsvol as value_sell")
      ->where(DB::raw('LENGTH(stock.stockcode)'), '3')
      ->where("catid", $board_top_trading)
      ->orderBy("fsvol", "desc")
      ->take(10)
      ->get();
    $top_buy = DB::connection('pgsql')
      ->table("stock_foreign_live")
      ->join('stock', 'stock.stockcode', '=', 'stock_foreign_live.stockcode')
      ->addSelect("stock.stockcode")
      ->addSelect("fbvol as value_buy")
      ->where(DB::raw('LENGTH(stock.stockcode)'), '3')
      ->where("catid", $board_top_trading)
      ->orderBy("fbvol", "desc")
      ->take(10)
      ->get();
    return [
      "data_eod" => $data_eod,
      "top_buy" => $top_buy,
      "top_sell" => $top_sell,
    ];
  }

  public function getAllCategoryNews(Request $request)
  {
    // $list_category = DB::connection('stocks_backend_pgsql')->table("cf_news")
    //   ->addSelect("category")
    //   ->distinct()
    //   ->whereNull("mack")
    //   ->pluck('category');
    // return [
    //   "list_category" => $list_category
    // ];
    return ["list_category" => [
      "Thị trường chứng khoán",
      "Tài chính quốc tế",
      "Tài chính - ngân hàng",
      "Kinh tế vĩ mô - Đầu tư ",
      "Doanh nghiệp",
      "Bất động sản",
      "Thời sự"
    ]];
  }

  public function getAllDataNewsCategory(Request $request)
  {
    $data_category = DB::table("category_news")
      ->get();
    return [
      #Tam thoi chua hien phan tin tu viet   
      #"list_category" => $data_category,
    ];
  }


  public function getListNewsFromCategory(Request $request)
  {
    $category = $request->input('category');

    if ($request->category == "Tất cả") {
      $category = "";
    }
    $type = $request->input('type');

    $page = $request->input('page');
    $item_per_page = $request->input('item_per_page') ? $request->input('item_per_page') : 15;
    $page -= 1;

    switch ($type) {
      case 'tin_tuc_nhan_dinh':
        return DB::table("kungfu_news")
          ->addSelect('id')
          ->addSelect('title')
          ->addSelect(DB::raw('thumbnail as image'))
          ->orderBy('date', 'desc')
          ->take(15)
          ->get();
        break;
      case 'tin_tuc_chung_khoan':
        // $user = JWTAuth::user();
        $limit_count_news_each_mack = 3;
        // $arrMack = DB::table("trading_log")
        //   ->where('id_user', $user->id)
        //   ->pluck('mack');
        // if (count($arrMack) == 0) {
        //   $arrMack = DB::connection('stocks_backend_pgsql')->table("cf_news")
        //     ->orderBy(DB::raw('RAND()'))
        //     ->take(5)
        //     ->pluck('mack');
        // }
        $arrMack = DB::connection('stocks_backend_pgsql')->table("cf_news")
          ->orderBy(DB::raw('RAND()'))
          ->take(5)
          ->pluck('mack');
        $data_news =  DB::connection('stocks_backend_pgsql')->table('cf_news')
          ->addSelect('id')
          ->addSelect('mack')
          ->addSelect('image')
          ->addSelect('title')
          ->addSelect('date')
          ->where('mack', $arrMack[0])
          ->orderBy('date', 'desc')
          ->take($limit_count_news_each_mack);
        for ($i = 1; $i < count($arrMack); $i++) {
          $data_news = $data_news->unionAll(
            DB::connection('stocks_backend_pgsql')->table('cf_news')
              ->addSelect('id')
              ->addSelect('mack')
              ->addSelect('image')
              ->addSelect('title')
              ->addSelect('date')
              ->where('mack', $arrMack[$i])
              ->orderBy('date', 'desc')
              ->take($limit_count_news_each_mack)
          );
        }
        return $data_news->get();
        break;
      case 'cafef':
        return DB::connection('stocks_backend_pgsql')->table("cf_news")
          ->addSelect('id')
          ->addSelect('title')
          ->addSelect('date')
          ->addSelect('image')
          ->addSelect('category')
          ->orderBy('date', 'desc')
          ->where("category", "like", "%" . $category . "%")
          ->whereNull("mack")
          ->offset($page * 15)
          ->take($item_per_page)
          ->get();
      default:
        break;
    }
  }
  public function getListNewsFromCategorys(Request $request)
  {
    $category = $request->input('category');
    $type = $request->input('type');

    // $page = $request->input('page');
    // $item_per_page = $request->input('item_per_page') ? $request->input('item_per_page') : 15;
    // $page -= 1;

    switch ($type) {
      case 'tin_tuc_nhan_dinh':
        return DB::table("kungfu_news")
          ->addSelect('id')
          ->addSelect('title')
          ->addSelect(DB::raw('thumbnail as image'))
          ->orderBy('date', 'desc')
          ->take(15)
          ->get();
        break;
      case 'tin_tuc_chung_khoan':
        // $user = JWTAuth::user();
        $limit_count_news_each_mack = 3;
        // $arrMack = DB::table("trading_log")
        //   ->where('id_user', $user->id)
        //   ->pluck('mack');
        // if (count($arrMack) == 0) {
        //   $arrMack = DB::connection('stocks_backend_pgsql')->table("cf_news")
        //     ->orderBy(DB::raw('RAND()'))
        //     ->take(5)
        //     ->pluck('mack');
        // }
        $arrMack = DB::connection('stocks_backend_pgsql')->table("cf_news")
          ->orderBy(DB::raw('RAND()'))
          ->take(5)
          ->pluck('mack');
        $data_news =  DB::connection('stocks_backend_pgsql')->table('cf_news')
          ->addSelect('id')
          ->addSelect('mack')
          ->addSelect('image')
          ->addSelect('title')
          ->addSelect('date')
          ->where('mack', $arrMack[0])
          ->orderBy('date', 'desc')
          ->take($limit_count_news_each_mack);
        for ($i = 1; $i < count($arrMack); $i++) {
          $data_news = $data_news->unionAll(
            DB::connection('stocks_backend_pgsql')->table('cf_news')
              ->addSelect('id')
              ->addSelect('mack')
              ->addSelect('image')
              ->addSelect('title')
              ->addSelect('date')
              ->where('mack', $arrMack[$i])
              ->orderBy('date', 'desc')
              ->take($limit_count_news_each_mack)
          );
        }
        return $data_news->get();
        break;
      case 'cafef':
        return DB::connection('stocks_backend_pgsql')->table("cf_news")
          ->addSelect('id')
          ->addSelect('title')
          ->addSelect('date')
          ->addSelect('image')
          ->addSelect('category')
          ->orderBy('date', 'desc')
          ->where("category", "like", "%" . $category . "%")
          ->whereNull("mack")
          // ->offset($page * 15)
          // ->take($item_per_page)
          ->paginate(15);
      default:
        break;
    }
  }

  public function getContentNews(Request $request)
  {
    $id = $request->input('id');
    $category = $request->input('category');
    $type = $request->input('type');
    switch ($type) {
      case 'tin_tuc_nhan_dinh':
        $data_return =  DB::table("kungfu_news")
          ->addSelect('content')
          ->where("id", $id)
          ->first();
        $content = $data_return->content;
        $content = preg_replace("/a href/", "a target='blank' href", $content);
        return $content;
        break;
      case 'tin_tuc_chung_khoan':
        $data_return =  DB::connection('stocks_backend_pgsql')->table("cf_news")
          ->addSelect('content')
          ->where("id", $id)
          ->first();
        $content = $data_return->content;
        $content = preg_replace("/a href/", "a target='blank' href", $content);
        return $content;
        break;
      case 'cafef':
        $data_return =  DB::connection('stocks_backend_pgsql')->table("cf_news")
          ->addSelect('content')
          ->addSelect('image')
          ->where("id", $id)
          ->first();
        $content = $data_return->content;
        $content = preg_replace("/a href/", "a target='blank' href", $content);
        return $content;
      default:
        break;
    }
  }

  public function getContentMarketPulse(Request $request)
  {
    return response()->json(DB::table('kungfu_news')
      ->addSelect("content")->where("id", 45)->first());
  }
}
