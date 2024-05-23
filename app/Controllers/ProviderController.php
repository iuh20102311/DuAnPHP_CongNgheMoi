<?php

namespace App\Controllers;

use App\Models\Material;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;


class ProviderController
{
    public function getProviders(): Collection
    {
        $provider = Provider::query()->where('status', '!=' , 'DELETED');

        if (isset($_GET['status'])) {
            $status = urldecode($_GET['status']);
            $provider->where('status', $status);
        }

        if (isset($_GET['name'])) {
            $name = urldecode($_GET['name']);
            //$name = str_replace(' ', '%20', $name);
            $provider->where('name', 'like', '%' . $name . '%');
        }

        if (isset($_GET['email'])) {
            $email = urldecode($_GET['email']);
            $provider->where('email', $email);
        }

        if (isset($_GET['phone'])) {
            $phone = urldecode($_GET['phone']);
            $provider->where('phone', $phone);
        }

        if (isset($_GET['city'])) {
            $city = urldecode($_GET['city']);
            $provider->where('city', 'like', '%' . $city . '%');
        }

        if (isset($_GET['district'])) {
            $district = urldecode($_GET['district']);
            $provider->where('district', 'like', '%' . $district . '%');
        }

        return $provider->get();
    }

    public function getProviderById($id) : Model
    {
        $provider = Provider::query()->where('id',$id)->first();
        return $provider;
    }

    public function getMaterialByProvider($id)
    {
        $provider = Provider::query()->where('id',$id)->first();
        return $provider->materials;
    }

    public function addMaterialToProvider($id)
    {
        $provider = Provider::query()->where('id',$id)->first();
        $data = json_decode(file_get_contents('php://input'),true);
        $material = Material::query()->where('id',$data['material_id'])->first();
        $provider->material()->attach($material);
        return 'Thêm thành công';
    }

    public function createProvider(): Model
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $provider = new Provider();
        $provider->validate($data);
        $provider->fill($data);
        $provider->save();
        return $provider;
    }

    public function updateProviderById($id): bool | int
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $provider = Provider::find($id);

        if ($provider) {
            $provider->validate($data);
            return $provider->update($data);
        }
        return false;
    }

    public function deleteProvider($id)
    {
        $provider = Provider::find($id);

        if ($provider) {
            $provider->status = 'DELETED';
            $provider->save();
            return "Xóa thành công";
        }
        else {
            http_response_code(404);
            return "Không tìm thấy";
        }
    }
}