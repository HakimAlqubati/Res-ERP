<?php

namespace App\Http\Controllers;

use App\Imports\ImportCategories;
use App\Imports\ImportItemTypes;
use App\Imports\ImportProducts;
use App\Imports\ImportPurchaseInvoiceDetails;
use App\Imports\ImportUnitPrices;
use App\Imports\ImportUnits;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
class ImportController extends Controller
{
    public function import_products_view()
    {
        return view('import_products');
    }
    public function import_purchase_invoice_details_view()
    {
        return view('import_purchase_invoice_details');
    }

    public function import_units_view()
    {
        return view('import_units');
    }

    public function import_item_types_view()
    {
        return view('import_item_types');
    }

    public function import_categories_view()
    {
        return view('import_categories');
    }


    public function import_unit_prices_view()
    {
        return view('import_unit_prices');
    }

    public function importProducts(Request $request)
    {
         
        try {
            Excel::import(new ImportProducts, $request->file('file')->store('files'));
        } catch (\Exception $e) {
            // Print or log the exception
            echo "Exception: " . $e->getMessage();
        }

        return redirect()->back();
    }

    public function importpurchaseInvoiceDetails(Request $request)
    {
         
        try {
            Excel::import(new ImportPurchaseInvoiceDetails, $request->file('file')->store('files'));
        } catch (\Exception $e) {
            // Print or log the exception
            echo "Exception: " . $e->getMessage();
        }

        return redirect()->back();
    }

    public function importCategories(Request $request)
    {
         
        try {
            Excel::import(new ImportCategories, $request->file('file')->store('files'));
        } catch (\Exception $e) {
            // Print or log the exception
            echo "Exception: " . $e->getMessage();
        }

        // return redirect()->back();
    }

    public function importUnits(Request $request)
    {
         
        try {
            Excel::import(new ImportUnits, $request->file('file')->store('files'));
        } catch (\Exception $e) {
            // Print or log the exception
            echo "Exception: " . $e->getMessage();
        }
        return redirect()->back()->with('success', 'done');
        return redirect()->back();
    }

    public function importItemTypes(Request $request)
    {
         
        try {
            Excel::import(new ImportItemTypes, $request->file('file')->store('files'));
        } catch (\Exception $e) {
            // Print or log the exception
            echo "Exception: " . $e->getMessage();
        }
        // return redirect()->back()->with('success', 'done');
        // return redirect()->back();
    }

    public function importUnitPrices(Request $request)
    {
         
        try {
            Excel::import(new ImportUnitPrices, $request->file('file')->store('files'));
        } catch (\Exception $e) {
            // Print or log the exception
            echo "Exception: " . $e->getMessage();
        }

        // return redirect()->back();
    }
}
