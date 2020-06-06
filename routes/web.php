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
            return response()->json(['status' => 200, "message" => "Welcome, " . $name]);
        } else {
            return response()->json(['success' => 200, "message" => "Invalid login."], );
        }
    } catch (Exception $e) {
        return response()->json(['status' => 500]);
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
            return response()->json(['status' => 200, "result" => ""]);
        } else {
            $results = DB::table('users')->insert(['email' => $id, 'password' => $hash, 'name' => $name]);
            return response()->json(['status' => 200, "result" => $results]);
        }
    } catch (Exception $e) {
        return response()->json(['status' => 500]);
    }
});

Route::get('/create_booking', function () {
    try {

        date_default_timezone_set(env("TIMEZONE"));
        $current_hour = date('H', time());
        $day_fare_hour = env("DAY_HOUR");
        $night_fare_hour = env("NIGHT_HOUR");
        $fare_rate = env('DAY_RATE');
        if (($current_hour >= $night_fare_hour) ||
            ($current_hour < $day_fare_hour)) {
            $fare_rate = env('NIGHT_RATE');
        }

        $origin = $_GET["origin"];
        $destination = $_GET["destination"];
        error_log("origin:" . $origin . ", destination:" . $destination);
        $url = "https://maps.googleapis.com/maps/api/directions/json?key=" . env('GOOGLE_API_KEY');
        $url .= "&origin=" . $origin;
        $url .= "&destination=" . $destination;
        $response = Http::get($url);

        $route_data = json_decode($response);
        $distance_metres = $route_data->routes[0]->legs[0]->distance->value;
        $distance_kilometres = $distance_metres / 1000;
        $cost = round($distance_kilometres * $fare_rate, 1, PHP_ROUND_HALF_UP);

        $time_text = $route_data->routes[0]->legs[0]->duration->text;
        $distance_text = $route_data->routes[0]->legs[0]->distance->text;

        $id = uniqid();
        return response()->json(['status' => 200, 'message' => "Route calculated", 'price' => $cost, 'distance' => $distance_text, 'time' => $time_text, 'booking_id' => $id]);
    } catch (Exception $e) {
        error_log($e);
        return response()->json(['status' => 500, 'message' => "Unable to calculate distance due to internal error."]);
        $new = 1;
    }
});
