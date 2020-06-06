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
$json = json_encode("{name:'Hhk',age:10}");
error_log($json -> name);
error_log($json -> age);
    try {
        $origin = $_GET["origin"];
        $destination = $_GET["destination"];
        error_log("origin:" . $origin . ", destination:" . $destination);
        $url = "https://maps.googleapis.com/maps/api/directions/json?key=" . env('GOOGLE_API_KEY');
        $url .= "&origin=" . $origin;
        $url .= "&destination=" . $destination;
        error_log($url);
        $response = Http::get($url);
        error_log($response);
        parse
        return response()->json(['status' => 200, 'message' => "Route calculated", 'data' => $response]);
    } catch (Exception $e) {
        error_log($e);
        return response()->json(['status' => 400, 'message' => "Unable to calculate distance due to internal error."]);
        $new = 1;
    }
});
