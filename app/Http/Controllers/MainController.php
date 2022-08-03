<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\File;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use JWTAuth;
use App\Models\User;
use Tymon\JWTAuth\Exceptions\JWTException;
use Image;
use DB;
use Input;
use App\Item;
use Session;
use Response;
use Validator;

class MainController extends Controller
{
    public function login(Request $request){
    $validate = Validator::make($request->all(), [ 
      'email'       => 'required',
      'password'    => 'required',
    ]);
    if ($validate->fails()) {    
        return response()->json("Enter Credentials To Signin", 400);
    }
    $credentials = $request->only('email', 'password');
    try {
        if (! $token = JWTAuth::attempt($credentials)) {
            $token = JWTAuth::attempt($credentials);
       return response()->json([
        	'success' => false,
        	'message' => 'Login credentials are invalid.',
        ], 400);
        }
    } catch (JWTException $e) {
    return $credentials;
        return response()->json([
                'success' => false,
                'message' => 'Could not create token.',
            ], 500);
    }
    $user = User::where('email','=',$request->email)->first();
    // DB::table('users')
    //  ->where('id', $user->id)
    //  ->update(['verify_token' => $token]);
    return response()->json(['data' => $user,'success' => true,'token' => $token,]);
    }
    public function signup(Request $request){
        //Validate data
        $data = $request->only('name', 'email', 'password');
        $validator = Validator::make($data, [
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 200);
        }

        //Request is valid, create new user
        $user = User::create([
        	'name' => $request->name,
        	'email' => $request->email,
        	'password' => bcrypt($request->password)
        ]);

        //User created, return success response
        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => $user
        ], 200);
        // $validate = Validator::make($request->all(), [ 
        //   'name'        => 'required',
        //   'email'       => 'required',
        //   'password'    => 'required',
        // ]);
        // if ($validate->fails()) {    
        //     return response()->json("Fields Required", 400);
        // }
        // $validateemail = Validator::make($request->all(), [ 
        //   'email'       => 'unique:users,email',
        // ]);
        // if ($validateemail->fails()) {    
        //     return response()->json("Email Already Exist", 400);
        // }
        // $save = DB::table('users')->insert([
        //     'name' => $request->name,
        //     'email' => $request->email,
        //     'password' => $request->password,
        //     'created_at' => date('Y-m-d h:i:s'),
        // ]);
        // if($save){
        //     return response()->json(['message' => 'Register Successfully'],200);
        // }else{
        //     return response()->json("Oops! Something Went Wrong", 400);
        // }
    }
    public function checklogin(Request $request){
        $token = explode(' ', $request->header('authorization'));
        //  dd($token);
        // $user = User::where('verify_token','=',$token[1])->first();
        // return response()->json(['data' => $user,'message' => 'Login User Data'],200);
        $user = JWTAuth::authenticate($token[1]);
        return response()->json(['user' => $user,'message' => 'Login User Data'],200);
    }
    public function logout(Request $request)
    {
        $token = explode(' ', $request->header('authorization'));
        try {
            JWTAuth::invalidate($token[1]);
            return response()->json([
                'success' => true,
                'message' => 'User has been logged out'
            ],200);
        } catch (JWTException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, user cannot be logged out'
            ], 400);
        }
    }
    public function updateprofile(Request $request){
        $validate = Validator::make($request->all(), [ 
          'id'          => 'required',  
          'name'        => 'required',
          'email'       => 'required',
          'password'    => 'required',
        ]);
        if ($validate->fails()) {    
            return response()->json($validate->errors(), 400);
        }
        $getemail = DB::table('users')
        ->where('id','=',$request->id)
        ->select('email')
        ->first();
        if ($getemail->email != $request->email) {
        $validateemail = Validator::make($request->all(), [ 
          'email'       => 'unique:users,email',
        ]);
        if ($validateemail->fails()) {    
            return response()->json("Email Already Exist", 400);
        }
        }
        $adds = [
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => bcrypt($request->password),
        ];
        $save = DB::table('users')
            ->where('id','=',$request->id)
            ->update($adds);
        if($save){
            return response()->json(['message' => 'Updated Successfully'],200);
        }else{
            return response()->json("Oops! Something Went Wrong", 400);
        }
    }
    public function dbtables(Request $request){
        $show = DB::select('SELECT table_name FROM information_schema.tables');
        return response()->json(['data' => $show,'message' => 'DB Tables'],200);
    }
    public function gettable(Request $request){
     $validate = Validator::make($request->all(), [ 
          'table_name'       => 'required',
        ]);
        if ($validate->fails()) {    
            return response()->json("Table Name Required", 400);
        }
        $show = DB::select('SELECT table_name FROM information_schema.tables');
        $tablearray = array();
        foreach ($show as $shows) {
            $tablearray[] = $shows->table_name;
        }
        $checktableisexist = array_search($request->table_name,$tablearray,true);
        $isnumber = is_numeric($checktableisexist);
        if ($isnumber == 'true') {
            $gettable = DB::table($request->table_name)
            ->select('*')
            ->get();
            // $gettable = $this->paginate($gettable);
            if($gettable){
                return response()->json(['data' => $gettable,'message' => 'Table Data'],200);
            }else{
                $emptydata = array();
                return response()->json(['data' => $emptydata,'message' => 'No Data'],200);
            }
        }else{
            return response()->json("Table Not Found", 400);   
        }
    }
    public function savetag(Request $request){
        $validate = Validator::make($request->all(), [ 
          'tags_name'   => 'required',
          'tablename'   => 'required',
          'columnname'  => 'required',
          'row_id'      => 'required',
        ]);
        if ($validate->fails()) {    
            return response()->json("Fields Required", 400);
        }
        $save = DB::table('tags')->insert([
            'tags_name'     => $request->tags_name,
            'tablename'     => $request->tablename,
            'columnname'    => $request->columnname,
            'row_id'        => $request->row_id,
            'users_id'      => $request->id,
            'created_at'    => date('Y-m-d h:i:s'),
        ]);
        if($save){
            return response()->json(['message' => 'Tag Saved Successfully'],200);
        }else{
            return response()->json("Oops! Something Went Wrong", 400);
        }
    }
    public function searchbytag(Request $request){
        $validate = Validator::make($request->all(), [ 
          'tags_name'   => 'required',
        ]);
        if ($validate->fails()) {    
            return response()->json("Fields Required", 400);
        }
        $gettag = DB::table('tags')
        ->select('*')
        ->where('users_id','=',$request->id)
        ->where('tags_name','like','%'.$request->tags_name.'%')
        ->first();
        if (!empty($gettag)) {
        $getdata = DB::table($gettag->tablename)
        ->select('*')
        ->where($gettag->columnname,'=',$gettag->row_id)
        ->first();
        }else{
            return response()->json(['message' => 'Tag Not Exist'],200);
        }
        if($getdata){
            return response()->json(['data' => $getdata,'message' => 'Tag Data'],200);
        }else{
            $emptyarray = array();
            return response()->json(['data' => $emptyarray,'message' => 'No Tag Data Found'],200);
        }
    }
    public function usertaglist(Request $request){
        $validate = Validator::make($request->all(), [ 
          'id'   => 'required',
        ]);
        if ($validate->fails()) {    
            return response()->json("User Id Required", 400);
        }
        $show = DB::table('tags')
        ->select('*')
        ->where('users_id','=',$request->id)
        ->get();
        return response()->json(['data' => $show,'message' => 'Tag List'],200);
    }
    public function deletetag(Request $request){
        $validate = Validator::make($request->all(), [ 
          'tags_id'   => 'required',
        ]);
        if ($validate->fails()) {    
            return response()->json("Tag Id Required", 400);
        }
        $task = DB::table('tags')->where('tags_id','=',$request->tags_id)->delete();
        if ($task) {
        	return response()->json(['message' => 'Successfully Deleted'],200);
        }else{
        	return response()->json(['message' => 'Oops! Something Went Wrong'],400);
        }
    }
    public function forgetpassword(Request $request){
    	if($request->email == ""){
            return response()->json(['message' => 'Please Enter Email'],200);
        }
      $verify_token =  $this->generateRandomString(100);
      $data = array();
      $data['verify_token'] = $verify_token;
      $cmd = DB::table('users')
             ->where('email', $request->email)
             ->update(['verify_token' => $verify_token]);
      if($cmd){
        $toemail = $request->email;
          Mail::send('emails.forgetpassword', ['verify_token' => $verify_token],
            function ($message) use ($toemail) {
              $message->to($toemail)
            ->subject('Forget Password');
            });
        return response()->json(['message' => 'Check Your Email'],200);
      } else{
        return response()->json(['message' => 'Oops! Something Went Wrong'],400);
      }
    }
    public function verifycode(Request $request){
        $validate = Validator::make($request->all(), [ 
          'verify_token'   => 'required',
        ]);
        if ($validate->fails()) {    
            return response()->json("Verification Code Required", 400);
        }
        $result =  DB::table('users')
                 ->where('verify_token', '=',$request->verify_token)
                 ->select('verify_token','id')->first();
        if(!empty($result)){
            $verify_token = $result->verify_token;
            $id = $result->id;
            return response()->json(['verify_token' => $verify_token,'id' => $id, 'message' => 'Successfully Verified'],200);
        } else{
             return response()->json(['message' => 'Oops! Something Went Wrong'],400);
        }
    }
    public function resetpassword(Request $request){
        $validate = Validator::make($request->all(), [ 
          'verify_token'    => 'required',
          'id'              => 'required',
          'password'        => 'required',
        ]);
        if ($validate->fails()) {    
            return response()->json("Fields Required", 400);
        }   
       $cmd = DB::table('users')
             ->where('id', $request->id)
             ->where('verify_token', $request->verify_token)
             ->update(['password' => $request->password,'verify_token' => '']);
        if($cmd){
            return response()->json(['message' => 'Successfully Reset'],200);
        } else{
            return response()->json(['message' => 'Oops! Something Went Wrong'],400);
        }	
    }
    public function getsavetagdata(Request $request){
        $validate = Validator::make($request->all(), [ 
          'tags_id'     => 'required',
        ]);
        if ($validate->fails()) {    
            return response()->json("Fields Required", 400);
        }
        $gettag = DB::table('tags')
        ->select('*')
        ->where('users_id','=',$request->id)
        ->where('tags_id','=',$request->tags_id)
        ->first();
        if (!empty($gettag)) {
        $getdata = DB::table($gettag->tablename)
        ->select('*')
        ->where($gettag->columnname,'=',$gettag->row_id)
        ->first();
        }else{
            return response()->json(['message' => 'Tag Not Exist'],200);
        }
        if($getdata){
            return response()->json(['data' => $getdata,'message' => 'Tag Data'],200);
        }else{
            $emptyarray = array();
            return response()->json(['data' => $emptyarray,'message' => 'No Tag Data Found'],200);
        }
    }
    public  function generateRandomString($length = 5){
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
	}
    public function paginate($items, $perPage = 30, $page = null, $options = []){
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return  new  LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }
}