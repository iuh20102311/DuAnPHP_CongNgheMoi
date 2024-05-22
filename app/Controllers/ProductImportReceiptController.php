<?php

namespace App\Controllers;

use App\Models\ProductImportReceipt;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ProductImportReceiptController
{
    public function getProductImportReceipts(): Collection
    {
        $productIRs = ProductImportReceipt::query()->where('status', '!=' , 'DELETED');

        if (isset($_GET['quantity'])) {
            $quantity = urldecode($_GET['quantity']);
            $productIRs->where('quantity', $quantity);
        }

        if (isset($_GET['type'])) {
            $type = urldecode($_GET['type']);
            $productIRs->where('type', $type);
        }

        $productIRs = $productIRs->get();
        foreach ($productIRs as $index => $productIR) {
            $warehouse = Warehouse::query()->where('id', $productIR->warehouse_id)->first();
            unset($productIR->warehouse_id);
            $productIR->warehouse = $warehouse;
        }

        return $productIRs;
    }

    public function getProductImportReceiptById($id) : ?Model
    {
        $productIR = ProductImportReceipt::query()->where('id',$id)->first();
        $warehouse = Warehouse::query()->where('id',$productIR->warehouse_id)->first();
        if ($productIR) {
            unset($productIR->warehouse_id);
            $productIR->warehouse = $warehouse;
            return $productIR;
        } else {
            return null;
        }
    }

    public function getImportReceiptDetailsByExportReceipt($id)
    {
        $productIR = ProductImportReceipt::query()->where('id',$id)->first();
        return $productIR->ProductImportReceiptDetails;
    }

    public function createProductImportReceipt(): Model
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $productIR = new ProductImportReceipt();
        $productIR->validate($data);
        $productIR->fill($data);
        $productIR->save();
        return $productIR;
    }

    public function updateProductImportReceiptById($id): bool | int
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $productIR = ProductImportReceipt::find($id);

        if ($productIR) {
            $productIR->validate($data);
            return $productIR->update($data);
        }
        return false;
    }

    public function deleteProductImportReceipt($id): string
    {
        $productIR = ProductImportReceipt::find($id);

        if ($productIR) {
            $productIR->status = 'DELETED';
            $productIR->save();
            return "Xóa thành công";
        }
        else {
            http_response_code(404);
            return "Không tìm thấy";
        }
    }
}