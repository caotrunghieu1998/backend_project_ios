<?php

namespace App\Http\Controllers\Api;

use App\ApiResult\ApiResult;
use App\Http\Controllers\Controller;
use App\Product;
use App\SupportFunction\SupportFunction;
use App\User;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Http\Request;
use Intervention\Image\ImageManagerStatic;

class ProductController extends Controller
{
    private $apiResult;
    private $URL_SAVE_IMAGE = 'images/products/';
    private $URL_DEFAULT_IMAGE = '/images/products/default.png';

    // Construct
    function __construct()
    {
        $this->apiResult = ApiResult::getInstance();
    }
    // Add New Product
    public function addNewProduct(Request $request)
    {
        try {
            // Check field
            $validator = Validator::make($request->all(), [
                'name'              => 'required|min:2|max:200',
                'price'             => 'required|integer|min:0',
                'quantity'          => 'required|integer|min:1',
                'type'              => 'required|min:1',
                'unit'              => 'required|min:1',
            ]);
            if ($validator->fails()) {
                $this->apiResult->setError("Some field is not true");
            } else {
                // Check Name Product
                $checkProductName = Product::where('name', $request->name)->first();
                if ($checkProductName) {
                    $this->apiResult->setError("This name has been used");
                } else {
                    // Create Product
                    $product = Product::create([
                        'name' => $request->name,
                        'price' => $request->price,
                        'quantity' => $request->quantity,
                        'type' => $request->type,
                        'unit' => $request->unit,
                    ]);
                    // Check the image of product
                    $image = $request->file('image');
                    if ($image) {
                        // Get file name
                        $image_name = $image->getClientOriginalName();
                        $arr = explode('.', $image_name);
                        $check_file = end($arr);
                        if ($check_file) {
                            if (
                                strtolower($check_file) == 'jpg' ||
                                strtolower($check_file) == 'jpeg' ||
                                strtolower($check_file) == 'png'
                            ) {
                                // Insert images
                                $avatar_name = $product->id . '.' . $image->getClientOriginalExtension();
                                $product->image = $this->URL_SAVE_IMAGE . $avatar_name;
                                $product->save();
                                // Save image 600 x 600
                                $image_resize = ImageManagerStatic::make($image->getRealPath());
                                $image_resize->resize(600, 600);
                                $image_resize->save(public_path($this->URL_SAVE_IMAGE . $avatar_name));
                            }
                        }
                    }
                    $this->apiResult->setData("Create Product Success");
                }
            }
        } catch (Exception $ex) {
            $this->apiResult->setError(
                "System error when create product",
                $ex->getMessage()
            );
        } finally {
            return response($this->apiResult->toResponse());
        }
    }

    // Get List Product
    public function getListProduct(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'screen'    => 'required|string|min:2|max:200',
            ]);
            if ($validator->fails()) {
                $this->apiResult->setError("What is your screen ?");
            } else {
                // Get list product
                $query = Product::select('products.*')
                ->orderBy('products.id');
                if ($request->has('keyword')) {
                    $query->where('products.name', 'like', '%' . $request->keyword . '%');
                }
                if ($request->screen == "SHOP") {
                    $query->where('products.isActive', '=', true);
                }
                $listProduct = $query->get();
                foreach ($listProduct as $product) {
                    if($product->image == null){
                        $product->image = $this->URL_DEFAULT_IMAGE;
                    }
                }
                $this->apiResult->setData($listProduct);
            }
            return response($this->apiResult->toResponse());
        } catch (Exception $ex) {
            $this->apiResult->setError(
                "System error when get List product",
                $ex->getMessage()
            );
            return response($this->apiResult->toResponse());
        }
    }

    // UPDATE Product
    public function updateProduct(Request $request)
    {
        try {
            // Check field
            $validator = Validator::make($request->all(), [
                'id'                => 'required|min:1',
                'name'              => 'required|min:2|max:200',
                'price'             => 'required|integer|min:0',
                'quantity'          => 'required|integer|min:1',
                'type'              => 'required|min:1',
                'unit'              => 'required|min:1',
            ]);
            if ($validator->fails()) {
                $this->apiResult->setError("Some field is not true");
            } else {
                // Check Name Product
                $checkProductName = Product::where('name', $request->name)
                    ->where('id', '!=', $request->id)
                    ->first();
                if ($checkProductName) {
                    $this->apiResult->setError("This name has been used");
                } else {
                    // Check product
                    $checkProduct = Product::where('id', $request->id)->first();
                    if($checkProduct){
                        // Update Product
                        $checkProduct
                        ->update([
                            'name' => $request->name,
                            'price' => $request->price,
                            'quantity' => $request->quantity,
                            'type' => $request->type,
                            'unit' => $request->unit,
                        ]);
                        // Check the image of product
                        $image = $request->file('image');
                        if ($image) {
                            // Get file name
                            $image_name = $image->getClientOriginalName();
                            $arr = explode('.', $image_name);
                            $check_file = end($arr);
                            if ($check_file) {
                                if (
                                    strtolower($check_file) == 'jpg' ||
                                    strtolower($check_file) == 'jpeg' ||
                                    strtolower($check_file) == 'png'
                                ) {
                                    // Insert images
                                    $avatar_name = $checkProduct->id . '.' . $image->getClientOriginalExtension();
                                    $checkProduct->image = $this->URL_SAVE_IMAGE . $avatar_name;
                                    $checkProduct->save();
                                    // Save image 600 x 600
                                    $image_resize = ImageManagerStatic::make($image->getRealPath());
                                    $image_resize->resize(600, 600);
                                    $image_resize->save(public_path($this->URL_SAVE_IMAGE . $avatar_name));
                                }
                            }
                        }
                        $this->apiResult->setData("Update Product Success");
                    } else {
                        $this->apiResult->setError("Not found product");
                    }
                }
            }
        } catch (Exception $ex) {
            $this->apiResult->setError(
                "System error when Update product",
                $ex->getMessage()
            );
        } finally {
            return response($this->apiResult->toResponse());
        }
    }

    // Change product active Status
    public function changeActiveStatus(Request $request)
    {
        try {
            // Find product
            $product = Product::where([
                ['id', '=', $request->id]
            ])
                ->first();
            if (!$product) {
                $this->apiResult->setError("Cannot find the product");
            } else {
                $productStatus = $product->isActive == 1;
                $product->isActive = !$productStatus;
                $product->save();
                $message = $productStatus == true ?
                    "Deactive staff \"" . $product->name . "\" success" :
                    "Active staff \"" . $product->name . "\" success";
                $this->apiResult->setData($message);
            }
            return response($this->apiResult->toResponse());
        } catch (Exception $ex) {
            $this->apiResult->setError(
                "System error when change active Status",
                $ex->getMessage()
            );
        } finally {
            return response($this->apiResult->toResponse());
        }
    }
}
