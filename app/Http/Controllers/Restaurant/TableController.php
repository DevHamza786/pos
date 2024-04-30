<?php

namespace App\Http\Controllers\Restaurant;

use Datatables;
use Validator;
use App\BusinessLocation;
use App\Restaurant\ResTable;
use Illuminate\Http\Request;

use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Routing\Controller;
use App\Restaurant\ResTablePicture;

class TableController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        if (!auth()->user()->can('access_tables')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $tables = ResTable::where('res_tables.business_id', $business_id)
                ->join('business_locations AS BL', 'res_tables.location_id', '=', 'BL.id')
                ->select([
                    'res_tables.name as name', 'BL.name as location',
                    'res_tables.description', 'res_tables.id'
                ]);

            return Datatables::of($tables)
                ->addColumn(
                    'action',
                    '@role("Admin#' . $business_id . '")
                    <button data-href="{{action(\'Restaurant\TableController@edit\', [$id])}}" class="btn btn-xs btn-primary edit_table_button"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</button>
                        &nbsp;
                    @endrole
                    @role("Admin#' . $business_id . '")
                        <button data-href="{{action(\'Restaurant\TableController@destroy\', [$id])}}" class="btn btn-xs btn-danger delete_table_button"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</button>
                    @endrole'
                )
                ->removeColumn('id')
                ->escapeColumns(['action'])
                ->make(true);
        }

        return view('restaurant.table.index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        if (!auth()->user()->can('access_tables')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id);

        return view('restaurant.table.create')
            ->with(compact('business_locations'));
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        // dd($request->all());
        if (!auth()->user()->can('access_tables')) {
            abort(403, 'Unauthorized action.');
        }

        // Validate the request data
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('res_tables')->where(function ($query) use ($request) {
                    // Check for uniqueness within the current business_id
                    return $query->where('business_id', $request->session()->get('user.business_id'));
                }),
            ],
            'charges' => 'required|numeric|min:0.01',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->toArray()
            ], 400);
        }

        try {

            $input = $request->only(['name', 'charges', 'description', 'location_id']);
            $business_id = $request->session()->get('user.business_id');
            $input['business_id'] = $business_id;
            $input['created_by'] = $request->session()->get('user.id');
            $table = ResTable::create($input);
            if ($table) {
                // Loop through each uploaded picture
                foreach ($request->file('pictures') as $picture) {
                    // Save the picture to the storage
                    $path = $picture->store('table_pictures');
                    $tablePicture = new ResTablePicture();
                    $tablePicture->table_id = $table->id;
                    $tablePicture->path = $path;
                    $tablePicture->name = $picture->getClientOriginalName();
                    $tablePicture->save();
                }
            }
            $output = [
                'success' => true,
                'data' => $table,
                'msg' => __("lang_v1.added_success")
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }
        return $output;
    }

    /**
     * Show the specified resource.
     * @return Response
     */
    public function show()
    {
        if (!auth()->user()->can('access_tables')) {
            abort(403, 'Unauthorized action.');
        }

        return view('restaurant.table.show');
    }

    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('access_tables')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $table = ResTable::with('tablePicture')->where('business_id', $business_id)->find($id);
            return view('restaurant.table.edit')
                ->with(compact('table'));
        }
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('access_tables')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $input = $request->only(['name', 'charges', 'description', 'location_id']);
                $business_id = $request->session()->get('user.business_id');

                $table = ResTable::where('business_id', $business_id)->findOrFail($id);
                $table->name = $input['name'];
                $table->charges = $input['charges'];
                $table->description = $input['description'];
                $table->save();

                if($request->file('pictures')){
                    $tablePicture = ResTablePicture::where('table_id',$id);
                    $tablePicture->delete();
                    if($tablePicture->count() == 0){
                        foreach ($request->file('pictures') as $picture) {
                            // Save the picture to the storage
                            $path = $picture->store('table_pictures');
                            $tablePicture = new ResTablePicture();
                            $tablePicture->table_id = $id;
                            $tablePicture->path = $path;
                            $tablePicture->name = $picture->getClientOriginalName();
                            $tablePicture->save();
                        }
                    }
                }

                $output = [
                    'success' => true,
                    'msg' => __("lang_v1.updated_success")
                ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

                $output = [
                    'success' => false,
                    'msg' => __("messages.something_went_wrong")
                ];
            }

            return $output;
        }
    }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('access_tables')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->user()->business_id;

                $table = ResTable::where('business_id', $business_id)->findOrFail($id);
                $table->delete();

                $output = [
                    'success' => true,
                    'msg' => __("lang_v1.deleted_success")
                ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

                $output = [
                    'success' => false,
                    'msg' => __("messages.something_went_wrong")
                ];
            }

            return $output;
        }
    }
}
