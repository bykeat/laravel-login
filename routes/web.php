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

Route::get('/login', function () {
    try {
        $id = $_GET["id"];
        $pwd = $_GET["pwd"];
        $hash = hash("sha256", $pwd);

        $result = DB::table('users')->where('email', $id)->where('password', $hash)->get();

        if ($result) {
            $name = $result[0]->name;
            return response()->json(['success' => true, "message" => "Welcome, " . $name]);
        } else {
            return response()->json(['success' => false, "message" => "Invalid login."], );
        }
    } catch (Exception $e) {
        return response()->json(['success' => false]);
    }
});

Route::get('/register', function () {
    try {
        $id = $_GET["id"];
        $pwd = $_GET["pwd"];
        $hash = hash("sha256", $pwd);
        $name = $_GET["name"];

        $data = DB::table('users')->where('email', [$id])->find(1);

        if ($data) {
            return response()->json(['success' => false]);
        } else {
            $results = DB::table('users')->insert(['email' => $id, 'password' => $hash, 'name' => $name]);
            return response()->json(['success' => true, "result" => $results]);
        }
    } catch (Exception $e) {
        return response()->json(['success' => false]);
    }
});

Route::get('/route_estimation', function () {
    try {
        return response("Hello Kitty");
    } catch (Exception $e) {
        return response("Hello No Kitty");
        $new = 1;
    }
});
