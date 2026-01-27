<?php

namespace App\Http\Controllers\User;

use App\Domain\Commerce\Services\ChatService;
use Illuminate\Http\Request;

/**
 * ChatController - Handles user chat and support messaging
 *
 * Follows DATA_FLOW_POLICY: Uses ChatService for all business logic
 */
class ChatController extends UserBaseController
{
    public function __construct(
        private ChatService $chatService
    ) {
        parent::__construct();
    }

    public function messages()
    {
        $convs = $this->chatService->getUserChatThreads($this->user->id);

        return view('user.chat.index', [
            'user' => $this->user,
            'convs' => $convs,
        ]);
    }

    public function message($id)
    {
        $conv = $this->chatService->getChatThread($id);

        return view('user.chat.create', [
            'user' => $this->user,
            'conv' => $conv,
        ]);
    }

    public function messagedelete($id)
    {
        $this->chatService->deleteChatThread($id);

        return redirect()->back()->with('success', __('Message Deleted Successfully'));
    }

    public function msgload($id)
    {
        $conv = $this->chatService->getChatThread($id);

        return view('load.user-chat-message', [
            'conv' => $conv,
        ]);
    }

    public function usercontact(Request $request)
    {
        $result = $this->chatService->sendMessageToMerchant($this->user, [
            'user_id' => $request->user_id,
            'merchant_id' => $request->merchant_id,
            'email' => $request->email,
            'name' => $request->name,
            'subject' => $request->subject,
            'message' => $request->message,
        ]);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('unsuccess', $result['message']);
    }

    public function postmessage(Request $request)
    {
        $this->chatService->postMessage($request->all());

        return back()->with('success', __('Message Sent Successfully'));
    }

    public function adminmessages()
    {
        $convs = $this->chatService->getUserSupportTickets($this->user->id);

        return view('user.ticket.index', [
            'convs' => $convs,
        ]);
    }

    public function adminDiscordmessages()
    {
        $convs = $this->chatService->getUserDisputes($this->user->id);

        return view('user.dispute.index', [
            'convs' => $convs,
        ]);
    }

    public function messageload($id)
    {
        $conv = $this->chatService->getSupportThread($id);

        return view('load.user-support-message', [
            'conv' => $conv,
        ]);
    }

    public function adminmessage($id)
    {
        $conv = $this->chatService->getSupportThread($id);

        return view('user.ticket.create', [
            'conv' => $conv,
        ]);
    }

    public function adminmessagedelete($id)
    {
        $this->chatService->deleteSupportThread($id);

        return redirect()->back()->with('success', __('Message Deleted Successfully'));
    }

    public function adminpostmessage(Request $request)
    {
        $this->chatService->postSupportMessage($request->all());

        return back()->with('success', __('Message Sent Successfully'));
    }

    public function adminusercontact(Request $request)
    {
        $result = $this->chatService->sendMessageToAdmin($this->user, [
            'type' => $request->type,
            'subject' => $request->subject,
            'message' => $request->message,
            'purchase' => $request->purchase,
        ]);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('unsuccess', $result['message']);
    }
}
