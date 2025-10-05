<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Product::select('id', 'title', 'slug', 'price', 'stock', 'category_id', 'created_at')->get();
    }

    public function headings(): array
    {
        return ['ID', 'Title', 'Slug', 'Price', 'Stock', 'Category', 'Created At'];
    }
}

