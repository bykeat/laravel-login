<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', function () {
    try {
        error_log(var_dump($_POST));
        $id = $_POST["id"];
        $pwd = $_POST["pwd"];
        $hash = hash("sha256", $pwd);

        $result = DB::table('users')->where('email', $id)->where('password', $hash)->get();

        if ($result->count() > 0) {
            $name = $result[0]->name;
            return response()->json(['status' => 200, "message" => "Welcome, " . $name]);
        } else {
            return response()->json(['status' => 500, "message" => "Invalid login."], );
        }
    } catch (Exception $e) {
        error_log($e);
        return response()->json(['status' => 500, 'message' => 'Invalid input.']);
    }
});

Route::get('/login', function () {
    try {
        $id = $_GET["id"];
        $pwd = $_GET["pwd"];
        $hash = hash("sha256", $pwd);

        $result = DB::table('users')->where('email', $id)->where('password', $hash)->get();

        if ($result->count() > 0) {
            $name = $result[0]->name;
            return response()->json(['status' => 200, "message" => "Welcome, " . $name]);
        } else {
            return response()->json(['status' => 500, "message" => "Invalid login."], );
        }
    } catch (Exception $e) {
        return response()->json(['status' => 500, 'message' => 'Invalid input.']);
    }
});

Route::get('/register', function () {
    try {
        $id = $_GET["id"];
        $pwd = $_GET["pwd"];
        $hash = hash("sha256", $pwd);
        $name = $_GET["name"];

        $data = DB::table('users')->where('email', [$id])->get();

        if ($data->count() > 0) {
            return response()->json(['status' => 500, "message" => "User exists."]);
        } else {
            $results = DB::table('users')->insert(['email' => $id, 'password' => $hash, 'name' => $name]);
            return response()->json(['status' => 200, "message" => "You may login with your email."]);
        }
    } catch (Exception $e) {
        return response()->json(['status' => 500]);
    }
});

Route::get('/confirm_booking', function () {
    try {

        $booking_id = $_GET["booking_id"];
        $fcm_token = $_GET["fcm_token"];

        $status = 1;
        $response = DB::table("booking")
            ->where("booking_id", $booking_id)
            ->update(["status" => $status]);

        if ($response === 1) {
            $json_data = array
                (
                    'token' => $fcm_token,
                    'notification' => array(
                        'body' => 'Your booking ' . $booking_id . ' has confirmed.',
                        'title' => 'Booking confirmed',
                    ),
                );

            $url = 'https://fcm.googleapis.com/fcm/send';
            $server_key = env('FCM_KEY');
            $headers = array(
                'Content-Type:application/json',
                'Authorization:key=' . $server_key,
            );
            //CURL request to route notification to FCM connection server (provided by Google)
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json_data));
            $result = curl_exec($ch);
            if ($result === false) {
                die('FCM Send Error: ' . curl_error($ch));
            }
            curl_close($ch);
        }

        return response()->json(['status' => 200, 'message' => "Booking confirmed."]);
    } catch (Exception $e) {
        error_log($e);
        return response()->json(['status' => 500, 'message' => "Booking confirmation error."]);
    }
});

Route::get('/cancel_booking', function () {
    try {
        $booking_id = $_GET["booking_id"];
        $status = -1;
        $result = DB::table("booking")
            ->where("booking_id", $booking_id)
            ->update(["status" => $status]);
        return response()->json(['status' => 200, 'message' => "Booking cancelled."]);
    } catch (Exception $e) {
        return response()->json(['status' => 500, 'message' => "Booking cancellation error."]);
    }
});

Route::get('/make_booking', function () {
    try {

        date_default_timezone_set(env("TIMEZONE"));

        try {
            $booking_type = $_GET["booking_type"];
            $pickup_time = $_GET["pickup_time"];
            $note = $_GET["notes"];
            $passengers = $_GET["passengers"];
            $origin = $_GET["origin"];
            $destination = $_GET["destination"];
        } catch (Exception $e) {
            error_log($e);
            return response()->json(['status' => 500, 'message' => "Unable to calculate distance due to invalid input."]);
        }

        if ($booking_type === "pre") {
            $current_hour = date('H', strtotime($pickup_time));
        } else {
            $current_hour = date('H', time());
        }

        $dayFareHour = env("DAY_HOUR");
        $nightFareHour = env("NIGHT_HOUR");
        $fare_rate = env('DAY_RATE');
        if (($current_hour >= $nightFareHour) ||
            ($current_hour < $dayFareHour)) {
            $fare_rate = env('NIGHT_RATE');
        }
        $url = "https://maps.googleapis.com/maps/api/directions/json?key=" . env('GOOGLE_API_KEY');
        $url .= "&origin=" . $origin;
        $url .= "&destination=" . $destination;
        $response = Http::get($url);

        $route_data = json_decode($response);
        $distance_metres = $route_data->routes[0]->legs[0]->distance->value;
        $distance_kilometres = $distance_metres / 1000;
        $fare = round($distance_kilometres * $fare_rate, 1, PHP_ROUND_HALF_UP);

        $travel_time = $route_data->routes[0]->legs[0]->duration->text;
        $distance_text = $route_data->routes[0]->legs[0]->distance->text;
        // $fare = 15.00;
        // $distance_text = "15km";
        // $travel_time = "8 mins";
        $id = uniqid();
        $status = 0;
        DB::table('booking')->insert([
            'token' => $token,
            'booking_id' => $id,
            'booking_type' => $booking_type,
            'pickup_time' => $pickup_time ? date('Y.m.d H:i:s', strtotime($pickup_time)) : "",
            'passengers' => $passengers,
            'origin' => $origin,
            'destination' => $destination,
            'fare' => $fare,
            'status' => $status,
            'note' => $note,
        ]);

        return response()->json(['status' => 200, 'message' => "Booking created",
            'fare' => $fare, 'distance' => $distance_text,
            'time' => $travel_time, 'booking_id' => $id,
            'pickup_datetime' => date('d M Y h:i a', strtotime($pickup_time)),
        ]);

    } catch (Exception $e) {
        error_log($e);
        return response()->json(['status' => 500, 'message' => "Unable to calculate distance due to internal error."]);
        $new = 1;
    }
});

Route::post('/register', function () {
    try {
        $id = $_POST["id"];
        $pwd = $_POST["pwd"];
        $hash = hash("sha256", $pwd);
        $name = $_POST["name"];
        $data = DB::table('users')->where('email', [$id])->get();

        if ($data->count() > 0) {
            return response()->json(['status' => 200, "result" => ""]);
        } else {
            $results = DB::table('users')->insert(['email' => $id, 'password' => $hash, 'name' => $name]);
            return response()->json(['status' => 200, "result" => $results]);
        }
    } catch (Exception $e) {
        return response()->json(['status' => 500]);
    }
});
