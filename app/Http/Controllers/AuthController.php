<?php

namespace App\Http\Controllers;

use App\Mail\ConfirmedEmailMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(
            'auth:api',
            [
                'except' => [
                    'login',
                    'register',
                    'confirmEmail',
                    'resendEmail',
                ],
            ]
        );
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     * @throws ValidationException
     */
    public function register(Request $request)
    {
        $this->validate($request, [
            "email" => "required|unique:users",
            "name" => "required",
            "password" => "required"
        ]);

        $user = new User();
        $user->email = $request->get("email");
        $user->name = $request->get("name");
        $user->password = Hash::make($request->get("password"));
        $user->code_activation = $this->getCodeRandom();
        $user->save();

        Mail::to($user->email)->send(new ConfirmedEmailMail($user));

        return response()->json($user, 201);
    }

    /**
     * @param int $length
     *
     * @return string
     */
    private function getCodeRandom(int $length = 12): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = "";

        for ($i = 0; $i < $length; $i++) $randomString .= $characters[rand(0, $charactersLength - 1)];

        return $randomString;
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     * @throws ValidationException
     */
    public function resendEmail(Request $request)
    {
        $this->validate($request, [
            "email" => "required"
        ]);

        $user = User::where("email", $request->get("email"))->first();

        if (!$user) {
            return response()->json(['message' => "user not found."], 404);
        }

        if ($user->email_verified_at) {
            return response()->json(['message' => "User already activated"], 403);
        }

        Mail::to($user->email)->send(new ConfirmedEmailMail($user));

        return response()->json(['message' => 'email sent'], 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function confirmEmail(Request $request)
    {
        $this->validate($request, [
            "email" => "required",
            "code" => "required"
        ]);

        $user = User::where("email", $request->get("email"))
            ->where("code_activation", $request->get("code"))
            ->first();

        if (!$user) {
            return response()->json(['message' => "user not found."], 404);
        }

        if ($user->email_verified_at) {
            return response()->json(['message' => "User already activated"], 403);
        }

        $user->email_verified_at = date("Y-m-d H:i:s");
        $user->save();

        return response()->json(['message' => 'Confirmed user'], 200);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return JsonResponse
     */
    public function login()
    {
        $credentials = request(['email', 'password']);

        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = User::where("email", $credentials)
            ->whereNull("email_verified_at")
            ->first();

        if ($user) {
            return response()->json(['message' => 'email not confirmed'], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param string $token
     *
     * @return JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }
}
