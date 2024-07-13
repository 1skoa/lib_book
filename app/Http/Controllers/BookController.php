<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Imagick\Driver;
use Illuminate\Support\Facades\File;
class BookController extends Controller
{
    public function index()
    {
        return Book::all();
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'width' => 'required|integer|min:1',
            'height' => 'required|integer|min:1',
        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '.png';
            $directory = 'public/images';
            $path = $directory . '/' . $filename;

            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }
            $manager = new ImageManager(new Driver());
            $width = $request->input('width', 300);
            $height = $request->input('height', 300);

            $image = $manager->read($image)
                ->resize(300, 300)
                ->crop($width, $height)
                ->save($path);

            $book = new Book();
            $book->title = $request->title;
            $book->author = $request->author;
            $book->description = $request->description;
            $book->image = $filename;
            $book->save();

            return response()->json(['message' => 'Image uploaded and cropped successfully', 'path' => $path]);
        }

        return response()->json(['message' => 'No image uploaded'], 400);
    }

    public function show(Book $id)
    {
        return response()->json($id);
    }

    public function update(Request $request, $id)
    {
        $book = Book::findOrFail($id);

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'author' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:png,jpeg,jpg|max:2048',
            'width' => 'sometimes|integer|min:1',
            'height' => 'sometimes|integer|min:1',
        ]);
        if ($request->has('title')) {
            $book->title = $request->title;
        }
        if ($request->has('author')) {
            $book->author = $request->author;
        }
        if ($request->has('description')) {
            $book->description = $request->description;
        }
        if ($request->hasFile('image')) {
            if ($book->image) {
                $oldPath = storage_path('public/images' . $book->image);
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            $image = $request->file('image');
            $filename = time() . '.png';
            $path = storage_path('public/images' . $filename);
            if (!File::exists(storage_path('public/images'))) {
                File::makeDirectory(storage_path('public/images'), 0755, true);
            }

            $manager = new ImageManager(new Driver());
            $width = $request->input('width');
            $height = $request->input('height');

            $manager->read($image->getPathname())
                ->resize(300, 300)
                ->crop($width, $height)
                ->save($path);

            $book->image = 'public/images' . $filename;
        }
        $book->save();

        return response()->json($book);
    }

    public function destroy($id)
    {
        $book = Book::findOrFail($id);

        if ($book->image) {
            $oldPath = storage_path('app/public/' . $book->image);
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        $book->delete();
        return response()->json(null, 204);
    }
}
