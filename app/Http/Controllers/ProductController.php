<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use App\Repositories\Products\ProductRepository;
use App\Repositories\Products\V2\ProductRepository as V2ProductRepository;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    private $productRepository;
    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function index(Request $request)
    {
        return $this->productRepository->index($request);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
    /**
     * get products report
     */
    public function reportProducts(Request $request)
    {
        return $this->productRepository->report($request);
    }
    public function reportProductsv2(Request $request)
    {
        return $this->productRepository->reportv2($request);
    }
    public function reportProductsv2Details(Request $request, $category_id)
    {
        return $this->productRepository->reportv2Details($request, $category_id);
    }
    public function getProductOrderQuantities(Request $request)
    {
        return $this->productRepository->getProductsOrdersQuntities($request);
    }

    public function getProductOrderQuantitiesV2(Request $request, V2ProductRepository $repo)
    {
        return $repo->getProductsOrdersQuntitiesPaginated($request);
    }
}
