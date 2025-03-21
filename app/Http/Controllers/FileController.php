<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\ProcessCSV;
use App\Models\Products;
use App\Models\Uploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class FileController extends Controller {
    public function upload(Request $request) {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimetypes:text/csv'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Unsuccessful upload'
            ], 415);
        }
        
        $userId = $request->user()['id'];
        $file = $request->file('file');

        $upload = new Uploads([
            'users_id' => $userId,
            'file_path' => $file->hashName(),
            'status' => 'pending',
            'created_at' => now()
        ]);
        if(!$upload->save()) {
            return response()->json([
                'message' => 'Error while saving uploads data'
            ], 500);
        }

        $filename = $file->store();
        
        ProcessCSV::dispatch($filename, $userId, $request->user()['email'], 0);
    
        return response()->json([
            'filename' => $filename
        ]);
    }

    public function products(Request $request) {
        $userData = $request->user();
        $products = Products::where('users_id', $userData['id'])
            ->get();

        if (!empty($products) && !$request->user()->can('view', $products[0])) {
            return response()->json([
                'message' => 'Data not found',
            ], 404);
        }

        return response()->json([
            'products' => $products
        ]);
    }

    public function status(Request $request, $uploadId) {
        $products = Uploads::where('id', $uploadId)
            ->first();

        if (empty($products) || !$request->user()->can('view', $products)) {
            return response()->json([
                'message' => 'Data not found',
            ], 404);
        } else {
            return response()->json([
                'status' => $products->status,
            ]);
        }
    }
}
