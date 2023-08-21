<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Blog;

class BlogController extends Controller
{
   public function index(){

   }

   public function create(Request $request){

       $reqAll = $request->all();
       if($request->show_for == 'mobile') {
           $reqAll['content'] = strip_tags($request->content);
        }
       Blog::create($reqAll);

       return response()->json(['status' => true,'message' => 'اطلاعیه با موفقیت ایجاد شد !']);
   }
    public function edit(Request $request){

    }
}
