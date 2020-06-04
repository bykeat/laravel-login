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
   
    $hash=hash ("sha256",$pwd);
    //$result = DB::select("select * from users where email = ? and password=?", [$id,$hash]);   
    $result = DB::table('users')->where('email',$id)->where('password',$hash)->get(); 
    if ($result){
        $name = $result[0]->name;
        return response()->json(['success'=>true, "message"=>"Welcome, ".$name]);
    }else{
        return response()->json(['success'=>false, "message"=>"Invalid login."],);
    }
});

Route::get('/register',  function(){
     try {
        $id=$_GET["id"];
        $pwd=$_GET["pwd"];
        $hash=hash ("sha256",$pwd);
        $name=$_GET["name"];
        //$count = DB::select("select count(*) as count from users where email= ?", [$id]); 
        $data = DB::table('users')->where('email', [$id])->find(1);

        if ($data){
            return response()->json(['success'=>false]);
        }else{
            // $results = DB::insert("INSERT INTO users (email,password,name) VALUES (?,?,?)",[$id,$hash,$name]);
            $results = DB::table('users')->insert(['email' => $id, 'password'=>$hash, 'name'=>$name]);
            return response()->json(['success'=>true, "result"=>$results]);
        }
    }catch(Exception $e){
        return response()->json(['success'=>false]);
    }
});
