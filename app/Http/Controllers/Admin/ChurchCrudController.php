<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\ChurchRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Models\StatusHistoryChurch;
use App\Models\Church;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'label' => "Province / State", // Table column heading
            'type' => 'text'
        ]);

        $this->crud->addColumn([
            'name' => 'postal_code', // The db column name
            'label' => "Postal Code", // Table column heading
            'type' => 'text'
        ]);

        $this->crud->addColumn([
            'name' => 'country', // The db column name
            'label' => "Country", // Table column heading
            'type' => 'relationship',
            'attribute' => 'country_name',
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
                                ->where('status_history_churches.id', '<', 'temps.id');
                        });
                });
        })->whereNull('temps.id')
            ->select('status_history_churches.churches_id', 'status_history_churches.status');
        $this->crud->query->leftJoinSub($subQuery, 'status_history_churches', function ($leftJoinSub) {
            $leftJoinSub->on('churches.id', 'status_history_churches.churches_id');
        })->select('churches.*', DB::raw('IFNULL(status_history_churches.status, "-") as status'));
        return $this->traitSearch();
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
            'label'           => "Email 1",
            'type'            => 'text',
            'tab'             => 'Church / Office Information',
        ]);

        $this->crud->addField([
            'name'            => 'second_email',
            'label'           => "Email 2",
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
            'name'            => 'certificate',
            'label'           => "Certificate / SK",
            'type'            => 'select2_from_array',
            'options'         => ['Yes' => "Yes", 'No' => "No"],
            'tab'             => 'Church / Office Information',
        ]);

        $this->crud->addField([
            'name'  => 'date_of_certificate',
            'type'  => 'date_picker',
            'label' => 'Date of Certificate',
            'tab'   => 'Church / Office Information',

            // optional:
            'date_picker_options' => [
                'todayBtn' => 'linked',
                'format'   => 'dd-mm-yyyy',
                'language' => 'en'
            ],
        ]);

        $this->crud->addField([
            'name'            => 'notes',
            'label'           => "Notes",
            'type'            => 'textarea',
            'tab'             => 'Church / Office Information',
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

            if ($isDuplicate != null) {
                DB::rollback();
                $errors = [
                    'church_name' => ['The Church with same Church Name, Phone and Postal Code has already exists.'],
                    'phone' => ['The Church with same Church Name, Phone and Postal Code has already exists.'],
                    'postal_code' => ['The Church with same Church Name, Phone and Postal Code has already exists.'],
                ];
                return redirect($this->crud->route . '/create')
                    ->withInput()->withErrors($errors);
            }
            $item = $this->crud->create($this->crud->getStrippedSaveRequest());
            $this->data['entry'] = $this->crud->entry = $item;

            DB::commit();
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

        $model = Church::where('id', $id)->firstOrFail();

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

            if ($isDuplicate != null && $isDuplicate->id != $id) {
                DB::rollback();
                $errors = [
                    'church_name' => ['The Church with same Church Name, Phone and Postal Code has already exists.'],
                    'phone' => ['The Church with same Church Name, Phone and Postal Code has already exists.'],
                    'postal_code' => ['The Church with same Church Name, Phone and Postal Code has already exists.'],
                ];
                return redirect($this->crud->route . '/'. $id . '/edit')
                    ->withInput()->withErrors($errors);
            }

            $item = $this->crud->update($request->get($this->crud->model->getKeyName()),
            $this->crud->getStrippedSaveRequest());
            $this->data['entry'] = $this->crud->entry = $item;

            DB::commit();
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
        $data['crud'] = $this->crud;
        return view('vendor.backpack.crud.showchurch',$data);
    }
}
