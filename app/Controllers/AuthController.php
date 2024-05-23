<?php

namespace App\Controllers;

use Dotenv\Dotenv;
use App\DTO\LoginResponseDTO;
use App\Models\Role;
use App\Models\Session;
use App\Models\User;
use App\Models\Profile;
use App\Utils\TokenGenerator;
use DateTimeImmutable;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Lcobucci\JWT\Encoding\CannotDecodeContent;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Token\UnsupportedHeaderFound;
use Lcobucci\JWT\Token\Parser;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyEmail;


class AuthController
{
    public function login(): false|string
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['email']) || !isset($data['password'])) {
            return json_encode(['error' => 'Email và mật khẩu là bắt buộc.'], JSON_UNESCAPED_UNICODE);
        }

        $user = User::where('email', $data['email'])->first();
        if (!$user || !password_verify($data['password'], $user->password)) {
            return json_encode(['error' => 'Email hoặc password không chính xác.'], JSON_UNESCAPED_UNICODE);
        }

        $role = Role::where('id', $user->role_id)->first();
        $roleName = $role->name;
        $response = new LoginResponseDTO(TokenGenerator::generateAccessToken($user->id), TokenGenerator::generateRefreshToken($user->id));
        return json_encode($response);
    }

    private function getSessionToken($userId) {
        $session = Session::where('user_id', $userId)->first();
        if ($session) {
            return $session->token;
        }
        return null;
    }

    public function refreshToken()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['user_id'])) {
            error_log("User ID is missing");
            http_response_code(401);
            return false;
        }
        $userId = $data['user_id'];
        $token = $this->getSessionToken($userId);
        if (!$token) {
            error_log("Token not found for user with ID: " . $userId);
            http_response_code(401);
            return false;
        }
        $parser = new \Lcobucci\JWT\Token\Parser(new JoseEncoder());
        try {
            $parsedToken = $parser->parse($token);
            assert($parsedToken instanceof Plain);
            $now = new DateTimeImmutable();
            if ($parsedToken->isExpired($now)) {
                error_log("Token is expired");
                http_response_code(401);
                return false;
            }

            $userId = $parsedToken->claims()->get('id');
            $response = new LoginResponseDTO(TokenGenerator::generateAccessToken($userId), TokenGenerator::generateRefreshToken($userId));
            return json_encode($response);

        } catch (CannotDecodeContent|InvalidTokenStructure|UnsupportedHeaderFound $e) {
            error_log($e->getMessage());
            http_response_code(401);
            return false;
        }
    }
    public function checkEmailExistence($email)
    {
        // API endpoint và API key
        $apiEndpoint = 'https://emailverification.whoisxmlapi.com/api/v3';
        $apiKey = 'at_kalegIeEx43vPpE6dVkBBS5BUWJ56';

        // Tạo URL cho yêu cầu
        $url = $apiEndpoint . '?apiKey=' . $apiKey . '&emailAddress=' . urlencode($email);

        // Khởi tạo một cURL session
        $curl = curl_init();

        // Thiết lập các tùy chọn của cURL
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ]);

        // Gửi yêu cầu và lấy phản hồi
        $response = curl_exec($curl);

        // Kiểm tra lỗi nếu có
        if (curl_errno($curl)) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new Exception('cURL error: ' . $error);
        }

        curl_close($curl);

        $responseData = json_decode($response, true);

        if (isset($responseData['emailExists']) && $responseData['emailExists'] === true) {
            return true; // Email tồn tại
        } else {
            return false; // Email không tồn tại
        }
    }

    public function register()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['email']) || !isset($data['password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Email and password are required']);
                return;
            }

            $zeroBounceApiKey = '3a8a2ee7fe574fc583f54857c8e0e2d9';
            $emailToCheck = $data['email'];
            $apiUrl = "https://api.zerobounce.net/v2/validate?api_key=$zeroBounceApiKey&email=$emailToCheck";
            $response = file_get_contents($apiUrl);
            $responseData = json_decode($response, true);

            if (!$responseData || !isset($responseData['status']) || $responseData['status'] !== 'valid') {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid or non-existent email']);
                return;
            }

            // Mã hóa mật khẩu
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $data['role_id'] = $data['role_id'] ?? 1;

            // Tạo người dùng mới
            $createdUser = User::create([
                'email' => $data['email'],
                'password' => $hashedPassword,
                'role_id' => $data['role_id']
            ]);

            $fullName = trim($data['name']);
            $nameParts = explode(' ', $fullName);
            $lastName = array_pop($nameParts);
            $firstName = implode(' ', $nameParts);

            $profileData = [
                'user_id' => $createdUser->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => null,
                'birthday' => null,
                'avatar' => null,
                'gender' => null
            ];

            Profile::create($profileData);

            http_response_code(201);
            echo json_encode($createdUser);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error registering user: ' . $e->getMessage()]);
        }
    }



    public function changePassword(): string
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['user_id']) || !isset($data['old_password']) || !isset($data['new_password'])) {
            return json_encode(['error' => 'Thiếu thông tin.'], JSON_UNESCAPED_UNICODE);
        }
        $userId = $data['user_id'];
        $oldPassword = $data['old_password'];
        $newPassword = $data['new_password'];

        $user = User::find($userId);
        if (!$user) {
            return json_encode(['error' => 'Người dùng không tồn tại.'], JSON_UNESCAPED_UNICODE);
        }

        if (!password_verify($oldPassword, $user->password)) {
            return json_encode(['error' => 'Mật khẩu cũ không chính xác.'], JSON_UNESCAPED_UNICODE);
        }

        $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $user->password = $hashedNewPassword;
        $user->save();

        return json_encode(['message' => 'Mật khẩu đã được thay đổi thành công.'], JSON_UNESCAPED_UNICODE);
    }

    public function getProfile()
    {
        $headers = apache_request_headers();
        $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;

        if (!$token) {
            return json_encode(['error' => 'Token không tồn tại'], JSON_UNESCAPED_UNICODE);
        }

        try {
            $parser = new Parser(new JoseEncoder());
            $parsedToken = $parser->parse($token);

            $userId = $parsedToken->claims()->get('id');
            $user = User::find($userId);

            if (!$user) {
                return json_encode(['error' => 'Người dùng không tồn tại'], JSON_UNESCAPED_UNICODE);
            }

            // Lấy profile dựa trên user_id
            $profile = Profile::where('user_id', $userId)->first();
            if (!$profile) {
                return json_encode(['error' => 'Profile không tồn tại cho user_id này'], JSON_UNESCAPED_UNICODE);
            }

            $role = Role::find($user->role_id);

            return json_encode([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role->name,
                'first_name' => $profile->first_name,
                'last_name' => $profile->last_name,
                'phone' => $profile->phone,
                'birthday' => $profile->birthday,
                'avatar' => $profile->avatar,
                'gender' => $profile->gender
            ], JSON_UNESCAPED_UNICODE);
        } catch (CannotDecodeContent|InvalidTokenStructure|UnsupportedHeaderFound $e) {
            return json_encode(['error' => 'Token không hợp lệ'], JSON_UNESCAPED_UNICODE);
        }
    }

}