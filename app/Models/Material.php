<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Exception;
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\ValidationException;

class Material extends Model
{
    use HasFactory;
    protected $table = 'materials';
    protected $fillable = ['name','unit','weight','origin','quantity','note','status'];
    protected $primaryKey = 'id';
    public $timestamps = true;

    public function providers(): BelongsToMany
    {
        return $this->belongsToMany(Provider::class, 'provider_materials');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'material_categories');
    }

    public function MaterialExportReceiptDetails(): HasMany
    {
        return $this->hasMany(MaterialExportReceiptDetail::class, 'material_id');
    }

    public function MaterialImportReceiptDetails(): HasMany
    {
        return $this->hasMany(MaterialImportReceiptDetail::class, 'material_id');
    }

    public function MaterialInventories()
    {
        return $this->hasMany(MaterialInventory::class,'material_id');
    }

    /**
     * @throws Exception
     */
    public function validate(array $data, bool $isUpdate = false) : string
    {
        $validators = [
            'name' => v::notEmpty()->setName('name')->setTemplate('Tên không được rỗng'),
            'unit' => v::notEmpty()->setName('unit')->setTemplate('Đơn vị không được rỗng'),
            'weight' => v::notEmpty()->setName('weight')->setTemplate('Khối lượng không được rỗng'),
            'origin' => v::notEmpty()->setName('origin')->setTemplate('Nguồn xuất xứ không được rỗng'),
            'quantity' => v::notEmpty()->setName('quantity')->setTemplate('Số lượng không được rỗng'),
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