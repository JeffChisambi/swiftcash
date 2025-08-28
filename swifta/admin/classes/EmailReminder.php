<?php
require_once 'config/database.php';

class EmailReminder {
    private $conn;
    private $from_email = "noreply@swiftcash.mw";
    private $from_name = "SwiftCash Solutions";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function sendUpcomingPaymentReminders() {
        // Get loans due in 3 days
        $query = "SELECT l.*, c.full_name, c.email, c.phone_number
                  FROM loans l 
                  JOIN customers c ON l.customer_id = c.id 
                  WHERE l.status = 'active' 
                  AND DATEDIFF(l.due_date, CURDATE()) = 3
                  AND l.id NOT IN (
                      SELECT loan_id FROM email_reminders 
                      WHERE reminder_type = 'upcoming' 
                      AND DATE(sent_date) = CURDATE()
                  )";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($loans as $loan) {
            $this->sendUpcomingPaymentEmail($loan);
            $this->logReminder($loan['id'], 'upcoming');
        }

        return count($loans);
    }

    public function sendOverdueReminders() {
        // Get overdue loans
        $query = "SELECT l.*, c.full_name, c.email, c.phone_number,
                  DATEDIFF(CURDATE(), l.due_date) as days_overdue
                  FROM loans l 
                  JOIN customers c ON l.customer_id = c.id 
                  WHERE l.status = 'active' 
                  AND l.due_date < CURDATE()";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($loans as $loan) {
            if ($loan['days_overdue'] >= 7) {
                // Send late fee notification
                $this->sendLateFeeEmail($loan);
                $this->logReminder($loan['id'], 'late_fee');
            } else {
                // Send overdue reminder
                $this->sendOverdueEmail($loan);
                $this->logReminder($loan['id'], 'overdue');
            }
        }

        return count($loans);
    }

    private function sendUpcomingPaymentEmail($loan) {
        $subject = "Payment Reminder - SwiftCash Loan Due Soon";
        $message = $this->getUpcomingPaymentTemplate($loan);
        return $this->sendEmail($loan['email'], $subject, $message);
    }

    private function sendOverdueEmail($loan) {
        $subject = "Urgent: Overdue Payment - SwiftCash Loan";
        $message = $this->getOverdueTemplate($loan);
        return $this->sendEmail($loan['email'], $subject, $message);
    }

    private function sendLateFeeEmail($loan) {
        $late_fee = $loan['total_payable'] * 0.15;
        $subject = "Late Fee Applied - SwiftCash Loan";
        $message = $this->getLateFeeTemplate($loan, $late_fee);
        return $this->sendEmail($loan['email'], $subject, $message);
    }

    private function sendEmail($to, $subject, $message) {
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: {$this->from_name} <{$this->from_email}>" . "\r\n";
        
        return mail($to, $subject, $message, $headers);
    }

    private function getUpcomingPaymentTemplate($loan) {
        $due_date = date('F j, Y', strtotime($loan['due_date']));
        return "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #1A4E8A;'>Payment Reminder</h2>
                <p>Dear {$loan['full_name']},</p>
                <p>This is a friendly reminder that your SwiftCash loan payment is due in 3 days.</p>
                
                <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <h3>Loan Details:</h3>
                    <p><strong>Loan ID:</strong> SC{$loan['id']}</p>
                    <p><strong>Amount Due:</strong> MWK " . number_format($loan['total_payable'], 2) . "</p>
                    <p><strong>Due Date:</strong> {$due_date}</p>
                </div>
                
                <h3>Payment Options:</h3>
                <ul>
                    <li>Airtel Money: 0986002431</li>
                    <li>TNM Mpamba: 0894020215</li>
                    <li>National Bank: 1009871647</li>
                </ul>
                
                <p>Please ensure payment is made by the due date to avoid late fees.</p>
                <p>For assistance, contact us on WhatsApp: +265 986002431</p>
                
                <p>Best regards,<br>SwiftCash Solutions Team</p>
            </div>
        </body>
        </html>";
    }

    private function getOverdueTemplate($loan) {
        $days_overdue = (new DateTime())->diff(new DateTime($loan['due_date']))->days;
        return "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #dc3545;'>Urgent: Overdue Payment</h2>
                <p>Dear {$loan['full_name']},</p>
                <p>Your SwiftCash loan payment is now <strong>{$days_overdue} days overdue</strong>.</p>
                
                <div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ffc107;'>
                    <h3>Loan Details:</h3>
                    <p><strong>Loan ID:</strong> SC{$loan['id']}</p>
                    <p><strong>Amount Due:</strong> MWK " . number_format($loan['total_payable'], 2) . "</p>
                    <p><strong>Days Overdue:</strong> {$days_overdue} days</p>
                </div>
                
                <p style='color: #dc3545;'><strong>Important:</strong> A 15% late fee will be applied if payment is not received within 7-10 days of the due date.</p>
                
                <h3>Payment Options:</h3>
                <ul>
                    <li>Airtel Money: 0986002431</li>
                    <li>TNM Mpamba: 0894020215</li>
                    <li>National Bank: 1009871647</li>
                </ul>
                
                <p>Please contact us immediately to discuss payment arrangements.</p>
                <p>WhatsApp: +265 986002431 | Phone: +265 1009871647</p>
                
                <p>Best regards,<br>SwiftCash Solutions Team</p>
            </div>
        </body>
        </html>";
    }

    private function getLateFeeTemplate($loan, $late_fee) {
        return "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #dc3545;'>Late Fee Applied</h2>
                <p>Dear {$loan['full_name']},</p>
                <p>A late fee has been applied to your overdue SwiftCash loan.</p>
                
                <div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #dc3545;'>
                    <h3>Updated Loan Details:</h3>
                    <p><strong>Loan ID:</strong> SC{$loan['id']}</p>
                    <p><strong>Original Amount:</strong> MWK " . number_format($loan['total_payable'], 2) . "</p>
                    <p><strong>Late Fee (15%):</strong> MWK " . number_format($late_fee, 2) . "</p>
                    <p><strong>Total Amount Due:</strong> MWK " . number_format($loan['total_payable'] + $late_fee, 2) . "</p>
                </div>
                
                <p>Please settle this amount immediately to avoid further penalties.</p>
                
                <h3>Payment Options:</h3>
                <ul>
                    <li>Airtel Money: 0986002431</li>
                    <li>TNM Mpamba: 0894020215</li>
                    <li>National Bank: 1009871647</li>
                </ul>
                
                <p>Contact us immediately: WhatsApp +265 986002431</p>
                
                <p>Best regards,<br>SwiftCash Solutions Team</p>
            </div>
        </body>
        </html>";
    }

    private function logReminder($loan_id, $type) {
        $query = "INSERT INTO email_reminders (loan_id, reminder_type) VALUES (?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$loan_id, $type]);
    }
}
?>