<?php
namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Cmgmyr\Messenger\Models\Message;
use Cmgmyr\Messenger\Models\Participant;
use Cmgmyr\Messenger\Models\Thread;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
class Messenger_messagesController extends Controller
{
    /**
     * Show all of the message threads to the user.
     *
     * @return mixed
     */
    public function __construct() {
    //  $this->middleware(['auth', 'isAdmin'])->except('index', 'show');
        $this->middleware(['auth', 'clearance']);
    
    }

    public function index()
    {
        // All threads, ignore deleted/archived participants
       // $threads = Thread::getAllLatest()->get();
        // All threads that user is participating in
      $threads = Thread::forUser(Auth::id())->latest('updated_at')->get();
        // All threads that user is participating in, with new messages
      //   $threads = Thread::forUserWithNewMessages(Auth::id())->latest('updated_at')->get();
     // $threads = Thread::forUser(Auth::id())->get();
      $ids=false;
        return view('messenger.index', compact('threads','ids'));
    }
    /**
     * Shows a message thread.
     *
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        
      try {
            $threadid = Thread::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            Session::flash('error_message', 'The thread with ID: ' . $id . ' was not found.');
            return redirect()->route('messages');
        }
        // show current user in list if not a current participant
        // $users = User::whereNotIn('id', $thread->participantsUserIds())->get();
        // don't show the current user in list
        $userId = Auth::id();
        $users = User::whereNotIn('id', $threadid->participantsUserIds($userId))->get();
        $threadid->markAsRead($userId);
        $threads = Thread::forUser(Auth::id())->latest('updated_at')->get();
         $ids=$id;
        return view('messenger.index', compact('threadid','threads', 'users','ids'));
    }
    /**
     * Creates a new message thread.
     *
     * @return mixed
     */
    public function create()
    {
     $threads = Thread::forUser(Auth::id())->latest('updated_at')->get();
        $users = User::where('id', '!=', Auth::id())->get();
      $new_message=true;
       $ids=false;
        return view('messenger.index', compact('users','threads','new_message','ids'));
    }
    /**
     * Stores a new message thread.
     *
     * @return mixed
     */
    public function store()
    {
        $input = Input::all();
        $thread = Thread::create([
            'subject' => $input['subject'],
        ]);
        // Message
        Message::create([
            'thread_id' => $thread->id,
            'user_id' => Auth::id(),
            'body' => $input['message'],
        ]);
        // Sender
        Participant::create([
            'thread_id' => $thread->id,
            'user_id' => Auth::id(),
            'last_read' => new Carbon,
        ]);
        // Recipients
        if (Input::has('recipients')) {
            $thread->addParticipant($input['recipients']);
        }
        return redirect()->route('messages');
    }
    /**
     * Adds a new message to a current thread.
     *
     * @param $id
     * @return mixed
     */
    public function update($id)
    {
        try {
            $thread = Thread::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            Session::flash('error_message', 'The thread with ID: ' . $id . ' was not found.');
            return redirect()->route('messages');
        }
        $thread->activateAllParticipants();
        // Message
        Message::create([
            'thread_id' => $thread->id,
            'user_id' => Auth::id(),
            'body' => Input::get('message'),
        ]);
        // Add replier as a participant
        $participant = Participant::firstOrCreate([
            'thread_id' => $thread->id,
            'user_id' => Auth::id(),
        ]);
        $participant->last_read = new Carbon;
        $participant->save();
        // Recipients
        if (Input::has('recipients')) {
            $thread->addParticipant(Input::get('recipients'));
        }
        return redirect()->route('messages.show', $id);
    }
}