<?php

namespace App\Modules\Docs\Services;

class WorkbenchDocsService
{
    /**
     * Retrieve the structured documentation modules.
     * Designed to be highly scalable and cleanly isolated within its module.
     *
     * @return array
     */
    public static function getDocs(): array
    {
        return [
            'console_commands' => [
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
                'title' => __('docs.cron_title'),
                'description' => __('docs.cron_desc'),
                'sections' => [
                    [
                        'title' => __('docs.cron_backup_title'),
                        'content' => __('docs.cron_backup_content'),
                    ],
                    [
                        'title' => __('docs.cron_warnings_title'),
                        'content' => __('docs.cron_warnings_content'),
                    ],
                    [
                        'title' => __('docs.cron_overtime_title'),
                        'content' => __('docs.cron_overtime_content'),
                    ]
                ]
            ],
            // Scale and add future docs here easily.
        ];
    }
}
