<?php

use App\Http\Controllers\Stock\ProductController;
use Illuminate\Support\Facades\Route;

Route::apiResource('/products', ProductController::class);
