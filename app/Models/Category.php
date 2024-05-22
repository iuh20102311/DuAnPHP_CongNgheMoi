<?php

namespace App\Models;

use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\ValidationException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    use HasFactory;
    protected $table = 'categories';
    protected $fillable = ['name', 'type','status','created_at','updated_at'];
    protected $primaryKey = 'id';
    public $timestamps = true;

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_categories');
    }

    public function discounts(): BelongsToMany
    {
        return $this->belongsToMany(Discount::class, 'category_discounts');
    }

    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(Material::class, 'material_categories');
    }

    /**
     * @throws \Exception
     */
    public function validate(array $data)
    {
        $validators = [
            'name' => v::notEmpty()->setName('name')->setTemplate('Tên không được rỗng'),
            'type' => v::notEmpty()->setName('type')->setTemplate('Loại không được rỗng'),
            'status' => v::notEmpty()->setName('status')->setTemplate('Trạng thái không được rỗng'),
        ];

        $errors = [];
        foreach ($validators as $field => $validator) {
            try {
                $validator->assert(isset($data[$field]) ? $data[$field] : null);
            } catch (ValidationException $exception) {
                $errors[$field] = $exception->getMessages();
            }
        }

        if (!empty($errors)) {
            throw new \Exception(json_encode(['errors' => $errors]), 400);
        }
    }
}