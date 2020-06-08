<?php
namespace App\Services;

use DB;
use Http;

class Booking
{
    public function __construct()
    {

    }

    public function getUserByIdAndPassword($id, $password)
    {
        return DB::table('users')->where('email', $id)->where('password', $password)->get();
    }

    public function getUserById($id)
    {
        return DB::table('users')->where('email', $id)->get();
    }

    public function addUser($id, $password, $name)
    {
        return DB::table('users')->insert(['email' => $id, 'password' => $password, 'name' => $name]);
    }

    public function updateBookingStatus($booking_id, $status)
    {
        return DB::table("booking")
            ->where("booking_id", $booking_id)
            ->update(["status" => $status]);
    }

    public function createMessage($fcm_token, $title, $body)
    {
        return array
            (
            'to' => $fcm_token,
            'notification' => array(
                'body' => $body,
                'title' => $title,
            ),
        );
    }

    public function sendNotification($json_data)
    {
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
            error_log("SENT" . $result);
            die('FCM Send Error: ' . curl_error($ch));
        } else {
            error_log("ERROR" . $result);
        }
        curl_close($ch);
    }

    public function getRouteData($origin, $destination)
    {
        $url = "https://maps.googleapis.com/maps/api/directions/json?key=" . env('GOOGLE_API_KEY');
        $url .= "&origin=" . $origin;
        $url .= "&destination=" . $destination;
        return Http::get($url);
    }

    public function calculateFare($booking_type, $pickup_time, $distance_metres)
    {
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

        return round($distance_metres / 1000 * $fare_rate, 1, PHP_ROUND_HALF_UP);
    }

    public function addBooking($fcm_token, $id, $booking_type, $pickup_time, $passengers, $origin, $destination, $fare, $status, $note)
    {
        DB::table('booking')->insert([
            'fcm_token' => $fcm_token,
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
    }

    public function Hi($message)
    {
        error_log($message);
    }
}
