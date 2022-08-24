<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exceptions\bookstoreexception;

use App\Models\Book;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;


class BookController extends Controller
{
    //AAING BOOK IN BOOK CONTROLLER
    public function addingBook(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'description' => 'required|string|between:5,1000',
            'author' => 'required|string|between:5,300',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'Price' => 'required|integer',
            'quantity' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        try {
            $currentUser = JWTAuth::parseToken()->authenticate();
            if ($currentUser) {
                $book = new Book();
                $adminId = $book->adminOrUserVerification($currentUser->id);
                if (count($adminId) == 0) {
                    throw new bookstoreexception("NOT AN ADMIN", 404);
                }

                $bookDetails = Book::where('name', $request->name)->first();
                if ($bookDetails) {
                    throw new bookstoreexception("Book is already exist in there", 401);
                }

                $book->saveBookDetails($request, $currentUser)->save();
            } else {
                Log::error('Invalid User');
                throw new bookstoreexception("Invalid authorization token", 404);
            }

            Cache::remember('books', 3600, function () {
                return DB::table('books')->get();
            });

            Log::info('book created', ['admin_id' => $book->user_id]);

            return response()->json([
                'message' => 'Book created successfully'
            ], 201);
        } catch (bookstoreexception $exception) {
            return $exception->message();
        }
    }
}

   
