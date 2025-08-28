<?php
// This file should be run as a cron job daily
// Add to crontab: 0 9 * * * /usr/bin/php /path/to/admin/cron/send-reminders.php

require_once '../classes/EmailReminder.php';

$emailReminder = new EmailReminder();

echo "Starting email reminder process...\n";

// Send upcoming payment reminders
$upcomingCount = $emailReminder->sendUpcomingPaymentReminders();
echo "Sent {$upcomingCount} upcoming payment reminders\n";

// Send overdue reminders and late fee notifications
$overdueCount = $emailReminder->sendOverdueReminders();
echo "Sent {$overdueCount} overdue/late fee reminders\n";

echo "Email reminder process completed.\n";
?>