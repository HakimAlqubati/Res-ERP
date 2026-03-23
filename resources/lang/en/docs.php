<?php

return [
    'system_documentation' => 'System Documentation',
    'system_documentation_guide' => 'System Reference Guide',
    'cron_title' => 'Automated Scheduled Tasks',
    'cron_desc' => 'A comprehensive overview of operations executed automatically by the system in the background. These processes aim to maintain system performance, security, and stability without human intervention.',
    'cron_backup_title' => 'System Archiving & Backup',
    'cron_backup_content' => '<p>This process runs exactly every <strong>6 hours</strong>.</p><p>Its purpose is to periodically take automated backups of the databases to ensure maximum data protection and ease of rapid recovery when necessary.</p><hr><p><small style="color: #88126e; opacity: 0.8; font-weight: 600;">Technical Reference - Command: <code>tenant:backup</code></small></p>',
    'cron_warnings_title' => 'Automated System Alerts',
    'cron_warnings_content' => '<p>This mechanism executes every <strong>4 hours</strong>.</p><p>It performs checks on system requirements and automatically triggers the necessary warning notifications for management action.</p><hr><p><small style="color: #88126e; opacity: 0.8; font-weight: 600;">Technical Reference - Command: <code>notifications:warning</code></small></p>',
    'cron_overtime_title' => 'Automatic Overtime Processing',
    'cron_overtime_content' => '<p>This critical function runs continuously every <strong>half hour (30 minutes)</strong>.</p><p>Its role is to fully automate the calculation and approval of employee overtime hours instantly upon the end of shifts, eliminating manual administrative friction and human errors.</p><hr><p><small style="color: #88126e; opacity: 0.8; font-weight: 600;">Technical Reference - Command: <code>hr:overtime:auto-process</code></small></p>',
];
