<?php

namespace App\Http\Controllers\User;

use Auth;
use Validator;
use Session;
use Response;
use URL;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Notifications\UserCreated;

use App\User;

use Illuminate\Support\Facades\Hash;

class UsermgtController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function __construct(){
        $this->middleware('cache');
    }


    public function index()
    {
        $users = User::withTrashed()->get();
        $name = Auth::user()->username;
        return view('users.index', compact('users'))->with("name", $name);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $name = Auth::user()->username;
        return view('users.create')->with("name", $name);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'username' => 'bail|required',
            'first_name' => 'bail|required',
            'last_name' => 'bail|required',
            'email' => 'bail|email|required|unique:users',
            'password' => 'bail|required',
            'confirmpassword' => 'bail|required'
            ],
            [
                'required' => ':attribute is required',
                'unique' => ':attribute has already been taken'
            ]
        );
        if ($validator->fails()) 
        {
            $messages = $validator->messages()->toArray();
            
            Session::flash('messages', $this->formatMessages($messages, 'error'));
            return redirect()->to(URL::previous())->withInput();
        }
        else
        {
            if($input['password'] !== $input['confirmpassword']){
                Session::flash('messages', $this->formatMessages("Please confirm password", 'error'));
                return redirect()->to(URL::previous())->withInput();
            }

            $password = $request->password; // password is form field
            $hashedpassword = Hash::make($password);
            $user = new User;
            $user->username = $request->username;
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->email = $request->email;
            $user->password = $hashedpassword;
            $user->is_admin = 0;
            $user->role_id = 0;
            $user->created_by = Auth::user()->id;
            if ($user->save()) {
                Session::flash('success', 'User created successfully.');
                $user->notify(new UserCreated($user,$password));
                return redirect()->to('/admin/users');
            } else {
                Session::flash('error', 'Error creating User');
                return redirect()->to('/admin/users');
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = User::find($id);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user = User::find($id);
        $name = Auth::user()->username;
        return view('users.edit', compact('user','id'))->with("name", $name);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'username' => 'bail|required',
            'email' => 'bail|email|required',
            'first_name' => 'bail|required',
            'last_name' => 'bail|required'
            ],
            [
                'required' => ':attribute is required',
                'numeric' => 'account number must be in numbers'
            ]
        );
        if ($validator->fails()) 
        {
            $messages = $validator->messages()->toArray();
            
            Session::flash('messages', $this->formatMessages($messages, 'error'));
            return redirect()->to(URL::previous())->withInput();
        }
        else
        {
            $user = User::find($id);
            $user->username = $input['username'];
            $user->email = $input['email'];
            $user->first_name = $input['first_name'];
            $user->last_name = $input['last_name'];
            $user->save();
            $name = Auth::user()->username;
            
            Session::flash('success', 'User updated successfully.');
            return redirect('/admin/users')->with("name", $name);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $user->forceDelete();
        Session::flash('success', 'User deleted successfully.');     
        return back();
    }


    public function banUser(Request $request, $id){

        $dt = Carbon::now();
        $dateNow = $dt->toDateTimeString();

        $user = User::where('id', $id)->update(["deleted_at" => $dateNow]);

         Session::flash('success', 'User banned successfully.');
        return back();

    }

    public function unbanUser(Request $request, $id){

        $user = User::withTrashed()->where('id', $id);
        $user->restore();

    
    Session::flash('success', 'User unbanned successfully.');
        
        return back();

    }

    public function makeAdmin(Request $request, $id){

        $user = User::where('id', $id)->update(["is_admin" => 1]);

        Session::flash('success', 'User updated successfully.');
        
        return back();

    }

    public function removeAdmin(Request $request, $id){

        $user = User::where('id', $id)->update(["is_admin" => 0]);

        
        Session::flash('success', 'User updated successfully.');
        return back();

    }


}
