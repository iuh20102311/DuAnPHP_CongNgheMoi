<?php

namespace App\Controllers;

use App\Models\Product;
use App\Models\ProductExportReceipt;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ProductExportReceiptController
{
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
}