<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use App\Models\Order;
use App\Repositories\Orders\OrderRepository;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf  as PDF;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    private $orderRepository;
    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }
    public function index(Request $request)
    {
        return $this->orderRepository->index($request);
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
        $pricing_method = getCalculatingPriceOfOrdersMethod();
        return $this->orderRepository->storeWithFifo($request);
        if ($pricing_method == 'fifo') {
            return $this->orderRepository->storeWithFifo($request);
        } else if ($pricing_method == 'from_unit_prices') {
            return $this->orderRepository->storeWithUnitPricing($request);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id) {}

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
        return $this->orderRepository->update($request, $id);
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
     * Check if user has order pending for approval
     * @param int $branch_id
     */

    public function export($id)
    {
        return $this->orderRepository->export($id);
    }
    public function exportTransfer($id)
    {
        return $this->orderRepository->exportTransfer($id);
    }

    public function generate(Request $request, Order $order)
    {
        $order->load(['orderDetails.product', 'branch', 'customer']); // eager load data

        $pdf = PDF::loadView('pdf.order', compact('order'));

        return $pdf->download("order_{$order->id}.pdf");
    }

    public function printDeliveryOrder($orderId)
    {
        $order = Order::with(['orderDetails.product', 'branch', 'logs.creator'])->findOrFail($orderId);
        $deliveryInfo = $order->getDeliveryInfo();

        if (!$deliveryInfo) {
            abort(404, 'Order has not been marked as delivered yet.');
        }

        return view('pdf.delivery-order', compact('deliveryInfo'));
    }
}
