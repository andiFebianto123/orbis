<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Appointment_historyRequest;
use App\Models\Personel;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class Appointment_historyCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class Appointment_historyCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Appointment_history::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/appointment_history');
        CRUD::setEntityNameStrings('Appointment History', 'Appointment Histories');
        $this->crud->currentId = request()->personel_id;
        $this->crud->redirectTo = backpack_url('personel/'.$this->crud->currentId.'/show');
        $isPersonelExists =  Personel::where('id',$this->crud->currentId)->first();
        if($isPersonelExists == null){
            abort(404);
        }
        $this->crud->saveOnly=true;
    }

    public function index()
    {
        abort(404);
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
            'name' => 'id', // The db column name
            'label' => "ID", // Table column heading
            'type' => 'number'
        ]);

        $this->crud->addColumn([
            'name' => 'title_appointment', // The db column name
            'label' => "Subject", // Table column heading
            'type' => 'text'
        ]);

        $this->crud->addColumn([
            'name' => 'date_appointment', // The db column name
            'label' => "Date", // Table column heading
            'type' => 'date'
        ]);

        $this->crud->addColumn([
            'name' => 'notes', // The db column name
            'label' => "Notes", // Table column heading
            'type' => 'text'
        ]);

        $this->crud->addColumn([
            'name' => 'personel', // The db column name
            'label' => "Personel", // Table column heading
            'type' => 'relationship',
            'attribute' => 'first_name',
        ]);
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(Appointment_historyRequest::class);

        $this->crud->addField([
            'name'            => 'title_appointment',
            'label'           => "Subject",
            'type'            => 'text',
        ]);

        $this->crud->addField([
            'name'  => 'date_appointment',
            'type'  => 'date_picker',
            'label' => 'Date',

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
        ]);

        $this->crud->addField([
            'label'     => 'Personel', // Table column heading
            'type'      => 'hidden',
            'name'      => 'personel_id', // the column that contains the ID of that connected entity;
            'default'   => request('personel_id')
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

        return redirect(backpack_url('personel/'.$item->personel_id.'/show'));
    }

    public function update()
    {
        $this->crud->hasAccessOrFail('update');

        // execute the FormRequest authorization and validation, if one is required
        $request = $this->crud->validateRequest();
        // update the row in the db
        $item = $this->crud->update($request->get($this->crud->model->getKeyName()),
                            $this->crud->getStrippedSaveRequest());
        $this->data['entry'] = $this->crud->entry = $item;

        // show a success message
        \Alert::success(trans('backpack::crud.update_success'))->flash();

        return redirect(backpack_url('personel/'.$item->personel_id.'/show'));    
    }
}
