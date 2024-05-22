<?php

namespace App\Controllers;

use App\Models\MaterialExportReceipt;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class MaterialExportReceiptController
{
    public function getMaterialExportReceipts(): Collection
    {
        $materialERs = MaterialExportReceipt::query()->where('status', '!=' , 'DELETED');

        if (isset($_GET['type'])) {
            $type = urldecode($_GET['type']);
            $materialERs->where('type', $type);
        }

        $materialERs = $materialERs->get();
        foreach ($materialERs as $index => $materialER) {
            $warehouse = Warehouse::query()->where('id',$materialER->warehouse_id)->first();
            unset($materialER->warehouse_id);
            $materialER->warehouse = $warehouse;
        }

        return $materialERs;
    }

    public function getMaterialExportReceiptById($id) : ?Model
    {
        $materialER = MaterialExportReceipt::query()->where('id',$id)->first();
        $warehouse = Warehouse::query()->where('id',$materialER->warehouse_id)->first();
        if ($materialER) {
            unset($materialER->warehouse_id);
            $materialER->warehouse = $warehouse;
            return $materialER;
        } else {
            return null;
        }
    }

    public function getExportReceiptDetailsByExportReceipt($id)
    {
        $materialER = MaterialExportReceipt::query()->where('id',$id)->first();
        return $materialER->MaterialExportReceiptDetails;
    }

    public function createMaterialExportReceipt(): Model
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $materialER = new MaterialExportReceipt();
        $materialER->validate($data);
        $materialER->fill($data);
        $materialER->save();
        return $materialER;
    }

    public function updateMaterialExportReceiptById($id): bool | int
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $materialER = MaterialExportReceipt::find($id);

        if ($materialER) {
            $materialER->validate($data);
            return $materialER->update($data);
        }
        return false;
    }

    public function deleteMaterialExportReceipt($id): string
    {
        $materialER = MaterialExportReceipt::find($id);

        if ($materialER) {
            $materialER->status = 'DELETED';
            $materialER->save();
            return "Xóa thành công";
        }
        else {
            http_response_code(404);
            return "Không tìm thấy";
        }
    }
}