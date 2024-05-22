<?php

namespace App\Controllers;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class WarehouseController
{
    public function getWarehouses(): Collection
    {
        $warehouse = Warehouse::query()->where('status', '!=' , 'DELETED');

        if (isset($_GET['status'])) {
            $status = urldecode($_GET['status']);
            $warehouse->where('status', $status);
        }

        if (isset($_GET['name'])) {
            $name = urldecode($_GET['name']);
            //$name = str_replace(' ', '%20', $name);
            $warehouse->where('name', 'like', '%' . $name . '%');
        }

        if (isset($_GET['city'])) {
            $city = urldecode($_GET['city']);
            $warehouse->where('city', 'like', '%' . $city . '%');
        }

        if (isset($_GET['district'])) {
            $district = urldecode($_GET['district']);
            $warehouse->where('district', 'like', '%' . $district . '%');
        }

        return $warehouse->get();
    }

    public function getWarehouseById($id) : Model
    {
        return Warehouse::query()->where('id',$id)->first();
    }

    public function getProductInventoryByWarehouse($id) : ?Collection
    {
        $warehouse = Warehouse::query()->where('id', $id)->first();
        return $warehouse->inventories;
    }

    public function createWarehouse(): Model
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $warehouse = new Warehouse();
        $warehouse->validate($data);
        $warehouse->fill($data);
        $warehouse->save();
        return $warehouse;
    }

    public function updateWarehouseById($id): bool | int
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $warehouse = Warehouse::find($id);

        if ($warehouse) {
            $warehouse->validate($data);
            return $warehouse->update($data);
        }
        return false;
    }

    public function deleteWarehouse($id)
    {
        $warehouse = Warehouse::find($id);

        if ($warehouse) {
            $warehouse->status = 'DELETED';
            $warehouse->save();
            return "Xóa thành công";
        }
        else {
            http_response_code(404);
            return "Không tìm thấy";
        }
    }
}