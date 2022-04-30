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
    private $URL_DEFAULT_IMAGE;

    // Construct
    function __construct()
    {
        $this->apiResult = ApiResult::getInstance();
        $this->URL_DEFAULT_IMAGE = SupportFunction::get_url_sever().'/images/products/default.png';
    }
    // Add New Product
    public function addNewProduct(Request $request)
    {
        try {
            $userLogin = $request->user();

            $isAdmin = User::join('user_types', 'users.type_id', '=', 'user_types.id')
            ->where([
                ['user_types.rule', '=', 'ADMIN'],
                ['users.id', '=', $userLogin->id]
            ])
                ->select('users.*')
                ->first();
            if ($isAdmin) {
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
            } else {
                $this->apiResult->setError("You are not ADMIN type");
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
}
