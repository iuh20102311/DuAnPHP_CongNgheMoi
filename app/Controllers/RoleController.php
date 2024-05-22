<?php

namespace App\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class RoleController
{
    public function getRoles(): Collection
    {
        $role = Role::query()->where('status', '!=' , 'DELETED');

        if (isset($_GET['status'])) {
            $status = urldecode($_GET['status']);
            $role->where('status', $status);
        }

        if (isset($_GET['name'])) {
            $name = urldecode($_GET['name']);
            //$name = str_replace(' ', '%20', $name);
            $role->where('name', 'like', '%' . $name . '%');
        }

        return $role->get();
    }

    public function getRoleById($id) : Model
    {
        $role = Role::query()->where('id',$id)->first();
        return $role;
    }

    public function getUserByRole($id) : ?Collection
    {
        $role = Role::query()->where('id', $id)->first();

        if ($role) {
            return $role->users()->get();
        } else {
            return null;
        }
    }

    public function createRole(): Model
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $role = new Role();
        $role->validate($data);
        $role->fill($data);
        $role->save();
        return $role;
    }

    public function updateRoleById($id): bool | int
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $role = Role::find($id);

        if ($role) {
            $role->validate($data);
            return $role->update($data);
        }
        return false;
    }

    public function deleteRole($id)
    {
        $role = Role::find($id);

        if ($role) {
            $role->status = 'DELETED';
            $role->save();
            return "Xóa thành công";
        }
        else {
            http_response_code(404);
            return "Không tìm thấy";
        }
    }
}