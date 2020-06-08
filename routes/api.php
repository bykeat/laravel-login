<?php

use App\Services\Booking;
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

Route::get('/login', function (Booking $booking) {
    try {
        $id = $_GET["id"];
        $pwd = $_GET["pwd"];
        $hash = hash("sha256", $pwd);

        $result = $booking ->getUserByIdAndPassword($id, $hash);
        //DB::table('users')->where('email', $id)->where('password', $hash)->get();
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

Route::get('/register', function (Booking $booking) {
    try {
        $id = $_GET["id"];
        $pwd = $_GET["pwd"];
        $hash = hash("sha256", $pwd);
        $name = $_GET["name"];

        $data = $booking->getUserById($id);

        if ($data->count() > 0) {
            return response()->json(['status' => 500, "message" => "User exists."]);
        } else {
            $results = $booking->addUser($id, $hash, $name);
            return response()->json(['status' => 200, "message" => "You may login with your email."]);
        }
    } catch (Exception $e) {
        error_log($e);
        return response()->json(['status' => 500]);
    }
});

Route::get('/confirm_booking', function (Booking $booking) {
    try {

        $booking_id = $_GET["booking_id"];
        $fcm_token = $_GET["fcm_token"];
        $status = 1;
        $response = $booking->updateBookingStatus($booking_id, $status);

        if ($response === 1) {
            $body = 'Your booking ' . $booking_id . ' has confirmed.';
            $title = 'Your booking is confirmed';
            $json_data = $booking->createMessage($fcm_token, $title, $body);
            $booking->sendNotification($json_data);
        }
        return response()->json(['status' => 200, 'message' => "Booking confirmed."]);
    } catch (Exception $e) {
        error_log($e);
        return response()->json(['status' => 500, 'message' => "Booking confirmation error."]);
    }
});

Route::get('/cancel_booking', function (Booking $booking) {
    try {
        $booking_id = $_GET["booking_id"];
        $status = -1;
        $result = $booking->updateBookingStatus($booking_id, $status);
        return response()->json(['status' => 200, 'message' => "Booking cancelled."]);
    } catch (Exception $e) {
        return response()->json(['status' => 500, 'message' => "Booking cancellation error."]);
    }
});

Route::get('/make_booking', function (Booking $booking) {
    try {
        date_default_timezone_set(env("TIMEZONE"));
        try {
            $fcm_token = $_GET["fcm_token"];
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

        $response = $booking->getRouteData($origin, $destination);

        $route_data = json_decode($response);
        $distance_metres = $route_data->routes[0]->legs[0]->distance->value;
        $fare = $booking->calculateFare($booking_type, $pickup_time, $distance_metres);

        $travel_time = $route_data->routes[0]->legs[0]->duration->text;
        $distance_text = $route_data->routes[0]->legs[0]->distance->text;

        $id = uniqid();
        $status = 0;
        $booking->addBooking($fcm_token, $id, $booking_type, $pickup_time, $passengers, $origin, $destination, $fare, $status, $note);

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

//Not able to use due to CORS policy blocking POST request.
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
        return response()->json(['status' => 500, 'message' => 'Invalid input.']);
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
