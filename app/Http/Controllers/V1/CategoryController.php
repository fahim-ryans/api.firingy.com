<?php

namespace App\Http\Controllers\V1;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function index()
    {
        $data = array();

        $rootCategories = $this->_getCategories(1);

        foreach ($rootCategories as $rootCategory) {
            $data[$rootCategory->category_name]['clickable'] = $rootCategory->category_is_linkable;
            $data[$rootCategory->category_name]['slug'] = $rootCategory->category_slug;
            $data[$rootCategory->category_name]['mask'] = $rootCategory->category_mask;

            $categories = $this->_getCategories($rootCategory->category_id);
            foreach ($categories as $category) {
                $data[$rootCategory->category_name][$category->category_name]['clickable'] = $category->category_is_linkable;
                $data[$rootCategory->category_name][$category->category_name]['slug'] = $category->category_slug;
                $data[$rootCategory->category_name][$category->category_name]['mask'] = $category->category_mask;

                $subCategories = $this->_getCategories($category->category_id);
                foreach ($subCategories as $key => $subCategory) {
                    $data[$rootCategory->category_name][$category->category_name][$subCategory->category_name]['clickable'] = $subCategory->category_is_linkable;
                    $data[$rootCategory->category_name][$category->category_name][$subCategory->category_name]['slug'] = $subCategory->category_slug;
                    $data[$rootCategory->category_name][$category->category_name][$subCategory->category_name]['mask'] = $subCategory->category_mask;
                }
            }
        }

        return response()->json($data);
    }

    protected function _getCategories($categoryParentId)
    {
        return Category::select(
            'category_id',
            'category_name',
            'category_mask',
            'category_slug',
            'category_is_linkable'
        )
            ->orderBy('category_position', 'ASC')
            ->orderBy('category_name', 'ASC')
            ->where('category_is_exist', '=', '1')
            ->where('category_parent_id', '=', $categoryParentId)
            ->get();
    }
}
