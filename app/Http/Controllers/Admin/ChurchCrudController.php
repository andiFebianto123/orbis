<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\ChurchRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Models\StatusHistoryChurch;
use App\Models\Church;
use App\Models\CoordinatorChurch;
use App\Models\RelatedEntityChurch;
use App\Models\RcDpwList;
use App\Models\StructureChurch;
use App\Models\ChurchesRcdpw;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;
use App\Helpers\HitCompare;
use App\Helpers\HitApi;

/**
 * Class ChurchCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ChurchCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation{search as traitSearch;}
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation{store as traitStore;}
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation{update as traitUpdate;}
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Church::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/church');
        CRUD::setEntityNameStrings('Church / Office', 'Church & Office List');
        $this->crud->leftColumns = 2;
        $this->crud->rightColumns = 1; 
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        
        $this->crud->addColumn([
            'name'      => 'row_number',
            'type'      => 'row_number',
            'label'     => 'No.',
            'orderable' => false,
        ])->makeFirstColumn();

        $this->crud->addColumn([
            'name' => 'church_name', // The db column name
            'label' => "Church Name", // Table column heading
            'type' => 'text'
        ]);

        $this->crud->addColumn([
            'name' => 'church_status', // The db column name
            'label' => "Status", // Table column heading
            'type' => 'closure',
            'function' => function ($entry) {
                return $entry->status;
            },
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhere(DB::raw('IFNULL(status_history_churches.status, "-")'), 'LIKE', '%' . $searchTerm . '%');
            },
            'orderable' => true,
            'orderLogic' => function ($query, $column, $columnDirection) {
                return $query->orderBy(DB::raw('IFNULL(status_history_churches.status, "-")'), $columnDirection);
            },
        ]);

        // $this->crud->addColumn([
        //     'name' => 'rc_dpw', // The db column name
        //     'label' => "RC / DPW", // Table column heading
        //     'type' => 'relationship',
        //     'attribute' => 'rc_dpw_name',
        // ]);


        // $this->crud->addColumn([
        //     'label'     => 'RC / DPW', // Table column heading
        //     'type'      => 'select',
        //     'name'      => 'rc_dpw_name', // the column that contains the ID of that connected entity;
        //     'entity'    => 'churches_rcdpw.rcdpwlists', // the method that defines the relationship in your Model
        //     'attribute' => 'rc_dpw_name', // foreign key attribute that is shown to user
        //     'model'     => App\Models\ChurchesRcdpw::class, // foreign key model
        //     'limit' => 100,
        //     'searchLogic' => function ($query, $column, $searchTerm) {
        //         $query->orWhereHas('churches_rcdpw.rcdpwlists', function ($q) use ($column, $searchTerm) {
        //             $q->where('rc_dpw_name', 'like', '%'.$searchTerm.'%');
        //         });
        //     }
        // ]);

        $this->crud->addColumn([
            'name' => 'rc_dpw', // The db column name
            'label' => "RC / DPW", // Table column heading
            'type' => 'relationship',
            'attribute' => 'rc_dpw_name',
        ]);
        
        $this->crud->addColumn([
            'name' => 'church_type', // The db column name
            'label' => "Type", // Table column heading
            'type' => 'relationship',
            'attribute' => 'entities_type',
        ]);

        $this->crud->addColumn([
            'name' => 'contact_person', // The db column name
            'label' => "Contact Person", // Table column heading
            'type' => 'text'
        ]);

        $this->crud->addColumn([
            'name' => 'first_email', // The db column name
            'label' => "Email", // Table column heading
            'type' => 'text'
        ]);

        $this->crud->addColumn([
            'name' => 'phone', // The db column name
            'label' => "Phone", // Table column heading
            'type' => 'text'
        ]);

        $this->crud->addColumn([
            'name' => 'fax', // The db column name
            'label' => "Fax", // Table column heading
            'type' => 'text'
        ]);

        $this->crud->addColumn([
            'name' => 'church_address', // The db column name
            'label' => "Church Address", // Table column heading
            'type' => 'text'
        ]);

        $this->crud->addColumn([
            'name' => 'office_address', // The db column name
            'label' => "Office Address", // Table column heading
            'type' => 'text'
        ]);

        $this->crud->addColumn([
            'name' => 'city', // The db column name
            'label' => "City", // Table column heading
            'type' => 'text'
        ]);

        $this->crud->addColumn([
            'name' => 'province', // The db column name
            'label' => "State", // Table column heading
            'type' => 'text'
        ]);

        $this->crud->addColumn([
            'name' => 'postal_code', // The db column name
            'label' => "Postcode", // Table column heading
            'type' => 'text'
        ]);

        $this->crud->addColumn([
            'name' => 'country', // The db column name
            'label' => "Country", // Table column heading
            'type' => 'relationship',
            'attribute' => 'country_name',
        ]);

        $this->crud->addColumn([
            'name' => 'task_color',
            'label' => 'Task Color',
            'type' => 'closure',
            'function' => function($entry){
                return '<center><div style="width:15px; height: 15px; background-color:'.$entry->task_color.';"></div></center>';
            },
            'orderable' => false,
        ]);

        $this->crud->addColumn([
            'name' => 'latitude',
            'label' => 'Latitude',
            'type' => 'text',
        ]);

        $this->crud->addColumn([
            'name' => 'longitude',
            'label' => 'Longitude',
            'type' => 'text'
        ]);

    }

    function search()
    {
        $subQuery = StatusHistoryChurch::leftJoin('status_history_churches as temps', function ($leftJoin) {
            $leftJoin->on('temps.churches_id', 'status_history_churches.churches_id')
                ->where(function ($innerQuery) {
                    $innerQuery->whereRaw('status_history_churches.date_status < temps.date_status')
                        ->orWhere(function ($deepestQuery) {
                            $deepestQuery->whereRaw('status_history_churches.date_status = temps.date_status')
                                ->whereRaw('status_history_churches.id < temps.id');
                        });
                });
        })->whereNull('temps.id')
            ->select('status_history_churches.churches_id', 'status_history_churches.status');
        $this->crud->query->leftJoinSub($subQuery, 'status_history_churches', function ($leftJoinSub) {
            $leftJoinSub->on('churches.id', 'status_history_churches.churches_id');
        })->select('churches.*', DB::raw('IFNULL(status_history_churches.status, "-") as status'));
        return $this->traitSearch();
    }

    public function edit($id)
    {
        $this->crud->hasAccessOrFail('update');
        // get entry ID from Request (makes sure its the last ID for nested resources)
        $id = $this->crud->getCurrentEntryId() ?? $id;
        $this->crud->setOperationSetting('fields', $this->crud->getUpdateFields());
        // get the info for that entry
        $this->data['entry'] = $this->crud->getEntry($id);
        // dd($this->data['entry']->rdpw);

        $this->data['crud'] = $this->crud;
        $this->data['saveAction'] = $this->crud->getSaveAction();
        $this->data['title'] = $this->crud->getTitle() ?? trans('backpack::crud.edit').' '.$this->crud->entity_name;

        $this->data['id'] = $id;

        // load the view from /resources/views/vendor/backpack/crud/ if it exists, otherwise load the one in the package
        return view($this->crud->getEditView(), $this->data);
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(ChurchRequest::class);

        $this->crud->addField([
            'name'  => 'founded_on',
            'type'  => 'date_picker',
            'label' => 'Founded On',
            'tab'   => 'Church / Office Information',

            // optional:
            'date_picker_options' => [
                'todayBtn' => 'linked',
                'format'   => 'dd-mm-yyyy',
                'language' => 'en'
            ],
        ]);

        $this->crud->addField([
            'label'     => 'Church Type', // Table column heading
            'type'      => 'select2_church_type',
            'name'      => 'church_type_id', // the column that contains the ID of that connected entity;
            'entity'    => 'church_type', // the method that defines the relationship in your Model
            'attribute' => 'entities_type', // foreign key attribute that is shown to user
            'model'     => "App\Models\ChurchEntityType",
            'tab'       => 'Church / Office Information',
        ]);

        $this->crud->addField([
            'label' => 'Local Church', // Table column heading
            'type' => 'select2_from_array',
            'name' => 'church_local_id', // the column that contains the ID of that connected entity;
            'attributes' => ['id' => 'select-church-local'],
            'options' => Church::select('id', 'church_name')->whereExists(function($query){
                $query->from('church_types')->whereRaw('church_types.id = churches.church_type_id')
                ->where('church_types.entities_type', 'Local Church');
            })->get()->pluck('church_name', 'id')->toArray(),
            'tab' => 'Church / Office Information',
         ]);

        $this->crud->addField([
            'label'     => 'RC/DPW', // Table column heading
            'type'      => 'select2',
            'name'      => 'rc_dpw_id', // the column that contains the ID of that connected entity;
            'entity'    => 'rc_dpw', // the method that defines the relationship in your Model
            'attribute' => 'rc_dpw_name', // foreign key attribute that is shown to user
            'model'     => "App\Models\RcDpwList",
            'tab'       => 'Church / Office Information',
        ]);

        // $this->crud->addField([
        //     'label'     => 'RC/DPW', // Table column heading
        //     'type'      => 'select2',
        //     'name'      => 'churches_rcdpw', // the column that contains the ID of that connected entity;

        //     // 'entity'    => 'churches_rcdpw', // the method that defines the relationship in your Model
        //     'attribute' => 'rc_dpw_name', // foreign key attribute that is shown to user
        //     // 'multiple'  => true,
        //     'model'     => 'App\Models\RcDpwList',
        //     'tab'       => 'Church / Office Information',
        //     'option_value' => function($value){
        //         return $value->pluck('rc_dpwlists_id')->toArray();
        //     },
        // ]);

        $this->crud->addField([
            'name'            => 'church_name',
            'label'           => "Church Name",
            'type'            => 'text',
            'tab'             => 'Church / Office Information',
        ]);

        $this->crud->addField([
            'name'            => 'contact_person',
            'label'           => "Contact Person",
            'type'            => 'text',
            'tab'             => 'Church / Office Information',
        ]);

        $this->crud->addField([
            'name'            => 'building_name',
            'label'           => "Building Name",
            'type'            => 'text',
            'tab'             => 'Church / Office Information',
        ]);

        $this->crud->addField([
            'name'            => 'church_address',
            'label'           => "Church Address",
            'type'            => 'text',
            'tab'             => 'Church / Office Information',
        ]);

        $this->crud->addField([
            'name'            => 'office_address',
            'label'           => "Office Address",
            'type'            => 'text',
            'tab'             => 'Church / Office Information',
        ]);

        $this->crud->addField([
            'name'            => 'city',
            'label'           => "City",
            'type'            => 'text',
            'tab'             => 'Church / Office Information',
        ]);

        $this->crud->addField([
            'name'            => 'province',
            'label'           => "Province / State",
            'type'            => 'text',
            'tab'             => 'Church / Office Information',
        ]);

        $this->crud->addField([
            'name'            => 'postal_code',
            'label'           => "Postal Code",
            'type'            => 'text',
            'tab'             => 'Church / Office Information',
        ]);

        $this->crud->addField([
            'label'     => 'Country', // Table column heading
            'type'      => 'select2',
            'name'      => 'country_id', // the column that contains the ID of that connected entity;
            'entity'    => 'country', // the method that defines the relationship in your Model
            'attribute' => 'country_name', // foreign key attribute that is shown to user
            'model'     => "App\Models\CountryList",
            'tab'       => 'Church / Office Information',
        ]);

        $this->crud->addField([
            'name'            => 'first_email',
            'label'           => "Email",
            'type'            => 'text',
            'tab'             => 'Church / Office Information',
        ]);

        $this->crud->addField([
            'name'            => 'second_email',
            'label'           => "Email (Secondary)",
            'type'            => 'text',
            'tab'             => 'Church / Office Information',
        ]);

        $this->crud->addField([
            'name'            => 'phone',
            'label'           => "Phone",
            'type'            => 'number',
            'tab'             => 'Church / Office Information',
        ]);

        $this->crud->addField([
            'name'            => 'fax',
            'label'           => "Fax",
            'type'            => 'number',
            'tab'             => 'Church / Office Information',
        ]);

        $this->crud->addField([
            'name'            => 'website',
            'label'           => "Website",
            'type'            => 'text',
            'tab'             => 'Church / Office Information',
        ]);

        $this->crud->addField([
            'name'            => 'map_url',
            'label'           => "Map Url",
            'type'            => 'text',
            'tab'             => 'Church / Office Information',
        ]);

        $this->crud->addField([
            'name'            => 'service_time_church',
            'label'           => "Service Time",
            'type'            => 'textarea',
            'tab'             => 'Church / Office Information',
        ]);

        $this->crud->addField([
            'name'            => 'notes',
            'label'           => "Notes",
            'type'            => 'textarea',
            'tab'             => 'Church / Office Information',
        ]);

        $this->crud->addField([
            'name'    => 'task_color',
            'label'   => 'Task Color',
            'type'    => 'color',
            'tab'     => 'Church / Office Information',
        ]);

        $this->crud->addField([
            'name' => 'latitude',
            'label' => 'Latitude',
            'type' => 'text',
            'tab'     => 'Church / Office Information',
        ]);

        $this->crud->addField([
            'name' => 'longitude',
            'label' => 'Longitude',
            'type' => 'text',
            'tab'     => 'Church / Office Information',
        ]);

        $is_certificate_available = 0;
        if ($this->crud->getCurrentOperation() == 'update') {
            $is_certificate_available = (isset($this->crud->getCurrentEntry()->getAttributes()['certificate']))?1:0;
        }

        $this->crud->addField([
            'name' => 'check_certificate',
            'label' => "Certificate",
            'type'  => 'checkbox_certificate',
             'default' => $is_certificate_available,
            'tab' => 'Church / Office Information',
        ]);

        $this->crud->addField([
            'name'  => 'date_of_certificate',
            'type'  => 'date_picker',
            'label' => 'Date of Certificate',
            'tab'   => 'Church / Office Information',
            'wrapper'   => [ 
                'class'      => 'form-group col-sm-12 rect-image-certificate'
             ],
            // optional:
            'date_picker_options' => [
                'todayBtn' => 'linked',
                'format'   => 'dd-mm-yyyy',
                'language' => 'en'
            ],
        ]);

        $this->crud->addField([
            'label' => "Upload Certificate Photo (Max 3mb)",
            'name' => "certificate",
            'type' => 'image_certificate',
            'crop' => false, // set to true to allow cropping, false to disable
            'aspect_ratio' => 1, // omit or set to 0 to allow any aspect ratio
            'tab' => 'Church / Office Information',
            'wrapper'   => [ 
                'class'      => 'form-group col-sm-12 rect-image-certificate'
             ],
        ]);
    }

    public function store(Request $request)
    {
        $this->crud->setRequest($this->crud->validateRequest());

        /** @var \Illuminate\Http\Request $request */
        $request = $this->crud->getRequest();

        $this->crud->setRequest($request);
        $this->crud->unsetValidation(); // Validation has already been run

        DB::beginTransaction();
        try {
            $isDuplicate = Church::query();

            if (!$request->filled('church_name')) {
                $isDuplicate->whereNull('church_name');
            } else {
                $isDuplicate->where('church_name', $request->church_name);
            }

            if (!$request->filled('phone')) {
                $isDuplicate->whereNull('phone');
            } else {
                $isDuplicate->where('phone', $request->phone);
            }

            if (!$request->filled('postal_code')) {
                $isDuplicate->whereNull('postal_code');
            } else {
                $isDuplicate->where('postal_code', $request->postal_code);
            }

            $isDuplicate = $isDuplicate->select('id')->first();
            $errors = [];

            if ($isDuplicate != null) {
                // DB::rollback();
                $errors = [
                    'church_name' => ['The Church with same Church Name, Phone and Postal Code has already exists.'],
                    'phone' => ['The Church with same Church Name, Phone and Postal Code has already exists.'],
                    'postal_code' => ['The Church with same Church Name, Phone and Postal Code has already exists.'],
                ];
                // return redirect($this->crud->route . '/create')
                 //   ->withInput()->withErrors($errors);
            }

            $id_rcdpw = $request->rc_dpw_id;

            $item = $this->crud->create($this->crud->getStrippedSaveRequest());
            $this->data['entry'] = $this->crud->entry = $item;

            $id = $item->id;

            if(count($errors) != 0){
                DB::rollback();
                return redirect($this->crud->route . '/'. $id . '/edit')
                    ->withInput()->withErrors($errors);
            }

            /*
            if(ChurchesRcdpw::where('churches_id', $id)->exists()){
                // hapus semua data churches rcd
                ChurchesRcdpw::where('churches_id', $id)->delete();
            }

            $rc = new ChurchesRcdpw;
            $rc->churches_id = $id;
            $rc->rc_dpwlists_id = $id_rcdpw;
            $rc->save();
            */

            DB::commit();
            // hit api for update church
            $send = new HitApi;
            $id = [$item->getKey()];
            $module = 'sub_region';
            $response = $send->action($id, 'create', $module)->json();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }

        // show a success message
        \Alert::success(trans('backpack::crud.insert_success'))->flash();

        // save the redirect choice for next time
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($item->getKey());
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

    function update($id)
    {
        $this->crud->setRequest($this->crud->validateRequest());

        /** @var \Illuminate\Http\Request $request */
        $request = $this->crud->getRequest();

        $this->crud->setRequest($request);
        $this->crud->unsetValidation(); // Validation has already been run


        DB::beginTransaction();
        try {

            $model = Church::where('id', $id)->firstOrFail();

            $hitCompare = new HitCompare;
            $hitCompare->addFieldCompare(
                [
                    'church_name' => 'church_name',
                    'rc_dpw_id' => 'rc_dpw_id',
                    'church_local_id' => 'church_local_id',
                    'task_color' => 'task_color',
                    'church_address' => 'church_address',
                    'latitude' => 'latitude',
                    'longitude' => 'longitude',
                    'notes' => 'notes',
                ], 
            $request->all());
            $com = $hitCompare->compareData($model->toArray());

            $isDuplicate = Church::query();

            if (!$request->filled('church_name')) {
                $isDuplicate->whereNull('church_name');
            } else {
                $isDuplicate->where('church_name', $request->church_name);
            }

            if (!$request->filled('phone')) {
                $isDuplicate->whereNull('phone');
            } else {
                $isDuplicate->where('phone', $request->phone);
            }

            if (!$request->filled('postal_code')) {
                $isDuplicate->whereNull('postal_code');
            } else {
                $isDuplicate->where('postal_code', $request->postal_code);
            }

            $isDuplicate = $isDuplicate->select('id')->first();

            $errors = [];

            if ($isDuplicate != null && $isDuplicate->id != $id) {
                // DB::rollback();
                $errors = [
                    'church_name' => ['The Church with same Church Name, Phone and Postal Code has already exists.'],
                    'phone' => ['The Church with same Church Name, Phone and Postal Code has already exists.'],
                    'postal_code' => ['The Church with same Church Name, Phone and Postal Code has already exists.'],
                ];
                // return redirect($this->crud->route . '/'. $id . '/edit')
                //     ->withInput()->withErrors($errors);
            }

            $id_rcdpw = $request->rc_dpw_id;
            

            if(count($errors) != 0){
                DB::rollback();
                return redirect($this->crud->route . '/'. $id . '/edit')
                    ->withInput()->withErrors($errors);
            }

            $item = $this->crud->update($request->get($this->crud->model->getKeyName()),
            $this->crud->getStrippedSaveRequest());
            $this->data['entry'] = $this->crud->entry = $item;

            $id = $item->id;

            /*

            if(ChurchesRcdpw::where('churches_id', $id)->exists()){
                // hapus semua data churches rcd
                ChurchesRcdpw::where('churches_id', $id)->delete();
            }

            $rc = new ChurchesRcdpw;
            $rc->churches_id = $id;
            $rc->rc_dpwlists_id = $id_rcdpw;
            $rc->save();
            */
    
            DB::commit();
            // hit api for update church
            if($com){
                $send = new HitApi;
                $id = [$item->getKey()];
                $module = 'sub_region';
                $response = $send->action($id, 'update', $module)->json();
            }
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }

        // show a success message
        \Alert::success(trans('backpack::crud.update_success'))->flash();

        // save the redirect choice for next time
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($item->getKey());
    }

    public function show()
    {
        $this->crud->getCurrentEntry();
        $leaderships = StructureChurch::join('personels', 'personels.id', 'structure_churches.personel_id')
                        ->join('ministry_roles', 'ministry_roles.id', 'structure_churches.title_structure_id')
                        ->join('title_lists', 'title_lists.id', 'personels.title_id')
                        ->where('structure_churches.churches_id', $this->crud->getCurrentEntry()->id)
                        ->get(['structure_churches.id as id', 'ministry_roles.ministry_role as ministry_role', 
                        'title_lists.short_desc', 'title_lists.long_desc','personels.first_name', 'personels.last_name']);

        $data['crud'] = $this->crud;
        $data['leaderships'] = $leaderships;
        return view('vendor.backpack.crud.showchurch',$data);
    }

    public function destroy($id)
    {
        $this->crud->hasAccessOrFail('delete');

        $id = $this->crud->getCurrentEntryId() ?? $id;

        DB::beginTransaction();
        try{
            if(StatusHistoryChurch::where('churches_id', $id)->exists()){
                StatusHistoryChurch::where('churches_id', $id)->delete();
            }
            if(RelatedEntityChurch::where('churches_id', $id)->exists()){
                RelatedEntityChurch::where('churches_id', $id)->delete();
            }
            if(StructureChurch::where('churches_id', $id)->exists()){
                StructureChurch::where('churches_id', $id)->delete();
            }
            if(CoordinatorChurch::where('churches_id', $id)->exists()){
                CoordinatorChurch::where('churches_id', $id)->delete();
            }
            // if(ChurchesRcdpw::where('churches_id', $id)->exists()){
            //     ChurchesRcdpw::where('churches_id', $id)->delete();
            // }
            $response = $this->crud->delete($id);
            DB::commit();
            // hit api for update church
            $send = new HitApi;
            $id = [$id];
            $module = 'sub_region';
            $response_json = $send->action($id, 'delete', $module)->json();
            return $response;
        }
        catch(Exception $e){
            DB::rollback();
            throw $e;
        }
    }
}
