<?php

namespace App\Domain\Commerce\Services;

use App\Classes\MuaadhMailer;
use App\Domain\Commerce\Models\ChatThread;
use App\Domain\Commerce\Models\ChatEntry;
use App\Domain\Commerce\Models\SupportThread;
use App\Domain\Commerce\Models\SupportMessage;
use App\Domain\Catalog\Models\CatalogEvent;
use App\Domain\Commerce\Models\Purchase;
use App\Domain\Identity\Models\User;
use App\Domain\Platform\Models\FrontendSetting;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * ChatService - Handles chat and support messaging operations
 *
 * Domain: Commerce
 * Follows DATA_FLOW_POLICY: Model -> Service -> DTO -> View
 */
class ChatService
{
    /**
     * Get paginated chat threads for a user
     */
    public function getUserChatThreads(int $userId, int $perPage = 12): LengthAwarePaginator
    {
        return ChatThread::where('sent_user', $userId)
            ->orWhere('recieved_user', $userId)
            ->paginate($perPage);
    }

    /**
     * Get a chat thread by ID
     */
    public function getChatThread(int $threadId): ChatThread
    {
        return ChatThread::findOrFail($threadId);
    }

    /**
     * Delete a chat thread and its messages
     */
    public function deleteChatThread(int $threadId): void
    {
        $thread = ChatThread::findOrFail($threadId);

        if ($thread->messages->count() > 0) {
            foreach ($thread->messages as $message) {
                $message->delete();
            }
        }

        $thread->delete();
    }

    /**
     * Send a message to a merchant
     */
    public function sendMessageToMerchant(User $sender, array $data): array
    {
        $merchant = User::where('email', $data['email'])->first();
        $seller = User::findOrFail($data['merchant_id']);

        if (!$merchant) {
            return ['success' => false, 'message' => __('Merchant Not Found')];
        }

        if ($merchant->email === $seller->email) {
            return ['success' => false, 'message' => __('You can not message yourself!!')];
        }

        // Send email
        $msg = "Name: " . $data['name'] . "\nEmail: " . $data['email'] . "\nMessage: " . $data['message'];
        $this->sendEmail($seller->email, $data['subject'], $msg);

        // Find or create thread
        $thread = ChatThread::where('sent_user', $sender->id)
            ->where('subject', $data['subject'])
            ->first();

        if ($thread) {
            // Add to existing thread
            $this->createChatEntry($thread->id, $data['message'], $sender->id);
        } else {
            // Create new thread
            $thread = $this->createChatThread([
                'subject' => $data['subject'],
                'sent_user' => $sender->id,
                'recieved_user' => $merchant->id,
                'message' => $data['message'],
            ]);
            $this->createChatEntry($thread->id, $data['message'], $sender->id);
        }

        return ['success' => true, 'message' => __('Message sent successfully')];
    }

    /**
     * Post a message to an existing thread
     */
    public function postMessage(array $data): ChatEntry
    {
        $entry = new ChatEntry();
        $entry->fill($data)->save();
        return $entry;
    }

    /**
     * Get paginated support tickets for a user
     */
    public function getUserSupportTickets(int $userId, int $perPage = 12): LengthAwarePaginator
    {
        return SupportThread::where('type', 'Ticket')
            ->where('user_id', $userId)
            ->paginate($perPage);
    }

    /**
     * Get paginated support disputes for a user
     */
    public function getUserDisputes(int $userId, int $perPage = 12): LengthAwarePaginator
    {
        return SupportThread::where('type', 'Dispute')
            ->where('user_id', $userId)
            ->paginate($perPage);
    }

    /**
     * Get a support thread by ID
     */
    public function getSupportThread(int $threadId): SupportThread
    {
        return SupportThread::findOrFail($threadId);
    }

    /**
     * Delete a support thread and its messages
     */
    public function deleteSupportThread(int $threadId): void
    {
        $thread = SupportThread::findOrFail($threadId);

        if ($thread->messages->count() > 0) {
            foreach ($thread->messages as $message) {
                $message->delete();
            }
        }

        $thread->delete();
    }

    /**
     * Post a support message
     */
    public function postSupportMessage(array $data): SupportMessage
    {
        $message = new SupportMessage();
        $message->fill($data)->save();

        // Create notification
        $notification = new CatalogEvent();
        $notification->chat_thread_id = $message->thread->id;
        $notification->save();

        return $message;
    }

    /**
     * Send a message to admin (ticket or dispute)
     */
    public function sendMessageToAdmin(User $user, array $data): array
    {
        $type = $data['type'];

        // Validate purchase for disputes
        if ($type === 'Dispute') {
            $purchaseExists = Purchase::where('purchase_number', $data['purchase'])->exists();
            if (!$purchaseExists) {
                return ['success' => false, 'message' => __('Purchase Number Not Found')];
            }
        }

        // Get admin email
        $contactEmail = FrontendSetting::first()?->contact_email;

        // Send email
        $msg = "Email: " . $user->email . "\nMessage: " . $data['message'];
        $this->sendEmail($contactEmail, $data['subject'], $msg);

        // Find or create thread
        $thread = SupportThread::where('type', $type)
            ->where('user_id', $user->id)
            ->where('subject', $data['subject'])
            ->first();

        if ($thread) {
            // Add to existing thread
            $this->createSupportMessage($thread->id, $data['message'], $user->id);
        } else {
            // Create new thread
            $thread = $this->createSupportThread([
                'subject' => $data['subject'],
                'user_id' => $user->id,
                'message' => $data['message'],
                'purchase_number' => $data['purchase'] ?? null,
                'type' => $type,
            ]);

            // Create notification
            $notification = new CatalogEvent();
            $notification->chat_thread_id = $thread->id;
            $notification->save();

            $this->createSupportMessage($thread->id, $data['message'], $user->id);
        }

        return ['success' => true, 'message' => __('Message sent successfully')];
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    private function sendEmail(string $to, string $subject, string $body): void
    {
        $mailer = new MuaadhMailer();
        $mailer->sendCustomMail([
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
        ]);
    }

    private function createChatThread(array $data): ChatThread
    {
        $thread = new ChatThread();
        $thread->fill($data)->save();
        return $thread;
    }

    private function createChatEntry(int $threadId, string $message, int $userId): ChatEntry
    {
        $entry = new ChatEntry();
        $entry->chat_thread_id = $threadId;
        $entry->message = $message;
        $entry->sent_user = $userId;
        $entry->save();
        return $entry;
    }

    private function createSupportThread(array $data): SupportThread
    {
        $thread = new SupportThread();
        $thread->fill($data)->save();
        return $thread;
    }

    private function createSupportMessage(int $threadId, string $message, int $userId): SupportMessage
    {
        $msg = new SupportMessage();
        $msg->thread_id = $threadId;
        $msg->message = $message;
        $msg->user_id = $userId;
        $msg->save();
        return $msg;
    }
}
