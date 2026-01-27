<?php

namespace App\Classes;

use App\{
    Domain\Commerce\Models\Purchase,
    Domain\Platform\Models\CommsBlueprint
};
use Barryvdh\DomPDF\Facade\Pdf;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Illuminate\Support\Str;

class MuaadhMailer
{

    public $mail;
    protected $settings;

    public function __construct()
    {
        $this->settings = platformSettings();

        $this->mail = new PHPMailer(true);

        if ($this->settings->get('mail_driver')) {

            $this->mail->isSMTP();                          // Send using SMTP
            $this->mail->Host       = $this->settings->get('mail_host');       // Set the SMTP server to send through
            $this->mail->SMTPAuth   = true;                 // Enable SMTP authentication
            $this->mail->Username   = $this->settings->get('mail_user');   // SMTP username
            $this->mail->Password   = $this->settings->get('mail_pass');   // SMTP password
            $this->mail->SMTPSecure = $this->settings->get('mail_encryption');      // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
            $this->mail->Port       = $this->settings->get('mail_port');
        }
    }


    public function sendAutoPurchaseMail(array $mailData, $id)
    {
        $temp = CommsBlueprint::where('email_type', '=', $mailData['type'])->first();

        // إذا لم يوجد قالب البريد، تخطى الإرسال
        if (!$temp) {
            \Log::warning('Email template not found: ' . $mailData['type']);
            return false;
        }

        $purchase = Purchase::findOrFail($id);
        // Model cast handles decoding; handle legacy double-encoded data
        $cart = $purchase->cart;
        if (is_string($cart)) {
            $cart = json_decode($cart, true);
        }
        try {

            $body = preg_replace("/{customer_name}/", $mailData['cname'], $temp->email_body);
            $body = preg_replace("/{order_amount}/", $mailData['oamount'], $body);
            $body = preg_replace("/{admin_name}/", $mailData['aname'], $body);
            $body = preg_replace("/{admin_email}/", $mailData['aemail'], $body);
            $body = preg_replace("/{order_number}/", $mailData['onumber'], $body);
            $body = preg_replace("/{website_name}/", $this->settings->get('site_name'), $body);


            $dir = public_path('assets/temp_files');
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            // dd(['__fn__' => __FUNCTION__, 'dirExists' => is_dir($dir), 'dir' => $dir]); // فحص سريع
            $fileName = $dir . DIRECTORY_SEPARATOR . Str::random(4) . time() . '.pdf';

            // Pre-compute shipping names for view (DATA_FLOW_POLICY)
            $shippingNamesFormatted = $purchase->getFormattedShippingNames();

            // PRE-COMPUTED: PDF calculations (DATA_FLOW_POLICY - no @php in view)
            $pdfCalculations = $this->calculatePdfTotals($purchase, $cart);

            $pdf = PDF::loadView('pdf.purchase', [
                'purchase' => $purchase,
                'cart' => $cart,
                'shippingNamesFormatted' => $shippingNamesFormatted,
                'pdfCalculations' => $pdfCalculations,
            ])->save($fileName);

            //Recipients
            $this->mail->setFrom($this->settings->get('from_email'), $this->settings->get('from_name'));
            $this->mail->addAddress($mailData['to']);     // Add a recipient

            // Attachments
            $this->mail->addAttachment($fileName);

            // Content
            $this->mail->isHTML(true);

            $this->mail->Subject = $temp->email_subject;

            $this->mail->Body = $body;

            $this->mail->send();
        } catch (Exception $e) {
            // dd($e);
        }

        $files = glob('assets/temp_files/*'); //get all file names
        foreach ($files as $file) {
            if (is_file($file))
                unlink($file); //delete file
        }

        return true;
    }

    public function sendAutoMail(array $mailData)
    {

        $temp = CommsBlueprint::where('email_type', '=', $mailData['type'])->first();

        // إذا لم يوجد قالب البريد، تخطى الإرسال
        if (!$temp) {
            \Log::warning('Email template not found: ' . $mailData['type']);
            return false;
        }

        try {

            $body = preg_replace("/{customer_name}/", $mailData['cname'], $temp->email_body);
            $body = preg_replace("/{order_amount}/", $mailData['oamount'], $body);
            $body = preg_replace("/{admin_name}/", $mailData['aname'], $body);
            $body = preg_replace("/{admin_email}/", $mailData['aemail'], $body);
            $body = preg_replace("/{order_number}/", $mailData['onumber'], $body);
            $body = preg_replace("/{website_name}/", $this->settings->get('site_name'), $body);

            //Recipients
            $this->mail->setFrom($this->settings->get('from_email'), $this->settings->get('from_name'));
            $this->mail->addAddress($mailData['to']);     // Add a recipient

            // Content
            $this->mail->isHTML(true);

            $this->mail->Subject = $temp->email_subject;

            $this->mail->Body = $body;

            $this->mail->send();
        } catch (Exception $e) {
            // dd($e);
        }

        return true;
    }

    public function sendCustomMail(array $mailData)
    {
        try {

            //Recipients
            $this->mail->setFrom($this->settings->get('from_email'), $this->settings->get('from_name'));
            $this->mail->addAddress($mailData['to']);     // Add a recipient

            // Content
            $this->mail->isHTML(true);

            $this->mail->Subject = $mailData['subject'];

            $this->mail->Body = $mailData['body'];

            $this->mail->send();
        } catch (Exception $e) {
            // dd($e);
        }

        return true;
    }

    /**
     * Calculate totals for PDF purchase
     * PRE-COMPUTED: All values to avoid @php in view (DATA_FLOW_POLICY)
     */
    private function calculatePdfTotals($purchase, array $cart): array
    {
        $subtotal = 0;
        $items = $cart['items'] ?? [];

        // Sum all item prices
        foreach ($items as $item) {
            $subtotal += round(($item['price'] ?? 0) * $purchase->currency_value, 2);
        }

        // Tax calculation
        $tax = 0;
        $showTax = false;
        if ($purchase->tax != 0) {
            $tax = ($subtotal / 100) * $purchase->tax;
            $showTax = true;
        }

        // Total with tax
        $total = $subtotal + $tax;

        // Add shipping if applicable
        if ($purchase->shipping_cost > 0) {
            $total += $purchase->shipping_cost;
        }

        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'showTax' => $showTax,
            'total' => $total,
        ];
    }
}
