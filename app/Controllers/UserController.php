<?php

namespace App\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class UserController
{
    public function getUsers(): Collection
    {
        $user = User::query();

        if (isset($_GET['email'])) {
            $email = urldecode($_GET['email']);
            $user->where('email', $email);
        }

        if (isset($_GET['name'])) {
            $name = urldecode($_GET['name']);
            //$name = str_replace(' ', '%20', $name);
            $user->where('name', 'like', '%' . $name . '%');
        }

        return $user->get();
    }

    public function getUserById($id): ?Model
    {
        $user = User::query()->where('id', $id)->first();
        $role = Role::query()->where('id',$user->role_id)->first();
        if ($user) {
            unset($user->role_id);
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

    public function deleteUser($id)
    {
        $results = User::destroy($id);
        $results === 0 && http_response_code(404);
        return $results === 1 ? "Xóa thành công" : "Không tìm thấy";
    }

}