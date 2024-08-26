<?php

namespace App\Interfaces\Products;

interface ProductRepositoryInterface
{
    public function index($request);
    public function report($request);
}
