<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\RcDpwListRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use App\Imports\RcdpwListImport;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\HeadingRowImport;
use App\Helpers\HitCompare;
use App\Helpers\HitApi;
use App\Models\Church;
use App\Models\Personel;
use App\Models\RcDpwList;
use Excel;
use Exception;

/**
 * Class RcDpwListCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class RcDpwListCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\RcDpwList::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/rcdpwlist');
        CRUD::setEntityNameStrings('RC / DPW', 'Regional Council / DPW List');
        if (backpack_user()->hasAnyRole(['Editor','Viewer']))
        {
            $this->crud->denyAccess('list');
        }
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        $this->crud->addButtonFromView('top', 'uploadrcdpw', 'uploadrcdpw', 'beginning');

        // $this->crud->addColumn([
        //     'name' => 'id', // The db column name
        //     'label' => "ID", // Table column heading
        //     'type' => 'number'
        // ]);

        $this->crud->addColumn([
            'name'      => 'row_number',
            'type'      => 'row_number',
            'label'     => 'No.',
            'orderable' => false,
        ])->makeFirstColumn();

        $this->crud->addColumn([
            'name' => 'rc_dpw_name', // The db column name
            'label' => "Regional Council / DPW Names", // Table column heading
            'type' => 'text'
        ]);
    }

    public function uploadrcdpw(Request $request)
    {
        $status = 'Successfully Done';
        $status_error = 'Invalid File';

        $request->validate(['fileToUpload'=>'required|file|mimes:xls,xlsx']);
        $headings = (new HeadingRowImport)->toArray($request->fileToUpload);

        $currentheading = $headings[0] ?? [];
        $currentheading = $currentheading[0] ?? [];
        $correctheading = [0 => "dpw"];
        
        foreach($currentheading as $current){
            $index = array_search(strtolower($current), $correctheading);
            if ($index !== false) {
                unset($correctheading[$index]);
            }
        }

        if(count($correctheading)!=0){
            return redirect ( backpack_url ('import-rcdpw'))->with(['status_error' => $status_error]);
        }

        // 
        DB::beginTransaction();
        try{

            try{

                $rcdpwlistImport = new RcdpwListImport;
                $rcdpwlistImport->import(request()->file('fileToUpload'));

                DB::commit();

                if(count($rcdpwlistImport->ids) > 0){
                    $send = new HitApi;
                    $ids = $rcdpwlistImport->ids;
                    $module = 'rec_dpwlist';
                    $response = $send->action($ids, 'create', $module)->json();
                }

            }catch(\Maatwebsite\Excel\Validators\ValidationException $e){
               $failures = $e->failures();
               $arr_errors = [];
               foreach ($failures as $failure) {
                   $arr_errors[] = [
                       'row' => $failure->row(),
                       'errormsg' => $failure->errors(),
                       'values' => $failure->values(),
                   ];
               }
               $error_multiples = collect($arr_errors)->unique('row');
               DB::rollback();
            }

        }catch(Exception $e){
            throw $e;
            DB::rollback();
        }

        // Excel::import(new RcdpwListImport, request()->file('fileToUpload'));

        return back()->with(['status' => $status]);
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(RcDpwListRequest::class);

        $this->crud->addField([
            'name' => 'rc_dpw_name',
            'type' => 'text',
            'label' => "Regional Council / DPW Names"
        ]);
    }

    /**
     * Define what happens when the Update operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    public function store()
    {
        $this->crud->hasAccessOrFail('create');

        // execute the FormRequest authorization and validation, if one is required
        $request = $this->crud->validateRequest();

        // insert item in the db
        $item = $this->crud->create($this->crud->getStrippedSaveRequest());
        $this->data['entry'] = $this->crud->entry = $item;

        // show a success message
        \Alert::success(trans('backpack::crud.insert_success'))->flash();

        // save the redirect choice for next time
        $this->crud->setSaveAction();

        // hit api for create rcdpwlist
        $send = new HitApi;
        $ids = [$item->getKey()];
        $module = 'region';
        $response = $send->action($ids, 'create', $module)->json();

        return $this->crud->performSaveAction($item->getKey());
    }

    public function update()
    {
        $this->crud->hasAccessOrFail('update');

        // execute the FormRequest authorization and validation, if one is required
        $request = $this->crud->validateRequest();

        $item_previous = $this->crud->getEntry($request->get($this->crud->model->getKeyName()))->toArray();

        $hitCompare = new HitCompare;
        $hitCompare->addFieldCompare(
            [
               'rc_dpw_name' => 'rc_dpw_name'
            ], 
        $request->all());

        $com = $hitCompare->compareData($item_previous);

        // update the row in the db
        $item = $this->crud->update($request->get($this->crud->model->getKeyName()),
                            $this->crud->getStrippedSaveRequest());
        $this->data['entry'] = $this->crud->entry = $item;
        

        if($com){
            $send = new HitApi;
            $ids = [$item->getKey()];
            $module = 'region';
            $response = $send->action($ids, 'update', $module)->json();
        }

        // show a success message
        \Alert::success(trans('backpack::crud.update_success'))->flash();

        // save the redirect choice for next time
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($item->getKey());
    }

    public function destroy($id)
    {
        $this->crud->hasAccessOrFail('delete');

        $id = $this->crud->getCurrentEntryId() ?? $id;
        DB::beginTransaction();
        try {

            $personel = Personel::where('rc_dpw_id', $id)->first();
            if(isset($personel)){
                $personel->rc_dpw_id = null;
                $personel->save();
            }
    
            $church = Church::where('rc_dpw_id', $id)->first();
            if(isset($church)){
                DB::rollBack();

                return false;
            }
    
            $delete = $this->crud->delete($id);
    
            $send = new HitApi;
            $ids = [$id];
            $module = 'region';
            $send->action($ids, 'delete', $module)->json();
            
            DB::commit();

            return $delete;
        } catch (\Throwable $th) {
            return false;
        }
       
    }

    public function ajaxRcdpw()
    {
        $draw = request('draw');
        $start = request("start");
        $rowperpage = 10;
        if (request("length")) {
            $rowperpage = request("length");
        }
        $filters = [];

        $order_arr = request('order');
        $searchArr = request('search');

        $searchValue = $searchArr['value']; // Search value

        // Total records
        $countDeliveryStatuses = RcDpwList::count();
        $totalRecords = $countDeliveryStatuses;
        $totalRecordswithFilter = RcDpwList::where($filters)
                        ->where(function($query) use ($searchValue){
                            $query->where('rc_dpw_name','LIKE', '%'.$searchValue.'%');
                        })
                        ->count();

        $rcdpws = RcDpwList::where($filters)
            ->where(function($query) use ($searchValue){
                $query->where('rc_dpw_name','LIKE', '%'.$searchValue.'%');
            })
            ->skip($start)
            ->take($rowperpage)
            ->get();

        $tableBodies = [];
        foreach ($rcdpws as $key => $ds) {
            $tableBody = [];
            $tableBody[] = $ds->id;
            $tableBody[] = $ds->rc_dpw_name;
            array_push($tableBodies, $tableBody);
        }


        $response = array(
           "draw" => intval($draw),
           "iTotalRecords" => $totalRecords,
           "iTotalDisplayRecords" => $totalRecordswithFilter,
           "aaData" => $tableBodies
        );

        return $response;
    }




}
