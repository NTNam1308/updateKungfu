<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportAnalysisController extends Controller
{

    public function getListReport(Request $request)
    {
        $mack = strtoupper($request->input('mack'));
        $res = DB::connection('stocks_backend_pgsql')
            ->table('reports_analysis')
            ->addSelect('title')
            ->addSelect('date')
            ->addSelect('source')
            ->addSelect('stockcode')
            ->addSelect('link')
            ->where('stockcode', 'like' , '%'.$mack.'%')
            ->orderBy('date', "DESC")
            ->get();
        foreach ($res as $row) {
            $row->link = "https://data.kungfustockspro.live/REPORT_CF/" . $row->link;
        }
        return $res;
    }

    public function downloadReport($id)
    {
        $path_file = DB::table('document')
            ->where('id', $id)
            ->first();
        $path_file = $path_file->link;
        return response()->download($path_file);
    }
}
