<?php

namespace App\Controllers;

use App\Models\Material;
use App\Models\MaterialExportReceipt;
use App\Models\MaterialExportReceiptDetail;
use App\Models\MaterialInventory;
use App\Models\Warehouse;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class MaterialExportReceiptController
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

        $totalReceipts = MaterialExportReceipt::whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count();

        header('Content-Type: application/json');
        echo json_encode(['total_receipts' => $totalReceipts]);
    }


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

    public function exportMaterials()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        // Kiểm tra xem kho cần xuất kho có tồn tại trong bảng material_inventories hay không
        $warehouseExists = MaterialInventory::where('warehouse_id', $data['warehouse_id'])->exists();
        if (!$warehouseExists) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Kho xuất kho không tồn tại']);
            return;
        }

        // Tạo mới MaterialExportReceipt
        $materialExportReceipt = MaterialExportReceipt::create([
            'warehouse_id' => $data['warehouse_id'],
            'status' => 'ACTIVE', // Hoặc trạng thái mặc định phù hợp với hệ thống của bạn
            'note' => $data['note'] ?? '', // Thêm ghi chú nếu có
        ]);

        // Kiểm tra các nguyên vật liệu cần xuất kho
        $materials = $data['materials'] ?? [];
        foreach ($materials as $material) {
            // Kiểm tra xem nguyên vật liệu có tồn tại trong bảng material_inventories hay không
            $materialExists = MaterialInventory::where('material_id', $material['material_id'])
                ->where('warehouse_id', $data['warehouse_id'])
                ->exists();
            if (!$materialExists) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Nguyên vật liệu không tồn tại trong kho']);
                return;
            }

            // Kiểm tra xem số lượng nguyên vật liệu còn lại trong kho có đủ để xuất kho hay không
            $materialInventory = MaterialInventory::where('material_id', $material['material_id'])
                ->where('warehouse_id', $data['warehouse_id'])
                ->first();
            if ($materialInventory->quantity_available < $material['quantity']) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Số lượng nguyên vật liệu trong kho không đủ để xuất kho']);
                return;
            }

            // Tạo mới một MaterialExportReceiptDetail
            $materialExportReceiptDetail = $materialExportReceipt->details()->create([
                'material_id' => $material['material_id'],
                'quantity' => $material['quantity'],
            ]);

            // Cập nhật số lượng nguyên vật liệu trong kho tương ứng
            $materialInventory->quantity_available -= $material['quantity'];
            $materialInventory->save();

            // Cập nhật số lượng nguyên vật liệu trong bảng materials
            $materialModel = Material::find($material['material_id']);
            if ($materialModel) {
                $materialModel->quantity -= $material['quantity'];
                $materialModel->save();
            }
        }
        header('Content-Type: application/json');
        echo json_encode(['message' => 'Xuất kho thành công', 'receipt_id' => $materialExportReceipt->id]);
    }

}