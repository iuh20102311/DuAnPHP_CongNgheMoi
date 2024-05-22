<?php

namespace App\Controllers;

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
        $productER = ProductExportReceipt::query()->where('id',$id)->first();
        return $productER->ProductExportReceiptDetails;
    }

    public function createProductExportReceipt(): Model
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $productER = new ProductExportReceipt();
        $productER->validate($data);
        $productER->fill($data);
        $productER->save();
        return $productER;
    }

    public function updateProductExportReceiptById($id): bool | int
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $productER = ProductExportReceipt::find($id);

        if ($productER) {
            $productER->validate($data);
            return $productER->update($data);
        }
        return false;
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