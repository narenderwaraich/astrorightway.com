<?php

namespace App\Http\Controllers;

use App\Chat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;
use Redirect;
use Toastr;
use Auth;
use Carbon\Carbon;
use App\User;
use App\ChatPlan;
use App\BanerSlide;
use App\Order;
use App\Contact;
use App\UserPlan;
use App\Astrologer;
use App\MemberJoin;
use Mail;
use App\Mail\MessageNotification;
use App\Mail\ChatReply;

class ChatController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
            $banner = BanerSlide::where('page_name','=','chat')->first(); //dd($banner);
            if (isset($banner)) {
                $title = $banner->title;
                $description = $banner->description;
            }
            $astrologers = Astrologer::where('verified','=',2)->get(); //dd($astrologers);
            $messages = Chat::where('user_id',Auth::id())->get(); //dd($messages);
            $member = MemberJoin::where('user_id',Auth::id())->where('status','=',1)->first();
            $deactiveMember = MemberJoin::where('user_id',Auth::id())->where('status','=',0)->first();
            if($deactiveMember){
                Toastr::error('Your Member Fee Pending Please Pay Now', 'Error', ["positionClass" => "toast-top-right"]);
                return redirect()->to('/join-member/pay/payment');
            }
            return view('chat',compact('title','description','banner','messages','astrologers','member'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (Auth::check()) {
            $userID = Auth::id();
            $chatMsg = Chat::where('user_id',$userID)->where('message_status','=','Pending')->first();
            if($chatMsg){
                Toastr::error('Please Buy Any Plan', 'Error', ["positionClass" => "toast-bottom-right"]);
                return redirect()->to('/buy/plan');
            }else{

            $validate = $this->validate($request, [
                'user_message' => 'required',
                'astrologer' => 'required',
            ]);
            if(!$validate){
                    Redirect::back()->withInput();
            }

            $data['message_assign'] = $request->astrologer; 
            $data['user_message'] = $request->user_message;
            $data['user_id'] = $userID;
            $checkUserPlan = UserPlan::where('user_id',$userID)->first();
            if($checkUserPlan){

            $current = Carbon::now();
            $nowDate = $current->toDateTimeString(); //dd($nowDate);
            $nowD = strtotime(str_replace('/', '-', $nowDate)); //dd($nowD);
            $planExp = $checkUserPlan->expire_date;  //dd($planExp); 
            $planE = strtotime(str_replace('/', '-', $planExp)); //dd($planE);     
            $planExpires = $planE - $nowD; /// show total seconds
            //dd($planExpires);

            ///user active or not
            if($checkUserPlan->is_activated=1){ ///check plan active or deactive
                if($planExpires <= 0){ /// check plan exp date 
                    $data['message_status'] = "Pending";
                }else{
                    if($checkUserPlan->get_message == 0){ /// check message 
                        $data['message_status'] = "Pending";
                    }else{
                        $data['message_status'] = "Sent";
                        $message['get_message'] = $checkUserPlan->get_message - 1;
                        if($checkUserPlan->get_message == 0 || $checkUserPlan->get_message == 1){
                            $message['is_activated'] = 0;
                        } 
                        $checkUserPlan->update($message);
                    } 
                }
            }else{
                $data['message_status'] = "Pending";
            }

            }else{
               $data['message_status'] = "Pending"; 
            }
            $chatData = Chat::create($data);
            $user = User::where('id',$userID)->first();
            $astrologer = Astrologer::where('id',$request->astrologer)->first();
            $astrologerMail = $astrologer->email;
            Mail::to($astrologerMail)->send(new MessageNotification($user,$chatData));
            Toastr::success('Message Sent your reply with in 24hrs', 'Success', ["positionClass" => "toast-bottom-right"]);
            return redirect()->to('/talk-astro');
           }
        }else{
            Toastr::error('Please login first', 'Error', ["positionClass" => "toast-bottom-right"]);
            return redirect()->to('/login');
        }
    }

    public function astroReply(Request $request, $id)
    {
        if(Auth::check()){
            $validate = $this->validate($request, [
                'reply_message' => 'required',
            ]);
            if(!$validate){
                    Redirect::back()->withInput();
                    }
            $plan = Chat::find($id);
            $data['reply_message'] = $request->reply_message;
            $data['user_id'] = $plan->user_id;
            $data['message_status'] = "Reply";
            // $userPlan = UserPlan::where('user_id',$plan->user_id)->first(); //dd($userPlan);
            // $message['get_message'] = $userPlan->get_message - 1;
            // if($userPlan->get_message == 1){
            //     $message['is_activated'] = 0;
            // } 
            // $userPlan->update($message);
            $plan->update($data);
            $reply = $request->reply_message;
            $user = User::where('id',$plan->user_id)->first();
            $email = $user->email;
            Mail::to($email)->send(new ChatReply($reply));
            Toastr::success('Message Reply', 'Success', ["positionClass" => "toast-bottom-right"]);
            if(Auth::user()->role == "astrologer"){
                return redirect()->to('/astrologer/chat');
            }else{
                return redirect()->to('/admin/chat');
            }
    }else{
        return redirect()->to('/login');
    }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Chat  $chat
     * @return \Illuminate\Http\Response
     */
    public function show(Chat $chat)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Chat  $chat
     * @return \Illuminate\Http\Response
     */
    public function edit(Chat $chat)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Chat  $chat
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Chat $chat)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Chat  $chat
     * @return \Illuminate\Http\Response
     */
    public function destroy(Chat $chat)
    {
        //
    }

    public function viewChat($id){
        if(Auth::check()){
    if(Auth::user()->role == "administrator" || Auth::user()->role == "admin"){
        $chat = Chat::find($id);
        return view('Admin.chatView',compact('chat'));
        }
    }else{
        return redirect()->to('/login');
    }
    }

    public function showList(){
            if(Auth::check()){
                if(Auth::user()->role == "administrator" || Auth::user()->role == "admin"){
                    $getOrders = Order::where('status','=',"Pending")->get(); //dd($getOrders);
                    $contacts = Contact::where('status','=',"Pending")->get(); //dd($contacts);
                    $chats = Chat::where('message_status','=',"Sent")->get(); //dd($chats);
                    $getChats = Chat::where('message_status','=','Sent')->orderBy('created_at','desc')->paginate(10);
                    foreach ($getChats as $chat) {
                        $astrologer = Astrologer::where('id',$chat->message_assign)->first();
                        $chat->name = User::where('id',$chat->user_id)->first()->name;
                        $chat->email = User::where('id',$chat->user_id)->first()->email;
                        $chat->astrologer = $astrologer ? $astrologer->name : 'Guru';
                    }
                return view('Admin.chat',compact('getOrders','contacts','chats'),['getChats' =>$getChats]);
                }
            }else{
                return redirect()->to('/login');
            }
        }

        public function listWithStatus($status){
                if(Auth::check()){
            if(Auth::user()->role == "administrator" || Auth::user()->role == "admin"){
                $getOrders = Order::where('status','=',"Pending")->get(); //dd($getOrders);
                $contacts = Contact::where('status','=',"Pending")->get(); //dd($contacts);
                $chats = Chat::where('message_status','=',"Sent")->get(); //dd($chats);
                $getChats = Chat::where('message_status',$status)->orderBy('created_at','desc')->paginate(10);
                foreach ($getChats as $chat) {
                    $astrologer = Astrologer::where('id',$chat->message_assign)->first();
                    $chat->name = User::where('id',$chat->user_id)->first()->name;
                    $chat->email = User::where('id',$chat->user_id)->first()->email;
                    $chat->astrologer = $astrologer ? $astrologer->name : 'Guru';
                }
                return view('Admin.chat',compact('getOrders','contacts','chats'),['getChats' =>$getChats]);
                }
            }else{
                return redirect()->to('/login');
            }
            }

    public function userMessage($id){
        if(Auth::check()){
    if(Auth::user()->role == "administrator" || Auth::user()->role == "admin"){
        $getOrders = Order::where('status','=',"Pending")->get(); //dd($getOrders);
        $contacts = Contact::where('status','=',"Pending")->get(); //dd($contacts);
        $chat = Chat::find($id);
        return view('Admin.chatReply',compact('getOrders','contacts'),['chat' =>$chat]);
        }
    }else{
        return redirect()->to('/login');
    }
    }

    public function astrologerChatList(){
        if(Auth::user()->role == "astrologer"){
            $userId = Auth::id();
            $astrologerId = Astrologer::where('auth_id',$userId)->first()->id; //dd($astrologerId);
            $getChats = Chat::where('message_assign','=',$astrologerId)->orderBy('created_at','desc')->paginate(10);
            foreach ($getChats as $chat) {
                $chat->name = User::where('id',$chat->user_id)->first()->name;
                $chat->email = User::where('id',$chat->user_id)->first()->email;
            }
            return view('Astrologer.chat',['getChats' =>$getChats]);
        }
    }

    public function viewClientChat($id){
        if(Auth::check()){
    if(Auth::user()->role == "astrologer"){
        $chat = Chat::find($id);
        return view('Astrologer.chatView',compact('chat'));
        }
    }else{
        return redirect()->to('/login');
    }
    }

    public function astroUserMessage($id){
        if(Auth::user()->role == "astrologer"){
                $userId = Auth::id();
                $astrologerId = Astrologer::where('auth_id',$userId)->first()->id; //dd($astrologerId);
                $chats = Chat::where('message_status','=',"Sent")->where('message_assign','=',$astrologerId)->get(); //dd($chats);
                foreach ($chats as $chat) {
                  $chat->user_name = User::where('id',$chat->user_id)->first()->name;
                } 
                $chat = Chat::find($id);
        return view('Astrologer.chatReply',['chat' =>$chat, 'chats' =>$chats]);
        }
    }
}