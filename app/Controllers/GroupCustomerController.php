<?php

namespace App\Controllers;

use App\Models\Customer;
use App\Models\GroupCustomer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class GroupCustomerController
{
    public function getGroupCustomers(): Collection
    {
        $groupcustomer = GroupCustomer::query()->where('status', '!=' , 'DISABLE');

        if (isset($_GET['status'])) {
            $status = urldecode($_GET['status']);
            $groupcustomer->where('status', $status);
        }

        if (isset($_GET['name'])) {
            $name = urldecode($_GET['name']);
            //$name = str_replace(' ', '%20', $name);
            $groupcustomer->where('name', 'like', '%' . $name . '%');
        }

        return $groupcustomer->get();
    }

    public function getGroupCustomerById($id) : Model
    {
        $groupcustomer = GroupCustomer::query()->where('id',$id)->first();
        return $groupcustomer;
    }

    public function getCustomerByGroupCustomer($id) : ?Collection
    {
        $groupcustomer = GroupCustomer::query()->where('id', $id)->first();

        if ($groupcustomer) {
            return $groupcustomer->customers()->get();
        } else {
            return null;
        }
    }

    public function createGroupCustomer(): Model
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $groupcustomer = new GroupCustomer();
        $groupcustomer->validate($data);
        $groupcustomer->fill($data);
        $groupcustomer->save();
        return $groupcustomer;
    }

    public function updateGroupCustomerById($id): bool | int
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $groupcustomer = GroupCustomer::find($id);

        if ($groupcustomer) {
            $groupcustomer->validate($data);
            return $groupcustomer->update($data);
        }
        return false;
    }

    public function deleteGroupCustomer($id)
    {
        $groupcustomer = GroupCustomer::find($id);

        if ($groupcustomer) {
            $groupcustomer->status = 'DISABLE';
            $groupcustomer->save();
            return "Xóa thành công";
        }
        else {
            http_response_code(404);
            return "Không tìm thấy";
        }
    }
}