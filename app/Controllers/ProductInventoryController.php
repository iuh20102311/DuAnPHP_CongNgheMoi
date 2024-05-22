<?php

namespace App\Controllers;

use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ProductInventoryController
{
    public function getProductInventories() : Collection
    {
        $productinventories = ProductInventory::query()->where('status', '!=' , 'DISABLE');

        if (isset($_GET['quantity_available'])) {
            $quantity_available = urldecode($_GET['quantity_available']);
            $productinventories->where('quantity_available', $quantity_available);
        }

        if (isset($_GET['minimum_stock_level'])) {
            $minimum_stock_level = urldecode($_GET['minimum_stock_level']);
            $productinventories->where('minimum_stock_level', $minimum_stock_level);
        }

        $productinventories = $productinventories->get();
        foreach ($productinventories as $index => $productinventory) {
            $product = Product::query()->where('id',$productinventory->product_id)->first();
            unset($productinventory->product_id);
            $productinventory->product = $product;

            $warehouse = Warehouse::query()->where('id', $productinventory->warehouse_id)->first();
            unset($productinventory->warehouse_id);
            $productinventory->warehouse = $warehouse;
        }
        return $productinventories;
    }

    public function getProductInventoryById($id) : ?Model
    {
        $productinventory = ProductInventory::query()->where('id', $id)->first();

        if (!$productinventory) {
            return null;
        }

        $product = Product::query()->where('id', $productinventory->product_id)->first();
        $warehouse = Warehouse::query()->where('id', $productinventory->warehouse_id)->first();
        unset($productinventory->product_id, $productinventory->warehouse_id);
        $productinventory->product = $product;
        $productinventory->warehouse = $warehouse;
        return $productinventory;
    }

    public function createProductInventory(): Model
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $productinventory = new ProductInventory();
        $productinventory->validate($data);
        $productinventory->fill($data);
        $productinventory->save();
        return $productinventory;
    }

    public function updateProductInventoryById($id): bool | int
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $productinventory = ProductInventory::find($id);

        if ($productinventory) {
            $productinventory->validate($data);
            return $productinventory->update($data);
        }
        return false;
    }

    public function deleteProductInventory($id)
    {
        $productinventory = ProductInventory::find($id);

        if ($productinventory) {
            $productinventory->status = 'DISABLE';
            $productinventory->save();
            return "Xóa thành công";
        }
        else {
            http_response_code(404);
            return "Không tìm thấy";
        }
    }
}

