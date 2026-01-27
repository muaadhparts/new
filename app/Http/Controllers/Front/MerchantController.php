<?php

namespace App\Http\Controllers\Front;

use App\Domain\Identity\Models\User;
use App\Classes\MuaadhMailer;
use App\Domain\Commerce\Models\ChatThread;
use App\Domain\Commerce\Models\ChatEntry;
use App\Domain\Merchant\Services\MerchantCatalogService;
use Illuminate\{
    Http\Request,
    Support\Facades\DB
};

class MerchantController extends FrontBaseController
{
    public function __construct(
        private MerchantCatalogService $merchantCatalogService
    ) {
        parent::__construct();
    }

    public function index(Request $request, $slug)
    {
        // Find merchant by slug
        $merchant = $this->merchantCatalogService->findMerchantBySlug($slug);

        // If no merchant found, return 404
        // Note: static_content table was dropped - static pages no longer supported
        if (empty($merchant)) {
            return response()->view('errors.404', [], 404);
        }

        $data['merchant'] = $merchant;
        $data['categories'] = collect();

        // Get filter options from service (merchant-scoped)
        $data['quality_brands'] = $this->merchantCatalogService->getAvailableQualityBrands($merchant->id);
        $data['branches'] = $this->merchantCatalogService->getAvailableBranches($merchant->id);

        // Get latest products (platform-wide)
        $data['latest_products'] = $this->merchantCatalogService->getLatestProducts(5);

        // Build filters from request
        $filters = [
            'quality_brand' => $request->input('quality_brand', []),
            'branch' => $request->input('branch', []),
            'sort' => $request->sort,
            'pageby' => $request->pageby,
            'type' => $request->has('type') ? $request->type : null,
        ];

        // Get filtered catalog items
        $defaultPerPage = (int) ($this->gs->page_count ?? 12);
        $data['vprods'] = $this->merchantCatalogService->getFilteredCatalogItems(
            $merchant->id,
            $filters,
            $defaultPerPage
        );

        if ($request->ajax()) {
            $data['ajax_check'] = 1;
            return view('frontend.ajax.merchant', $data);
        }

        return view('frontend.merchant', $data);
    }

    //Send email to user
    public function merchantcontact(Request $request)
    {
        $gs     = $this->gs;
        $user   = User::findOrFail($request->user_id);
        $merchant = User::findOrFail($request->merchant_id);

        $subject = $request->subject;
        $to      = $merchant->email;
        $name    = $request->name;
        $from    = $request->email;
        $msg     = "Name: " . $name . "\nEmail: " . $from . "\nMessage: " . $request->message;

        if ($gs->mail_driver) {
            $data = [
                'to'      => $to,
                'subject' => $subject,
                'body'    => $msg,
            ];

            $mailer = new MuaadhMailer();
            $mailer->sendCustomMail($data);
        } else {
            $headers = "From: " . $gs->from_name . "<" . $gs->from_email . ">";
            mail($to, $subject, $msg, $headers);
        }

        $conv = ChatThread::where('sent_user', '=', $user->id)->where('subject', '=', $subject)->first();
        if (isset($conv)) {
            $msg = new ChatEntry();
            $msg->chat_thread_id = $conv->id;
            $msg->message         = $request->message;
            $msg->sent_user       = $user->id;
            $msg->save();
            return response()->json(__('Message Sent!'));
        } else {
            $message                 = new ChatThread();
            $message->subject        = $subject;
            $message->sent_user      = $request->user_id;
            $message->recieved_user  = $request->merchant_id;
            $message->message        = $request->message;
            $message->save();

            $msg = new ChatEntry();
            $msg->chat_thread_id = $message->id;
            $msg->message         = $request->message;
            $msg->sent_user       = $request->user_id;
            $msg->save();
            return response()->json(__('Message Sent!'));
        }
    }
}
