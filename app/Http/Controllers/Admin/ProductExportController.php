<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductsExport;

class ProductExportController extends Controller
{
    public function export()
    {
        return Excel::download(new ProductsExport, 'products.xlsx');
    }
}

