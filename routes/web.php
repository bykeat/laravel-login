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
    $count = DB::select("select count(*) from users_sample where login = '?' and pwd= '?'", [$id,$pwd]);
    if ($count > 0){
        return response(0,200)->header('Content-Type', 'application/json');
    }
});

Route::get('/register', function(){
    $id=$_GET["id"];
    $pwd=$_GET["pwd"];
    $count = DB::select("select count(*) from users_sample where login = '?'", [$id]);
    if ($count > 0){
        return response(0,200)->header('Content-Type', 'application/json');
    }else{
    $results = DB::insert("INSERT INTO users_sample (login,pwd) VALUES (?,?)",[$id,$pwd]);
     return response($results, 200)
                  ->header('Content-Type', 'application/json');
    }
});