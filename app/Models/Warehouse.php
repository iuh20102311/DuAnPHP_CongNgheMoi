<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Exception;
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\ValidationException;

class Warehouse extends Model
{
    use HasFactory;
    protected $table = 'warehouses';
    protected $fillable = ['name','address','city','district','ward','status','note','created_at','updated_at'];
    protected $primaryKey = 'id';
    public $timestamps = true;


    public function inventories(): HasMany
    {
        return $this->hasMany(ProductInventory::class, 'warehouse_id');
    }

    public function MaterialInventories()
    {
        return $this->hasMany(MaterialInventory::class,'warehouse_id');
    }

    /**
     * @throws Exception
     */
    public function validate(array $data, bool $isUpdate = false) : string
    {
        $validators = [
            'name' => v::notEmpty()->setName('name')->setTemplate('Tên không được rỗng'),
            'address' => v::notEmpty()->setName('address')->setTemplate('Địa chỉ không được rỗng'),
            'city' => v::notEmpty()->setName('city')->setTemplate('Thành phố không được rỗng'),
            'district' => v::notEmpty()->setName('district')->setTemplate('Đường không được rỗng'),
            'ward' => v::notEmpty()->setName('ward')->setTemplate('Phường không được rỗng'),
            'status' => v::notEmpty()->setName('status')->setTemplate('Trạng thái không được rỗng'),
        ];

        $error = "";
        foreach ($validators as $field => $validator) {
            if ($isUpdate && !array_key_exists($field, $data)) {
                continue;
            }

            try {
                $validator->assert(isset($data[$field]) ? $data[$field] : null);
            } catch (ValidationException $exception) {
                $error = $exception->getMessage();
                break;
            }
        }
        return $error;
    }
}