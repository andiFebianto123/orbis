<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LogHub;

class DetailPersonelApiController extends Controller
{
    public function list(){
        return LogHub::orderBy('id', 'desc')->paginate(100);
    }


}