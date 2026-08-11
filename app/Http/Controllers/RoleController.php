<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
//Importing laravel-permission models
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

use Session;

class RoleController extends Controller {

    public function __construct() {
        $this->middleware(['auth', 'isAdmin']);//isAdmin middleware lets only users with a //specific permission permission to access these resources
    }


    private function groupedPermissions($permissions)
    {
        $labels = [
            'posts' => 'Dossiers',
            'transfers' => 'Transferts',
            'missions' => 'Missions',
            'acentes' => 'Prestataires',
            'vehicules' => 'Véhicules',
            'invoices' => 'Factures',
            'payments' => 'Paiements',
            'accounts' => 'Comptes bancaires',
            'balances' => 'Balances',
            'users' => 'Utilisateurs',
            'roles' => 'Rôles',
            'permissions' => 'Permissions',
            'options' => 'Options',
            'hermes' => 'Hermes',
            'talep' => 'Demandes',
            'tasks' => 'Tâches',
            'charts' => 'Rapports',
            'fuel' => 'Carburant',
            'clients' => 'Clients',
            'attendance' => 'Permanence',
            'documents' => 'Documents',
            'imports' => 'Imports',
            'rates' => 'Taux / devises',
            'statuses' => 'Statuts',
            'service-types' => 'Types de service',
            'legacy' => 'Anciennes permissions',
        ];

        $actionLabels = [
            'view' => 'Voir',
            'create' => 'Créer',
            'update' => 'Modifier',
            'delete' => 'Supprimer',
            'manage' => 'Gérer',
            'other' => 'Autres',
        ];

        $groups = [];
        foreach ($permissions->sortBy('name') as $permission) {
            $name = $permission->name;
            $parts = explode('.', $name, 2);
            $module = count($parts) === 2 ? $parts[0] : 'legacy';
            $action = count($parts) === 2 ? $parts[1] : $name;
            $bucket = in_array($action, ['view', 'create', 'update', 'delete', 'manage'], true) ? $action : 'other';

            if (!isset($groups[$module])) {
                $groups[$module] = [
                    'key' => $module,
                    'label' => $labels[$module] ?? ucfirst(str_replace('-', ' ', $module)),
                    'permissions' => collect(),
                ];
            }

            $groups[$module]['permissions']->push((object) [
                'id' => $permission->id,
                'name' => $permission->name,
                'action' => $action,
                'bucket' => $bucket,
                'label' => $actionLabels[$bucket] ?? ucfirst($action),
            ]);
        }

        return collect($groups)->sortBy('label')->values();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $roles = Role::with(['permissions', 'users:id,name,email'])->orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();
        $permissionGroups = $this->groupedPermissions($permissions);
        $users = User::with('roles:id,name')
            ->whereDoesntHave('roles', function ($query) {
                $query->where('name', 'Driver');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('roles.index', compact('roles', 'permissions', 'permissionGroups', 'users'));
    }


    public function updatePermissions(Request $request, Role $role)
    {
        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $permissions = Permission::whereIn('id', $data['permissions'] ?? [])->pluck('name')->all();
        $role->syncPermissions($permissions);

        return redirect()->route('roles.index')->with('flash_message', 'Permissions du rôle '.$role->name.' mises à jour.');
    }

    public function updateUsers(Request $request, Role $role)
    {
        abort_if(in_array($role->name, ['Superadmin', 'Driver'], true), 403, 'Ce rôle est protégé.');

        $data = $request->validate([
            'users' => ['nullable', 'array'],
            'users.*' => ['integer', 'exists:users,id'],
        ]);

        $users = User::whereIn('id', $data['users'] ?? [])->get();

        foreach ($users as $user) {
            abort_if($user->hasRole('Driver'), 422, 'Un chauffeur ne peut pas être ajouté à un rôle bureau depuis cet écran.');
        }

        $role->users()->sync($users->pluck('id')->all());

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('roles.index')->with('flash_message', 'Utilisateurs du rôle '.$role->name.' mis à jour.');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        $permissions = Permission::orderBy('name')->get();//Get all permissions
        $permissionGroups = $this->groupedPermissions($permissions);

        return view('roles.create', ['permissions'=>$permissions, 'permissionGroups' => $permissionGroups]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
    //Validate name and permissions field
        $this->validate($request, [
            'name'=>'required|unique:roles|max:60',
            'permissions' =>'nullable|array',
            ]
        );

        $name = $request['name'];
        $role = new Role();
        $role->name = $name;

        $permissions = $request->input('permissions', []);

        $role->save();
    //Looping thru selected permissions
        foreach (($permissions ?? []) as $permission) {
            $p = Permission::where('id', '=', $permission)->firstOrFail(); 
         //Fetch the newly created role and assign permission
            $role = Role::where('name', '=', $name)->first(); 
            $role->givePermissionTo($p);
        }

        return redirect()->route('roles.index')
            ->with('flash_message',
             'Role'. $role->name.' added!'); 
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        return redirect('roles');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        $role = Role::findOrFail($id);
        $permissions = Permission::orderBy('name')->get();
        $permissionGroups = $this->groupedPermissions($permissions);

        return view('roles.edit', compact('role', 'permissions', 'permissionGroups'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {

        $role = Role::findOrFail($id);//Get role with the given id
    //Validate name and permission fields
        $this->validate($request, [
            'name'=>'required|max:60|unique:roles,name,'.$id,
            'permissions' =>'nullable|array',
        ]);

        $input = $request->except(['permissions']);
        $permissions = $request['permissions'];
        $role->fill($input)->save();

        $p_all = Permission::all();//Get all permissions

        foreach ($p_all as $p) {
            $role->revokePermissionTo($p); //Remove all permissions associated with role
        }

        foreach (($permissions ?? []) as $permission) {
            $p = Permission::where('id', '=', $permission)->firstOrFail(); //Get corresponding form //permission in db
            $role->givePermissionTo($p);  //Assign permission to role
        }

        return redirect()->route('roles.index')
            ->with('flash_message',
             'Role'. $role->name.' updated!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        $role->delete();

        return redirect()->route('roles.index')
            ->with('flash_message',
             'Role deleted!');

    }
}
