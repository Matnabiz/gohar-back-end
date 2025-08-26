<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'price'       => $this->price,
            'description' => $this->description,
            'main_image'  => $this->main_image
                ? asset('storage/' . $this->main_image)
                : null,
            'material'    => $this->material,
            'color'       => $this->color,
            'stock'       => $this->stock,
            'category'    => $this->whenLoaded('category', function () {
                return [
                    'id'         => $this->category->id,
                    'name'       => $this->category->name,
                    'breadcrumb' => $this->category->breadcrumb,
                ];
            }),
        ];
    }
}
