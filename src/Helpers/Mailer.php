<?php
namespace Helpers;

use Core\Config;

class Mailer
{
    public static function send(string $to, string $subject, string $body, string $from = ''): bool
    {
        if (empty($from)) {
            $from = 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'sheriffenterprises.com');
        }

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: $from\r\n";
        $headers .= "Reply-To: $from\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        return mail($to, $subject, $body, $headers);
    }

    public static function sendOrderNotification(string $businessEmail, string $customerEmail, array $order): void
    {
        $itemsHtml = '';
        foreach ($order['items'] as $item) {
            $itemsHtml .= '<tr><td style="padding:8px;border-bottom:1px solid #eee;">' . htmlspecialchars($item['product_name']) . '</td>';
            $itemsHtml .= '<td style="padding:8px;border-bottom:1px solid #eee;text-align:center;">x' . (int)($item['qty'] ?? 1) . '</td>';
            $itemsHtml .= '<td style="padding:8px;border-bottom:1px solid #eee;text-align:right;">₦' . number_format((float)$item['selling_price'] * (int)($item['qty'] ?? 1)) . '</td></tr>';
        }

        $total = $order['total'] ?? 0;
        $name = htmlspecialchars($order['name'] ?? 'Customer');
        $phone = htmlspecialchars($order['phone'] ?? '');
        $address = htmlspecialchars($order['address'] ?? '');
        $payment = htmlspecialchars($order['payment_method'] ?? 'Cash on Delivery');

        $subjectBusiness = "New Order from $name - Sheriff Shevvy Enterprises";
        $subjectCustomer = "Your Order Confirmation - Sheriff Shevvy Enterprises";

        $bodyBusiness = "
<!DOCTYPE html>
<html>
<head><meta charset='UTF-8'></head>
<body style='font-family:Arial,sans-serif;background:#f5f5f5;padding:20px;'>
<div style='max-width:600px;margin:auto;background:#fff;border-radius:8px;overflow:hidden;'>
<div style='background:#020617;color:#fbbf24;padding:20px;text-align:center;'>
<h2 style='margin:0;'>New Order Received</h2>
<p style='margin:5px 0 0;color:#94a3b8;'>Sheriff Shevvy Enterprises</p>
</div>
<div style='padding:20px;'>
<h3>Customer Details</h3>
<p><strong>Name:</strong> $name</p>
<p><strong>Phone:</strong> $phone</p>
<p><strong>Address:</strong> $address</p>
<p><strong>Payment:</strong> $payment</p>
<h3>Order Items</h3>
<table style='width:100%;border-collapse:collapse;'>
<thead><tr style='background:#f8fafc;'><th style='padding:8px;text-align:left;'>Item</th><th style='padding:8px;text-align:center;'>Qty</th><th style='padding:8px;text-align:right;'>Total</th></tr></thead>
<tbody>$itemsHtml</tbody>
</table>
<h3 style='text-align:right;margin-top:15px;'>Total: ₦" . number_format($total) . "</h3>
</div>
<div style='background:#f8fafc;padding:15px;text-align:center;color:#64748b;font-size:12px;'>
<p>Lalubu Street, Oke-Ilewo, Abeokuta, Ogun State, Nigeria | +234 803 248 8020</p>
</div>
</div>
</body>
</html>";

        $bodyCustomer = "
<!DOCTYPE html>
<html>
<head><meta charset='UTF-8'></head>
<body style='font-family:Arial,sans-serif;background:#f5f5f5;padding:20px;'>
<div style='max-width:600px;margin:auto;background:#fff;border-radius:8px;overflow:hidden;'>
<div style='background:#020617;color:#fbbf24;padding:20px;text-align:center;'>
<h2 style='margin:0;'>Order Confirmation</h2>
<p style='margin:5px 0 0;color:#94a3b8;'>Thank you for your order, $name!</p>
</div>
<div style='padding:20px;'>
<h3>Order Summary</h3>
<table style='width:100%;border-collapse:collapse;'>
<thead><tr style='background:#f8fafc;'><th style='padding:8px;text-align:left;'>Item</th><th style='padding:8px;text-align:center;'>Qty</th><th style='padding:8px;text-align:right;'>Total</th></tr></thead>
<tbody>$itemsHtml</tbody>
</table>
<h3 style='text-align:right;margin-top:15px;'>Total: ₦" . number_format($total) . "</h3>
<p style='margin-top:20px;padding:15px;background:#f0fdf4;border-radius:6px;'>
<strong>Payment Method:</strong> $payment<br>
<strong>Delivery Address:</strong> $address
</p>
<p style='color:#64748b;font-size:14px;'>We will contact you at <strong>$phone</strong> to confirm your order. For questions, call +234 803 248 8020.</p>
</div>
<div style='background:#f8fafc;padding:15px;text-align:center;color:#64748b;font-size:12px;'>
<p>Lalubu Street, Oke-Ilewo, Abeokuta, Ogun State, Nigeria | +234 803 248 8020</p>
</div>
</div>
</body>
</html>";

        self::send($businessEmail, $subjectBusiness, $bodyBusiness);
        if (!empty($customerEmail)) {
            self::send($customerEmail, $subjectCustomer, $bodyCustomer);
        }
    }
}
