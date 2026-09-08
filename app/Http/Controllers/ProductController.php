<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use App\Repositories\Products\ProductRepository;
use App\Repositories\Products\V2\ProductRepository as V2ProductRepository;
use App\Models\Branch;
use App\Modules\Stock\Reports\OrderTransfersReports\Actions\FetchOrderTransferReportAction;
use App\Modules\Stock\Reports\OrderTransfersReports\DTOs\OrderTransferReportFilterDTO;
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

    public function getProductOrderQuantitiesV3(Request $request, FetchOrderTransferReportAction $action)
    {
        $currentPage = (int) $request->input('page', 1);
        $perPage     = (int) $request->input('per_page', 200);

        $branchIds = $request->input('branch_id');
        if (function_exists('isBranchManager') && isBranchManager()) {
            $branchIds = [function_exists('getBranchId') ? getBranchId() : null];
            $branchIds = array_filter($branchIds);
        } else {
            if (is_string($branchIds)) {
                $branchIds = array_filter(explode(',', $branchIds));
            } elseif (is_numeric($branchIds)) {
                $branchIds = [(int) $branchIds];
            } elseif (!is_array($branchIds)) {
                $branchIds = [];
            }
        }

        if (empty($branchIds)) {
            $branchIds = Branch::whereIn('type', [
                Branch::TYPE_BRANCH, Branch::TYPE_CENTRAL_KITCHEN, Branch::TYPE_POPUP,
            ])->activePopups()->active()->pluck('id')->toArray();
        }

        $categoryIds = $request->input('category_id');
        if (is_string($categoryIds)) {
            $categoryIds = array_filter(explode(',', $categoryIds));
        } elseif (is_numeric($categoryIds)) {
            $categoryIds = [(int) $categoryIds];
        } elseif (!is_array($categoryIds)) {
            $categoryIds = [];
        }

        $filters = [
            'branch_id'    => $branchIds,
            'start_date'   => $request->input('start_date', $request->input('from_date')),
            'end_date'     => $request->input('end_date', $request->input('to_date')),
            'product_id'   => $request->input('product_id'),
            'category_id'  => $categoryIds,
            'order_number' => $request->input('order_number'),
        ];

        $filterDTO = OrderTransferReportFilterDTO::fromArray($filters, $currentPage, $perPage);
        $result = $action->execute($filterDTO);

        $paginator = $result['paginator'];

        return response()->json([
            'success'                  => true,
            'data'                     => $paginator->items(),
            'grand_total'              => $result['grand_total'],
            'current_page_total'       => $result['current_page_total'],
            'current_page_price_total' => $result['current_page_price_total'],
            'dataTotal'                => [
                'grand_total'              => $result['grand_total'],
                'current_page_total'       => $result['current_page_total'],
                'current_page_price_total' => $result['current_page_price_total'],
            ],
            'meta'                     => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
            'links'                    => [
                'next' => $paginator->nextPageUrl(),
                'prev' => $paginator->previousPageUrl(),
                'self' => $paginator->url($paginator->currentPage()),
            ],
        ]);
    }
}
