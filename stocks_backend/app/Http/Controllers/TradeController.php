<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TradeController extends Controller
{
    const VIETSTOCK_API_URL = "https://api2.vietstock.vn";

    public function getIndexInfo()
    {
        $endpoint = self::VIETSTOCK_API_URL . "/data/markettradinginfo";
        $client = new \GuzzleHttp\Client();

        $data = [];
        for ($i = 1; $i <= 5; $i++) {
            $response = $client->request('GET', $endpoint, ['query' => [
                'catID' => $i,
                'token' => "82134E070D6F447CA4D6FEEE0D927E88",
            ]]);
            $statusCode = $response->getStatusCode();
            $content = $response->getBody();
            if ($statusCode == 200 && isset($content)) {
                $data[] = json_decode($content)[0];
            }
        }

        return $data;
    }
    public function getTimescaleMarks()
    {
        return [
            ["id" => "F_2016_2", "label" => "F", "date" => "2016-07-30T00:00:00", "title" => "BCTC quý 2/2016|DT: 12449,1 tỷ, +18,6% (vs. Q2/15)|LN: 2815,4 tỷ, +28,8% (vs. Q2/15)", "color" => "#00C800"],
            ["id" => "E_26532", "label" => "D", "date" => "2016-08-19T00:00:00", "title" => "Cổ tức đợt 1/2016 bằng tiền, tỷ lệ 4000đ/CP|Ngày KHQ: 19/08/2016", "color" => "#A0248B"],
            ["id" => "E_26533", "label" => "S", "date" => "2016-08-24T00:00:00", "title" => "Cổ tức năm 2016 bằng cổ phiếu, tỷ lệ 5:1|Ngày KHQ: 19/08/2016", "color" => "#FF8040"],
            ["id" => "F_2016_3", "label" => "F", "date" => "2016-10-30T00:00:00", "title" => "BCTC quý 3/2016|DT: 12204,7 tỷ, +15,7% (vs. Q3/15)|LN: 2548,8 tỷ, +19,4% (vs. Q3/15)", "color" => "#00C800"],
            ["id" => "F_2016_4", "label" => "F", "date" => "2017-01-30T00:00:00", "title" => "BCTC quý 4/2016|DT: 11807,6 tỷ, +6,4% (vs. Q4/15)|LN: 1829 tỷ, -3,5% (vs. Q4/15)", "color" => "#00C800"],
            ["id" => "F_2016_0", "label" => "F", "date" => "2017-02-04T00:00:00", "title" => "BCTC năm 2016|DT: 46794,3 tỷ, +16,8% (vs. 2015)|LN: 9350,3 tỷ, +20,3% (vs. 2015)", "color" => "#00C800"],
            ["id" => "F_2017_1", "label" => "F", "date" => "2017-04-30T00:00:00", "title" => "BCTC quý 1/2017|DT: 12049,4 tỷ, +16,6% (vs. Q1/16)|LN: 2935,1 tỷ, +36,1% (vs. Q1/16)", "color" => "#00C800"],
            ["id" => "E_29607", "label" => "D", "date" => "2017-05-04T00:00:00", "title" => "Cổ tức đợt 2/2016 bằng tiền, tỷ lệ 2000đ/CP|Ngày KHQ: 04/05/2017", "color" => "#A0248B"],
            ["id" => "F_2017_2", "label" => "F", "date" => "2017-07-30T00:00:00", "title" => "BCTC quý 2/2017|DT: 13348,3 tỷ, +7,2% (vs. Q2/16)|LN: 2922,1 tỷ, +3,8% (vs. Q2/16)", "color" => "#00C800"],
            ["id" => "E_30808", "label" => "D", "date" => "2017-08-14T00:00:00", "title" => "Cổ tức đợt 1/2017 bằng tiền, tỷ lệ 2000đ/CP|Ngày KHQ: 14/08/2017", "color" => "#A0248B"],
            ["id" => "F_2017_3", "label" => "F", "date" => "2017-10-30T00:00:00", "title" => "BCTC quý 3/2017|DT: 13293,1 tỷ, +8,9% (vs. Q3/16)|LN: 2693,9 tỷ, +5,7% (vs. Q3/16)", "color" => "#00C800"],
            ["id" => "E_31795", "label" => "D", "date" => "2017-12-28T00:00:00", "title" => "Cổ tức đợt 2/2017 bằng tiền, tỷ lệ 1500đ/CP|Ngày KHQ: 28/12/2017", "color" => "#A0248B"],
            ["id" => "F_2017_0", "label" => "F", "date" => "2018-01-30T00:00:00", "title" => "BCTC năm 2017|DT: 51041,1 tỷ, +9,1% (vs. 2016)|LN: 10295,7 tỷ, +10,1% (vs. 2016)", "color" => "#00C800"],
            ["id" => "F_2017_4", "label" => "F", "date" => "2018-02-04T00:00:00", "title" => "BCTC quý 4/2017|DT: 12350,4 tỷ, +4,6% (vs. Q4/16)|LN: 1744,6 tỷ, -4,6% (vs. Q4/16)", "color" => "#00C800"],
            ["id" => "F_2018_1", "label" => "F", "date" => "2018-04-30T00:00:00", "title" => "BCTC quý 1/2018|DT: 12120,5 tỷ, +0,6% (vs. Q1/17)|LN: 2701,3 tỷ, -8% (vs. Q1/17)", "color" => "#00C800"], 
            ["id" => "E_34118", "label" => "D", "date" => "2018-06-05T00:00:00", "title" => "Cổ tức đợt 3/2017 bằng tiền, tỷ lệ 1500đ/CP|Ngày KHQ: 05/06/2018", "color" => "#A0248B"], 
            ["id" => "F_2018_2", "label" => "F", "date" => "2018-07-30T00:00:00", "title" => "BCTC quý 2/2018|DT: 13702,5 tỷ, +2,7% (vs. Q2/17)|LN: 2666,4 tỷ, -8,8% (vs. Q2/17)", "color" => "#00C800"], 
            ["id" => "E_34836", "label" => "S", "date" => "2018-09-05T00:00:00", "title" => "Cổ tức năm 2018 bằng cổ phiếu, tỷ lệ 5:1|Ngày KHQ: 05/09/2018", "color" => "#FF8040"], 
            ["id" => "E_34835", "label" => "D", "date" => "2018-09-10T00:00:00", "title" => "Cổ tức đợt 1/2018 bằng tiền, tỷ lệ 2000đ/CP|Ngày KHQ: 05/09/2018", "color" => "#A0248B"], 
            ["id" => "F_2018_3", "label" => "F", "date" => "2018-10-30T00:00:00", "title" => "BCTC quý 3/2018|DT: 13735,3 tỷ, +3,3% (vs. Q3/17)|LN: 2560,1 tỷ, -5% (vs. Q3/17)", "color" => "#00C800"], 
            ["id" => "E_35687", "label" => "D", "date" => "2018-12-27T00:00:00", "title" => "Cổ tức đợt 2/2018 bằng tiền, tỷ lệ 1000đ/CP|Ngày KHQ: 27/12/2018", "color" => "#A0248B"], 
            ["id" => "F_2018_0", "label" => "F", "date" => "2019-01-30T00:00:00", "title" => "BCTC năm 2018|DT: 52561,9 tỷ, +3% (vs. 2017)|LN: 10227,3 tỷ, -0,7% (vs. 2017)", "color" => "#00C800"], ["id" => "F_2018_4", "label" => "F", "date" => "2019-02-04T00:00:00", "title" => "BCTC quý 4/2018|DT: 13003,7 tỷ, +5,3% (vs. Q4/17)|LN: 2299,5 tỷ, +31,8% (vs. Q4/17)", "color" => "#00C800"], ["id" => "F_2019_1", "label" => "F", "date" => "2019-04-27T00:00:00", "title" => "BCTC quý 1/2019|DT: 13189,3 tỷ, +8,8% (vs. Q1/18)|LN: 2790,7 tỷ, +3,3% (vs. Q1/18)", "color" => "#00C800"], ["id" => "E_38030", "label" => "D", "date" => "2019-06-05T00:00:00", "title" => "Cổ tức đợt 3/2018 bằng tiền, tỷ lệ 1500đ/CP|Ngày KHQ: 05/06/2019", "color" => "#A0248B"], ["id" => "F_2019_2", "label" => "F", "date" => "2019-07-30T00:00:00", "title" => "BCTC quý 2/2019|DT: 14599 tỷ, +6,5% (vs. Q2/18)|LN: 2898,7 tỷ, +8,7% (vs. Q2/18)", "color" => "#00C800"], ["id" => "E_38777", "label" => "D", "date" => "2019-09-16T00:00:00", "title" => "Cổ tức đợt 1/2019 bằng tiền, tỷ lệ 2000đ/CP|Ngày KHQ: 16/09/2019", "color" => "#A0248B"], ["id" => "F_2019_3", "label" => "F", "date" => "2019-10-30T00:00:00", "title" => "BCTC quý 3/2019|DT: 14290,9 tỷ, +4% (vs. Q3/18)|LN: 2690,2 tỷ, +5,1% (vs. Q3/18)", "color" => "#00C800"], ["id" => "E_39323", "label" => "D", "date" => "2019-12-26T00:00:00", "title" => "Cổ tức đợt 2/2019 bằng tiền, tỷ lệ 1000đ/CP|Ngày KHQ: 26/12/2019", "color" => "#A0248B"], ["id" => "F_2019_0", "label" => "F", "date" => "2020-01-30T00:00:00", "title" => "BCTC năm 2019|DT: 56318,1 tỷ, +7,1% (vs. 2018)|LN: 10581,2 tỷ, +3,5% (vs. 2018)", "color" => "#00C800"], ["id" => "F_2019_4", "label" => "F", "date" => "2020-02-04T00:00:00", "title" => "BCTC quý 4/2019|DT: 14239 tỷ, +9,5% (vs. Q4/18)|LN: 2201,5 tỷ, -4,3% (vs. Q4/18)", "color" => "#00C800"], ["id" => "F_2020_1", "label" => "F", "date" => "2020-04-30T00:00:00", "title" => "BCTC quý 1/2020|DT: 14153,1 tỷ, +7,3% (vs. Q1/19)|LN: 2764,9 tỷ, -0,9% (vs. Q1/19)", "color" => "#00C800"], ["id" => "E_42179", "label" => "D", "date" => "2020-06-29T00:00:00", "title" => "Cổ tức đợt 3/2019 bằng tiền, tỷ lệ 1500đ/CP|Ngày KHQ: 29/06/2020", "color" => "#A0248B"], ["id" => "F_2020_2", "label" => "F", "date" => "2020-07-29T00:00:00", "title" => "BCTC quý 2/2020|DT: 15495,2 tỷ, +6,1% (vs. Q2/19)|LN: 3071,7 tỷ, +6% (vs. Q2/19)", "color" => "#00C800"], ["id" => "E_42808", "label" => "D", "date" => "2020-09-29T00:00:00", "title" => "Cổ tức đợt 1/2020 bằng tiền, tỷ lệ 2000đ/CP|Ngày KHQ: 29/09/2020", "color" => "#A0248B"], ["id" => "E_42809", "label" => "S", "date" => "2020-10-04T00:00:00", "title" => "Cổ tức năm 2020 bằng cổ phiếu, tỷ lệ 100:20|Ngày KHQ: 29/09/2020", "color" => "#FF8040"], ["id" => "F_2020_3", "label" => "F", "date" => "2020-10-30T00:00:00", "title" => "BCTC quý 3/2020|DT: 15563,2 tỷ, +8,9% (vs. Q3/19)|LN: 3077,1 tỷ, +14,4% (vs. Q3/19)", "color" => "#00C800"], ["id" => "E_43592", "label" => "D", "date" => "2021-01-05T00:00:00", "title" => "Cổ tức đợt 2/2020 bằng tiền, tỷ lệ 1000đ/CP|Ngày KHQ: 05/01/2021", "color" => "#A0248B"], ["id" => "F_2020_4", "label" => "F", "date" => "2021-01-30T00:00:00", "title" => "BCTC quý 4/2020|DT: 14424,8 tỷ, +1,3% (vs. Q4/19)|LN: 2185,2 tỷ, -0,7% (vs. Q4/19)", "color" => "#00C800"], ["id" => "F_2020_0", "label" => "F", "date" => "2021-02-04T00:00:00", "title" => "BCTC năm 2020|DT: 59636,3 tỷ, +5,9% (vs. 2019)|LN: 11098,9 tỷ, +4,9% (vs. 2019)", "color" => "#00C800"], ["id" => "F_2021_1", "label" => "F", "date" => "2021-04-30T00:00:00", "title" => "BCTC quý 1/2021|DT: 13190,3 tỷ, -6,8% (vs. Q1/20)|LN: 2575,9 tỷ, -6,8% (vs. Q1/20)", "color" => "#00C800"], ["id" => "E_45799", "label" => "D", "date" => "2021-06-07T00:00:00", "title" => "Test data hiển thị|Ngày : 07/06/2021", "color" => "#A0248B"]
        ];
    }

    public function getDailyPrice(Request $request)
    {
        $tradingdate = $request->input('tradingdate');
        $stockcode = $request->input('stockcode');
        $stockinfo = DB::connection('pgsql')->select(
            "SELECT tr.tradingdate, stock.stockcode ,trstock.basicprice, trstock.totaladjustrate 
            FROM trstock
            INNER JOIN tr ON trstock.trid = tr.trid
            INNER JOIN stock ON trstock.stockid = stock.stockid
            WHERE stock.stockcode = '$stockcode'
                AND tr.tradingdate = '$tradingdate'
            LIMIT 10"
        );

        return $stockinfo;
    }

    public function getTopStocks()
    {
        $stocksinfo = DB::connection('pgsql')->select(
            "SELECT stock.stockcode ,basicprice, openprice, closeprice, highestprice, lowestprice, totalvol, change, perchange
            FROM public.trstock
            INNER JOIN tr ON trstock.trid = tr.trid
            INNER JOIN stock ON trstock.stockid = stock.stockid
            ORDER BY trstock.trid DESC
            LIMIT 1000"
        );

        return $stocksinfo;
    }

    public function getHistory()
    {
        return "";
    }
    public function index(Request $req)
    {
        //dd($req->get('symbol'));

    }
    public function getConfig(Request $req)
    {
        $str = [
            "supported_resolutions" => ["1", "3", "5", "15", "30", "60", "120", "240", "D", '1W', '1M', '12M'],
            "supports_group_request" => false,
            "supports_marks" => true,
            "supports_search" => true,
            "supports_timescale_marks" => true
        ];
        return response()->json($str);
    }
    public function getSymbol(Request $req)
    {
        $str = [
            "description" => "Công ty cổ phần sữa Việt Nam",
            "exchange-listed" => "NasdaqNM",
            "exchange-traded" => "NasdaqNM",
            "has_intraday" => true,
            "has_no_volume" => false,
            "minmov" => 1,
            "minmov2" => 0,
            "name" => "VNM",
            "pointvalue" => 1,
            "pricescale" => 100,
            "intraday_multipliers" => ['1', '60'],
            "session" => "0930-1630",
            "supported_resolutions" => ["1", "3", "5", "15", "30", "60", "120", "240", "D", '1W', '1M', '12M'],
            "ticker" => "VNM",
            "timezone" => "America/New_York",
            "type" => "stock"
        ];
        return response()->json($str);
    }
}
