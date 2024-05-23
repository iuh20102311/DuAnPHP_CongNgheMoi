<?php

namespace App\Controllers;

use App\Models\Material;
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
        $materialIRs = MaterialImportReceipt::query()->where('id',$id)->first();
        $materialIRDList = $materialIRs->MaterialImportReceiptDetails;
        foreach ($materialIRDList as $key => $value) {
            $material = Material::query()->where('id', $value->material_id)->first();
            unset($value->material_id);
            $value->material = $material;
        }
        return $materialIRDList;
    }

    public function createMaterialImportReceipt(): Model | string
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $materialIR = new MaterialImportReceipt();
        $error = $materialIR->validate($data);
        if ($error != "") {
            http_response_code(404);
            error_log($error);
            return json_encode(["error" => $error]);
        }
        $materialIR->fill($data);
        $materialIR->save();
        return $materialIR;
    }

    public function updateMaterialImportReceiptById($id): bool | int | string
    {
        $materialIR = MaterialImportReceipt::find($id);

        if (!$materialIR) {
            http_response_code(404);
            return json_encode(["error" => "Provider not found"]);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $error = $materialIR->validate($data, true);

        if ($error != "") {
            http_response_code(404);
            error_log($error);
            return json_encode(["error" => $error]);
        }

        $materialIR->fill($data);
        $materialIR->save();

        return $materialIR;
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