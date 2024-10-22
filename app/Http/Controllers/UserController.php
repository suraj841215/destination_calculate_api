<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function createUser(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string',
                'address' => 'required|string',
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'address' => $validated['address'],
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'status' => 'active',
            ]);

            // Generate JWT Token
            $token = JWTAuth::fromUser($user);

            return response()->json([
                'status_code' => 200,
                'message' => 'User created successfully',
                'data' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'address' => $user->address,
                    'latitude' => $user->latitude,
                    'longitude' => $user->longitude,
                    'status' => $user->status,
                    'register_at' => $user->created_at->toDateTimeString(),
                    'token' => $token,
                ]
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status_code' => 422,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'User creation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function changeUserStatus(Request $request)
    {
        try {
            DB::table('users')->update([
                'status' => DB::raw("IF(status = 'active', 'inactive', 'active')")
            ]);

            return response()->json([
                'status_code' => 200,
                'message' => 'All user statuses have been updated successfully.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Failed to update user statuses',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getDistance(Request $request)
    {
        try {
            $validated = $request->validate([
                'destination_latitude' => 'required|numeric',
                'destination_longitude' => 'required|numeric',
            ]);

            $user = Auth::user();

            $earthRadius = 6371;

            $latFrom = deg2rad($user->latitude);
            $lonFrom = deg2rad($user->longitude);
            $latTo = deg2rad($validated['destination_latitude']);
            $lonTo = deg2rad($validated['destination_longitude']);

            $latDelta = $latTo - $latFrom;
            $lonDelta = $lonTo - $lonFrom;

            $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
                cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

            $distance = $earthRadius * $angle;

            return response()->json([
                'status_code' => 200,
                'message' => 'Distance calculated successfully',
                'distance' => round($distance, 2) . ' km'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status_code' => 422,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Distance calculation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getUserListing(Request $request)
    {
        try {
            $validated = $request->validate([
                'week_number' => 'required|array',
            ]);

            $daysOfWeek = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

            $data = [];

            foreach ($validated['week_number'] as $day) {
                $dayName = $daysOfWeek[$day];

                $users = User::whereRaw("WEEKDAY(created_at) = ?", [$day])->get(['name', 'email']);

                $data[$dayName] = $users;
            }

            return response()->json([
                'status_code' => 200,
                'message' => 'Users fetched successfully',
                'data' => $data,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status_code' => 422,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Failed to fetch users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
