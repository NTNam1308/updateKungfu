<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocumentController extends Controller
{
    public function getListYear(Request $request)
    {
        $mack = strtoupper($request->input('mack'));
        $data =  DB::connection('stocks_backend_pgsql')->table('document')
            ->addSelect('year')
            ->distinct()
            ->where('mack', $mack)
            ->orderBy('year', 'desc')
            ->get();
        $data = json_decode(json_encode($data), true);
        return array_column($data, "year");
    }

    public function getListDocument(Request $request)
    {
        $mack = strtoupper($request->input('mack'));
        $year = $request->input('year');
        if ($year == "*") {
            $data = DB::connection('stocks_backend_pgsql')->table('document')
                ->addSelect('id')
                ->addSelect('mack')
                ->addSelect('fullname')
                ->addSelect('link')
                ->addSelect('year')
                ->where('mack', $mack)
                ->orderBy("year","desc")
                ->orderBy("id","desc")
                ->get();
            foreach ($data as $row) {
                $path_file = $row->link;
                $path_file = str_replace("/data_hdd/document_crawl/", "", $path_file);
                $path_file = "https://data.kungfustockspro.live/" . $path_file;
                $row->link = $path_file;
            }
            return $data;
        } else {
            $data = DB::connection('stocks_backend_pgsql')->table('document')
                ->addSelect('id')
                ->addSelect('mack')
                ->addSelect('fullname')
                ->addSelect('link')
                ->addSelect('year')
                ->where('mack', $mack)
                ->where('year', $year)
                ->orderBy("year","desc")
                ->orderBy("id","desc")
                ->get();
            foreach ($data as $row) {
                $path_file = $row->link;
                $path_file = str_replace("/data_hdd/document_crawl/", "", $path_file);
                $path_file = "https://data.kungfustockspro.live/" . $path_file;
                $row->link = $path_file;
            }
            return $data;
        }
    }

    public function downloadDocument($id)
    {
        $path_file = DB::connection('stocks_backend_pgsql')->table('document')
            ->where('id', $id)
            ->first();
        $path_file = $path_file->link;
        return response()->download($path_file);
    }
}
