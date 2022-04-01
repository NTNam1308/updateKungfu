<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use Tymon\JWTAuth\Facades\JWTAuth;

class CheckTokenController extends Controller
{ 
    public function getUserFromToken(){
        return JWTAuth::user();
    }
}