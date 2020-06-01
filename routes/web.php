<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function(){
    $id=$_GET["id"];
    $pwd=$_GET["pwd"];
    $random=uniqid ();
    $result = DB::select("select * from users_sample where login = ? and pwd=?", [$id,$pwd]);
    
    if ($result){
        $name = $result[0]->name;
        return response()->json(['success'=>true,  "token"=>$result, "message"=>"Welcome, ".$name]);
    }else{
        return response()->json(['success'=>false,"token"=>"", "message"=>"Invalid login."],);
    }
});

Route::get('/register',  function(){
    $id=$_GET["id"];
    $pwd=$_GET["pwd"];
    $name=$_GET["name"];
    $count = DB::select("select count(*) as count from users_sample where login= ?", [$id]);
    if ($count[0] -> count > 0){
        return response()->json(['success'=>false, "count"=>$count[0] -> count]);
    }else{
    $results = DB::insert("INSERT INTO users_sample (login,pwd,name) VALUES (?,?,?)",[$id,$pwd,$name]);
     return response()->json(['success'=>true, "result"=>$results]);
    }
});