<?php

namespace App\Controllers;

use App\Models\Material;
use App\Models\MaterialExportReceipt;
use App\Models\MaterialExportReceiptDetail;
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
        $materialERs = MaterialExportReceipt::query()->where('id',$id)->first();
        $materialERDList = $materialERs->MaterialExportReceiptDetails;
        foreach ($materialERDList as $key => $value) {
            $material = Material::query()->where('id', $value->material_id)->first();
            unset($value->material_id);
            $value->material = $material;
        }
        return $materialERDList;
    }

    public function createMaterialExportReceipt(): Model | string
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $materialER = new MaterialExportReceipt();
        $error = $materialER->validate($data);
        if ($error != "") {
            http_response_code(404);
            error_log($error);
            return json_encode(["error" => $error]);
        }
        $materialER->fill($data);
        $materialER->save();
        return $materialER;
    }

    public function updateMaterialExportReceiptById($id): bool | int | string
    {
        $materialER = MaterialExportReceipt::find($id);

        if (!$materialER) {
            http_response_code(404);
            return json_encode(["error" => "Provider not found"]);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $error = $materialER->validate($data, true);

        if ($error != "") {
            http_response_code(404);
            error_log($error);
            return json_encode(["error" => $error]);
        }

        $materialER->fill($data);
        $materialER->save();

        return $materialER;
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