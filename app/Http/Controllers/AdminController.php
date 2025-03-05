<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;

class AdminController extends Controller
{
    public function index() {
        return view('admin.index');
    }

    public function brands() {
        $brands = Brand::orderBy('id', 'desc')->paginate(10);
        return view('admin.brands', compact('brands'));
    }

    public function add_brand() {
        return view('admin.brand_add');
    }

    public function brand_store(Request $request) {
        $request->validate([
            'name' => 'required|string|max:255',
             'slug'=>'required|unique:brand,slug',
            'image' => 'nullable|mimes:png,jpg,jpeg|max:2048'
        ]);

        DB::beginTransaction();
        try {
            $brand = new Brand();
            $brand->name = $request->name;

            // Generate a unique slug
            $slug = Str::slug($request->name);
            $count = Brand::where('slug', 'LIKE', "{$slug}%")->count();
            $brand->slug = $count ? "{$slug}-" . ($count + 1) : $slug;

            // Handle Image Upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $file_extension = $image->extension();
                $file_name = Carbon::now()->timestamp . '.' . $file_extension;

                // Ensure the directory exists
                File::ensureDirectoryExists(public_path('uploads/brands'));

                $this->GenerateBrandThumbnailsImage($image, $file_name);
                $brand->image = $file_name;
            }

            $brand->save();
            DB::commit();

            return redirect()->route('admin.brands')->with('status', 'Brand has been successfully created');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('An error occurred while saving the brand.');
        }
    }

    private function GenerateBrandThumbnailsImage($image, $imageName) {
        $destinationPath = public_path('uploads/brands');
        $img = Image::make($image)->fit(124, 124);
        $img->save($destinationPath . '/' . $imageName);
    }

    public function brand_edit($id) {
        $brand =Brand::find($id);
        return view('admin.brand_edit',compact('brand'));
    }

    public function brand_update(Request $request) {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug'=>'required|unique:brand,slug',
            'image' => 'nullable|mimes:png,jpg,jpeg|max:2048'
        ]);

    }
}
