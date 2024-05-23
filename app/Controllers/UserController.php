<?php

namespace App\Controllers;

use App\Models\Role;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Lcobucci\JWT\Encoding\CannotDecodeContent;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Token\UnsupportedHeaderFound;

class UserController
{
    public function getUsers(): Collection
    {
        $users = User::query()->where('status', '!=' , 'DELETED');

        if (isset($_GET['email'])) {
            $email = urldecode($_GET['email']);
            $users->where('email', $email);
        }

        if (isset($_GET['name'])) {
            $name = urldecode($_GET['name']);
            //$name = str_replace(' ', '%20', $name);
            $users->where('name', 'like', '%' . $name . '%');
        }

        $users = $users->get();
        foreach ($users as $index => $user) {
            $role = Role::query()->where('id',$user->role_id)->first();
            unset($user->customer_id);
            unset($user->password);
            $user->role = $role;
        }
        return $users;
    }

    public function getUserById($id): ?Model
    {
        $user = User::query()->where('id', $id)->first();
        $role = Role::query()->where('id',$user->role_id)->first();
        if ($user) {
            unset($user->role_id);
            unset($user->password);
            $user->role = $role;
            return $user;
        } else {
            return null;
        }
    }

    public function getInventoryTransactionByUser($id) : ?Collection
    {
        $user = User::query()->where('id', $id)->first();

        if ($user) {
            return $user->inventorytransactions()->get();
        } else {
            return null;
        }
    }

    public function getOrderByUser($id) : ?Collection
    {
        $user = User::query()->where('id', $id)->first();

        if ($user) {
            return $user->orders()->get();
        } else {
            return null;
        }
    }

    public function getProfileByUser($id) : ?Collection
    {
        $user = User::query()->where('id', $id)->first();

        if ($user) {
            return $user->profile()->get();
        } else {
            return null;
        }
    }

    public function updateUserById($id): bool|int
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        $user = User::query()->where('id', $id)->first();
        return $user->update($data);
    }

//    public function deleteUser($id)
//    {
//        $user = User::find($id);
//        if (!$user) {
//            http_response_code(404);
//            echo json_encode(['error' => 'User not found']);
//        }
//
//        $user->status = 'DELETED';
//        $user->save();
//        if ($user->profile) {
//            $user->profile->status = 'DELETED';
//            $user->profile->save();
//            return "Xóa thành công";
//        }
//        return $user;
//    }

    public function deleteUser($id)
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
            if (!$userId) {
                return json_encode(['error' => 'Token không hợp lệ'], JSON_UNESCAPED_UNICODE);
            }

            $user = User::find($userId);

            if (!$user) {
                return json_encode(['error' => 'Người dùng không tồn tại'], JSON_UNESCAPED_UNICODE);
            }

            $role = Role::find($user->role_id);

            if ($role && $role->role_name === 'Super_Admin') {
                $userToDelete = User::find($id);
                if (!$userToDelete) {
                    http_response_code(404);
                    return json_encode(['error' => 'User not found'], JSON_UNESCAPED_UNICODE);
                }

                $userToDelete->status = 'DELETED';
                $userToDelete->save();

                if ($userToDelete->profile) {
                    $userToDelete->profile->status = 'DELETED';
                    $userToDelete->profile->save();
                }

                http_response_code(200);
                return json_encode(['message' => 'User and profile deleted successfully'], JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(403);
                return json_encode(['error' => 'Permission denied'], JSON_UNESCAPED_UNICODE);
            }

        } catch (CannotDecodeContent | InvalidTokenStructure | UnsupportedHeaderFound $e) {
            return json_encode(['error' => 'Token không hợp lệ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }
}