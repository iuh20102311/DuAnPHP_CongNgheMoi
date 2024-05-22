<?php

namespace App\Controllers;

use App\Models\MaterialImportReceipt;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class MaterialImportReceiptController
{
    public function getMaterialImportReceipts(): Collection
    {
        $materialIRs = MaterialImportReceipt::query()->where('status', '!=' , 'DELETED');

        if (isset($_GET['type'])) {
            $type = urldecode($_GET['type']);
            $materialIRs->where('type', $type);
        }

        if (isset($_GET['total_price'])) {
            $total_price = urldecode($_GET['total_price']);
            $materialIRs->where('total_price', $total_price);
        }

        if (isset($_GET['total_price_min'])) {
            $total_price_min = urldecode($_GET['total_price_min']);
            $materialIRs->where('total_price', '>=', $total_price_min);
        }

        if (isset($_GET['total_price_max'])) {
            $total_price_max = urldecode($_GET['total_price_max']);
            $materialIRs->where('total_price', '<=', $total_price_max);
        }

        if (isset($_GET['status'])) {
            $status = urldecode($_GET['status']);
            $materialIRs->where('status', $status);
        }

        $materialIRs = $materialIRs->get();
        foreach ($materialIRs as $index => $materialIR) {
            $warehouse = Warehouse::query()->where('id',$materialIR->warehouse_id)->first();
            unset($materialIR->warehouse_id);
            $materialIR->warehouse = $warehouse;
        }

        return $materialIRs;
    }

    public function getMaterialImportReceiptById($id) : ?Model
    {
        $materialIR = MaterialImportReceipt::query()->where('id',$id)->first();
        $warehouse = Warehouse::query()->where('id',$materialIR->warehouse_id)->first();
        if ($materialIR) {
            unset($materialIR->warehouse_id);
            $materialIR->warehouse = $warehouse;
            return $materialIR;
        } else {
            return null;
        }
    }

    public function getImportReceiptDetailsByImportReceipt($id)
    {
        $materialIR = MaterialImportReceipt::query()->where('id',$id)->first();
        return $materialIR->MaterialImportReceiptDetails;
    }

    public function createMaterialImportReceipt(): Model
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $materialIR = new MaterialImportReceipt();
        $materialIR->validate($data);
        $materialIR->fill($data);
        $materialIR->save();
        return $materialIR;
    }

    public function updateMaterialImportReceiptById($id): bool | int
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $materialIR = MaterialImportReceipt::find($id);

        if ($materialIR) {
            $materialIR->validate($data);
            return $materialIR->update($data);
        }
        return false;
    }

    public function deleteMaterialImportReceipt($id): string
    {
        $materialIR = MaterialImportReceipt::find($id);

        if ($materialIR) {
            $materialIR->status = 'DELETED';
            $materialIR->save();
            return "Xóa thành công";
        }
        else {
            http_response_code(404);
            return "Không tìm thấy";
        }
    }
}