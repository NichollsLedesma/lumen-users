<?php

namespace App\Http\Controllers;

use App\Http\Services\Email;
use App\Http\Services\SmsMessage;
use App\Mail\ConfirmedEmailMail;
use App\Models\CustomConfig;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private $emailService;
    private $smsService;
    private const PREFIX_COUNTRY = [
        "ARG" => "+549"
    ];

    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->emailService = new Email();
        $this->smsService = new SmsMessage();
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
    public function register(Request $request): JsonResponse
    {
        $this->validate($request, [
            "email" => "required|unique:users",
            "name" => "required",
            "country" => "required",
            "phone_number" => "required",
            "password" => "required"
        ]);
        DB::beginTransaction();

        try {
            // create user
            $user = new User();
            $user->email = $request->get("email");
            $user->name = $request->get("name");
            $user->password = Hash::make($request->get("password"));
            $user->code_activation = $this->getCodeRandom();
            $user->phone_number = self::PREFIX_COUNTRY[$request->get("country")] . $request->get("phone_number");
            $user->save();
            // create config
            $config = new CustomConfig();
            $config->user_id = $user->id;
            $config->save();
            //commit transaction
            DB::commit();
            // send Email
            $operationId = $this->emailService->sendRegisterCode($user->email, $user->code_activation);

            if (!$operationId) {
                return response()->json(['message' => "user created but has problem to send email"], 201);
            }
        } catch (Exception $e) {
            Log::error("Error creating user: " . $e->getMessage());
            DB::rollBack();

            return response()->json(['message' => "Error creating user: " . $e->getMessage()], 500);
        }

        return response()->json($user, 201);
    }

    /**
     * @param int $length
     *
     * @return string
     * @throws Exception
     */
    private function getCodeRandom(int $length = 12): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = "";

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     * @throws ValidationException
     */
    public function resendEmail(Request $request): JsonResponse
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

        // send Email
        $this->emailService->sendRegisterCode($user->email, $user->code_activation);

        return response()->json(['message' => 'email sent'], 200);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     * @throws ValidationException
     */
    public function confirmEmail(Request $request): JsonResponse
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
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function updateConfig(Request $request): JsonResponse
    {
        $authType = $request->get("factor_auth_type");

        try {
            $config = CustomConfig::where("user_id", auth()->user()->id)->first();
            $config->factor_authentication = $authType;
            $config->save();
        } catch (Exception $e) {
            Log::error("Error saving config: " . $e->getMessage());

            return response()->json(['message' => "Error saving config"], 500);
        }

        if (!$authType) {
            return response()->json(['message' => 'Two factor authentication disabled '], 200);
        }

        return response()->json(['message' => 'Two factor authentication is active on mode: ' . $authType], 200);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return JsonResponse
     */
    public function login(): JsonResponse
    {
        $credentials = request(['email', 'password']);
        $code = request("code");

        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user = User::where("email", $credentials)
            ->whereNotNull("email_verified_at")
            ->first();

        if (!$user) {
            return response()->json(['message' => 'email not confirmed'], 401);
        }

        $config = CustomConfig::where("user_id", $user->id)->first();

        if (!$config->factor_authentication) {
            return $this->respondWithToken($token);
        }

        if (!$config->code_auth) {
            $this->sendCode2FA($user, $config);

            return response()->json(['message' => 'code sended to ' . $config->factor_authentication], 200);
        }

        if (!$code) {
            return response()->json(['message' => 'code missed'], 404);
        }

        $code_auth = $config->code_auth;
        $config->code_auth = null;
        $config->save();

        if ($code_auth !== $code) {
            return response()->json(['message' => 'error match codes'], 404);
        }

        return $this->respondWithToken($token);
    }

    private function sendCode2FA(User $user, CustomConfig $config): bool
    {
        $code = $this->getCodeRandom(5);
        $config->code_auth = $code;
        $config->save();

        switch ($config->factor_authentication) {
            case CustomConfig::FACTOR_AUTH_TYPE_EMAIL:
                $this->emailService->sendLoginCode($user->email, $code);
                break;
            case CustomConfig::FACTOR_AUTH_TYPE_PHONE:
                $this->smsService->sendSms($user->phone_number, "Login with this code: " . $code);
                break;
            default:
                return false;
        }

        return true;
    }

    /**
     * Get the authenticated User.
     *
     * @return JsonResponse
     */
    public function me(): JsonResponse
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return JsonResponse
     */
    public function refresh(): JsonResponse
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
    protected function respondWithToken($token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }
}
