<?php

namespace App\Http\Controllers;

use App\Http\Requests\KungfuNewRequest;
use App\Models\KungfuNews;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class KungfuNewsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $kungfu_news = KungfuNews::select('id', 'mack', 'title', 'category_id', 'thumbnail', 'date')
        ->orderBy('date', 'DESC')
        ->get();
        return response()->json( compact('kungfu_news') );
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(KungfuNewRequest $request)
    {
        $data = $request->all();
        $data['date'] = Carbon::now();
        $data['slug'] = $this->vn_to_str($request->title);
        Storage::move('public/images/preview/'.$data['thumbnail'], 'public/images/'.$data['thumbnail']);
        $data['thumbnail'] = '/storage/images/'.$data['thumbnail'];
        try{
            KungfuNews::create($data); 

            return response()->json(['status' => 'success'], 200);
        } catch(\Throwable $e) 
        {
            Log::error($e->getMessage());
            return response()->json(['status' => $e], 400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $news = KungfuNews::find($id);
        
        $news->mack =  isset($news->mack ) ? $news->mack  : '';
        $news->checkThumbnail =  isset($news->thumbnail ) ? $news->thumbnail  : '';
        $news->thumbnail =  isset($news->thumbnail ) ? url('/').$news->thumbnail  : '';
        return response()->json( $news );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(KungfuNewRequest $request, $id)
    {
        $data = $request->all();
        $kungfuNews = KungfuNews::find($id);
        if($data['thumbnail'] != $kungfuNews->thumbnail){
            Storage::move('public/images/preview/'.$data['thumbnail'], 'public/images/'.$data['thumbnail']);
            $data['thumbnail'] = '/storage/images/'.$data['thumbnail'];
        }
        try{
            KungfuNews::find($id)->update($data); 

            return response()->json(['status' => 'success'], 200);
        } catch(\Throwable $e) 
        {
            Log::error($e->getMessage());
            return response()->json(['status' => $e], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = KungfuNews::find($id);
        if($user){
            KungfuNews::find($id)->delete();
        }
        return response()->json( ['status' => 'success'] );
    }
    public function vn_to_str ($str){
 
        $unicode = array(
         
        'a'=>'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ',
         
        'd'=>'đ',
         
        'e'=>'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
         
        'i'=>'í|ì|ỉ|ĩ|ị',
         
        'o'=>'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
         
        'u'=>'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
         
        'y'=>'ý|ỳ|ỷ|ỹ|ỵ',
         
        'A'=>'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ặ|Ằ|Ẳ|Ẵ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
         
        'D'=>'Đ',
         
        'E'=>'É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
         
        'I'=>'Í|Ì|Ỉ|Ĩ|Ị',
         
        'O'=>'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
         
        'U'=>'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
         
        'Y'=>'Ý|Ỳ|Ỷ|Ỹ|Ỵ',
         
        );
         
        foreach($unicode as $nonUnicode=>$uni){
         
        $str = preg_replace("/($uni)/i", $nonUnicode, $str);
         
        }
        $str = str_replace(' ','-',$str);
         
        return $str;
         
        }
}
