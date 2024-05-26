<?php

namespace App\Controllers;

use App\Models\Product;
use App\Models\ProductExportReceipt;
use App\Models\ProductInventory;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ProductExportReceiptController
{
    public function countTotalReceipts()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['month']) || !isset($data['year'])) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Tháng và năm là bắt buộc.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $month = $data['month'];
        $year = $data['year'];

        $totalReceipts = ProductExportReceipt::whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count();

        header('Content-Type: application/json');
        echo json_encode(['total_receipts' => $totalReceipts]);
    }

    public function getProductExportReceipts(): Collection
    {
        $productERs = ProductExportReceipt::query()->where('status', '!=' , 'DELETED');

        if (isset($_GET['type'])) {
            $type = urldecode($_GET['type']);
            $productERs->where('type', $type);
        }

        if (isset($_GET['status'])) {
            $status = urldecode($_GET['status']);
            $productERs->where('status', $status);
        }

        $productERs = $productERs->get();
        foreach ($productERs as $index => $productER) {
            $warehouse = Warehouse::query()->where('id', $productER->warehouse_id)->first();
            unset($productER->warehouse_id);
            $productER->warehouse = $warehouse;
        }

        return $productERs;
    }

    public function getProductExportReceiptById($id) : ?Model
    {
        $productER = ProductExportReceipt::query()->where('id',$id)->first();
        $warehouse = Warehouse::query()->where('id',$productER->warehouse_id)->first();
        if ($productER) {
            unset($productER->warehouse_id);
            $productER->warehouse = $warehouse;
            return $productER;
        } else {
            return null;
        }
    }

    public function getExportReceiptDetailsByExportReceipt($id)
    {
        $productERs = ProductExportReceipt::query()->where('id',$id)->first();
        $productERList = $productERs->ProductExportReceiptDetails;
        foreach ($productERList as $key => $value) {
            $product = Product::query()->where('id', $value->product_id)->first();
            unset($value->product_id);
            $value->product = $product;
        }
        return $productERList;
    }

    public function createProductExportReceipt(): Model | string
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $productER = new ProductExportReceipt();
        $error = $productER->validate($data);
        if ($error != "") {
            http_response_code(404);
            error_log($error);
            return json_encode(["error" => $error]);
        }
        $productER->fill($data);
        $productER->save();
        return $productER;
    }

    public function updateProductExportReceiptById($id): bool | int | string
    {
        $productER = ProductExportReceipt::find($id);

        if (!$productER) {
            http_response_code(404);
            return json_encode(["error" => "Provider not found"]);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $error = $productER->validate($data, true);

        if ($error != "") {
            http_response_code(404);
            error_log($error);
            return json_encode(["error" => $error]);
        }

        $productER->fill($data);
        $productER->save();

        return $productER;
    }

    public function deleteProductExportReceipt($id): string
    {
        $productER = ProductExportReceipt::find($id);

        if ($productER) {
            $productER->status = 'DELETED';
            $productER->save();
            return "Xóa thành công";
        }
        else {
            http_response_code(404);
            return "Không tìm thấy";
        }
    }

    public function exportProducts()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        // Kiểm tra xem kho cần xuất kho có tồn tại trong bảng material_inventories hay không
        $warehouseExists = ProductInventory::where('warehouse_id', $data['warehouse_id'])->exists();
        if (!$warehouseExists) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Kho xuất kho không tồn tại']);
            return;
        }

        // Tạo mới MaterialExportReceipt
        $productExportReceipt = ProductExportReceipt::create([
            'warehouse_id' => $data['warehouse_id'],
            'status' => 'ACTIVE',
            'note' => $data['note'] ?? '',
        ]);

        // Kiểm tra các nguyên vật liệu cần xuất kho (cho thấy dữ liệu vào từ PostMan)
        $products = $data['products'] ?? [];
        foreach ($products as $product) {
            // Kiểm tra xem nguyên vật liệu có tồn tại trong bảng material_inventories hay không
            $productExists = ProductInventory::where('product_id', $product['product_id'])
                ->where('warehouse_id', $data['warehouse_id'])
                ->exists();
            if (!$productExists) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Sản phẩm không tồn tại trong kho']);
                return;
            }

            // Kiểm tra xem số lượng nguyên vật liệu còn lại trong kho có đủ để xuất kho hay không
            $productInventory = ProductInventory::where('product_id', $product['product_id'])
                ->where('warehouse_id', $data['warehouse_id'])
                ->first();
            if ($productInventory->quantity_available < $product['quantity']) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Số lượng nguyên vật liệu trong kho không đủ để xuất kho']);
                return;
            }

            // Tạo mới một MaterialExportReceiptDetail
            $productExportReceiptDetail = $productExportReceipt->details()->create([
                'product_id' => $product['product_id'],
                'quantity' => $product['quantity'],
            ]);

            // Cập nhật số lượng nguyên vật liệu trong kho tương ứng
            $productInventory->quantity_available -= $product['quantity'];
            $productInventory->save();

            // Cập nhật số lượng nguyên vật liệu trong bảng materials
            $materialModel = Product::find($product['product_id']);
            if ($materialModel) {
                $materialModel->quantity -= $product['quantity'];
                $materialModel->save();
            }
        }
        header('Content-Type: application/json');
        echo json_encode(['message' => 'Xuất kho thành công', 'receipt_id' => $productExportReceipt->id]);
    }
}