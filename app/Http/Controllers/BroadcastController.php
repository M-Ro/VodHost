<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Broadcast as Broadcast;

class BroadcastController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('guest');
    }

    /**
     * Return recently uploaded videos
     *
     * @return \Illuminate\Http\Response
     */
    public function recent()
    {
        $broadcasts = Broadcast::where('public', true)
                    ->orderBy('created_at', 'desc')
                    ->take(50)
                    ->get();

        $json = $broadcasts->toJson();

        return response()->json($json);
    }

    /**
     * Display the view for uploading a video
     *
     * @return \Illuminate\Http\Response
     */
     public function upload()
     {
         return view('upload');
     }
}
