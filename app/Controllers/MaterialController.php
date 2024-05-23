<?php

namespace App\Controllers;

use App\Models\Category;
use App\Models\Material;
use App\Models\MaterialExportReceipt;
use App\Models\MaterialExportReceiptDetail;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class MaterialController
{
    public function getMaterials(): Collection
    {
        $material = Material::query()->where('status', '!=' , 'DELETED');

        if (isset($_GET['status'])) {
            $status = urldecode($_GET['status']);
            $material->where('status', $status);
        }

        if (isset($_GET['name'])) {
            $name = urldecode($_GET['name']);
            //$name = str_replace(' ', '%20', $name);
            $material->where('name', 'like', '%' . $name . '%');
        }

        if (isset($_GET['unit'])) {
            $unit = urldecode($_GET['unit']);
            $material->where('unit', $unit);
        }

        if (isset($_GET['weight'])) {
            $weight = urldecode($_GET['weight']);
            $material->where('weight', $weight);
        }

        if (isset($_GET['weight_min'])) {
            $weight_min = urldecode($_GET['weight_min']);
            $material->where('weight', '>=', $weight_min);
        }

        if (isset($_GET['weight_max'])) {
            $weight_max = urldecode($_GET['weight_max']);
            $material->where('weight', '<=', $weight_max);
        }

        if (isset($_GET['quantity'])) {
            $quantity = urldecode($_GET['quantity']);
            $material->where('quantity', $quantity);
        }

        if (isset($_GET['quantity_min'])) {
            $quantity_min = urldecode($_GET['quantity_min']);
            $material->where('quantity', '>=', $quantity_min);
        }

        if (isset($_GET['quantity_max'])) {
            $quantity_max = urldecode($_GET['quantity_max']);
            $material->where('quantity', '<=', $quantity_max);
        }

        if (isset($_GET['origin'])) {
            $origin = urldecode($_GET['origin']);
            //$name = str_replace(' ', '%20', $name);
            $material->where('origin', 'like', '%' . $origin . '%');
        }

        return $material->get();
    }

    public function getMaterialById($id) : Model
    {
        $material = Material::query()->where('id',$id)->first();
        return $material;
    }

    public function getProviderByMaterial($id)
    {
        $material = Material::query()->where('id',$id)->first();
        return $material->providers;
    }

    public function addProviderToMaterial($id)
    {
        $material = Material::query()->where('id',$id)->first();
        $data = json_decode(file_get_contents('php://input'),true);
        $provider = Provider::query()->where('id',$data['provider_id'])->first();
        $material->providers()->attach($provider);
        return 'Thêm thành công';
    }

    public function getCategoryByMaterial($id)
    {
        $material = Material::query()->where('id',$id)->first();
        return $material->categories;
    }

    public function addCategoryToMaterial($id)
    {
        $material = Material::query()->where('id',$id)->first();
        $data = json_decode(file_get_contents('php://input'),true);
        $category = Category::query()->where('id',$data['category_id'])->first();
        $material->categories()->attach($category);
        return 'Thêm thành công';
    }

    public function getExportReceiptDetailsByMaterial($id)
    {
        $material = Material::query()->where('id',$id)->first();
        return $material->exportReceiptDetails;
    }

    public function addExportReceiptDetailToMaterial($id)
    {
        $material = Material::query()->where('id',$id)->first();
        $data = json_decode(file_get_contents('php://input'),true);
        $exportReceipt = MaterialExportReceiptDetail::query()->where('id',$data['material_export_receipt_id'])->first();
        $material->exportReceiptDetails()->attach($exportReceipt);
        return 'Thêm thành công';
    }

    public function getImportReceiptDetailsByMaterial($id)
    {
        $material = Material::query()->where('id',$id)->first();
        return $material->importReceiptDetails;
    }

    public function createMaterial(): Model
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $material = new Material();
        $material->validate($data);
        $material->fill($data);
        $material->save();
        return $material;
    }

    public function updateMaterialById($id): bool | int
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $material = Material::find($id);

        if ($material) {
            $material->validate($data);
            return $material->update($data);
        }
        return false;
    }

    public function deleteMaterial($id): string
    {
        $material = Material::find($id);

        if ($material) {
            $material->status = 'DELETED';
            $material->save();
            return "Xóa thành công";
        }
        else {
            http_response_code(404);
            return "Không tìm thấy";
        }
    }
}