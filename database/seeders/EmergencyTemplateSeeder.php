<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\EmergencyTemplate;
use App\Models\EmergencyEscalationRule;

class EmergencyTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Medical Emergency - Critical',
                'code' => 'MED_CRITICAL',
                'emergency_type' => 'medical',
                'severity' => 'critical',
                'title_template' => 'MEDICAL EMERGENCY: {user_name} requires immediate medical attention',
                'message_template' => 'URGENT: {user_name} ({user_phone}) is experiencing a medical emergency at {location}. Please contact immediately. Emergency details: {description}',
                'notification_channels' => ['sms', 'email', 'whatsapp'],
                'escalation_delay_minutes' => 5,
                'is_active' => true,
                'description' => 'Critical medical emergency template for immediate response'
            ],
            [
                'name' => 'Medical Emergency - High',
                'code' => 'MED_HIGH',
                'emergency_type' => 'medical',
                'severity' => 'high',
                'title_template' => 'Medical Emergency: {user_name} needs medical assistance',
                'message_template' => 'Medical emergency: {user_name} ({user_phone}) needs medical assistance at {location}. Details: {description}',
                'notification_channels' => ['sms', 'email'],
                'escalation_delay_minutes' => 15,
                'is_active' => true,
                'description' => 'High priority medical emergency template'
            ],
            [
                'name' => 'Safety Emergency - Critical',
                'code' => 'SAFETY_CRITICAL',
                'emergency_type' => 'safety',
                'severity' => 'critical',
                'title_template' => 'SAFETY EMERGENCY: {user_name} in danger',
                'message_template' => 'URGENT SAFETY ALERT: {user_name} ({user_phone}) is in immediate danger at {location}. Please respond immediately. Details: {description}',
                'notification_channels' => ['sms', 'email', 'whatsapp'],
                'escalation_delay_minutes' => 5,
                'is_active' => true,
                'description' => 'Critical safety emergency template'
            ],
            [
                'name' => 'Safety Emergency - High',
                'code' => 'SAFETY_HIGH',
                'emergency_type' => 'safety',
                'severity' => 'high',
                'title_template' => 'Safety Alert: {user_name} safety concern',
                'message_template' => 'Safety alert: {user_name} ({user_phone}) has a safety concern at {location}. Details: {description}',
                'notification_channels' => ['sms', 'email'],
                'escalation_delay_minutes' => 15,
                'is_active' => true,
                'description' => 'High priority safety emergency template'
            ],
            [
                'name' => 'Security Emergency - Critical',
                'code' => 'SECURITY_CRITICAL',
                'emergency_type' => 'security',
                'severity' => 'critical',
                'title_template' => 'SECURITY EMERGENCY: {user_name} security threat',
                'message_template' => 'URGENT SECURITY ALERT: {user_name} ({user_phone}) is facing a security threat at {location}. Immediate response required. Details: {description}',
                'notification_channels' => ['sms', 'email', 'whatsapp'],
                'escalation_delay_minutes' => 5,
                'is_active' => true,
                'description' => 'Critical security emergency template'
            ],
            [
                'name' => 'Technical Emergency - High',
                'code' => 'TECH_HIGH',
                'emergency_type' => 'technical',
                'severity' => 'high',
                'title_template' => 'Technical Emergency: {user_name} equipment failure',
                'message_template' => 'Technical emergency: {user_name} ({user_phone}) is experiencing equipment failure at {location}. Technical support needed. Details: {description}',
                'notification_channels' => ['sms', 'email'],
                'escalation_delay_minutes' => 30,
                'is_active' => true,
                'description' => 'High priority technical emergency template'
            ],
            [
                'name' => 'General Emergency - Medium',
                'code' => 'GEN_MEDIUM',
                'emergency_type' => 'other',
                'severity' => 'medium',
                'title_template' => 'Emergency: {user_name} needs assistance',
                'message_template' => 'Emergency: {user_name} ({user_phone}) needs assistance at {location}. Details: {description}',
                'notification_channels' => ['sms', 'email'],
                'escalation_delay_minutes' => 60,
                'is_active' => true,
                'description' => 'Medium priority general emergency template'
            ],
            [
                'name' => 'General Emergency - Low',
                'code' => 'GEN_LOW',
                'emergency_type' => 'other',
                'severity' => 'low',
                'title_template' => 'Alert: {user_name} situation',
                'message_template' => 'Alert: {user_name} ({user_phone}) has a situation at {location}. Details: {description}',
                'notification_channels' => ['email'],
                'escalation_delay_minutes' => 120,
                'is_active' => true,
                'description' => 'Low priority general emergency template'
            ]
        ];

        foreach ($templates as $templateData) {
            $template = EmergencyTemplate::updateOrCreate(
                ['code' => $templateData['code']],
                $templateData
            );

            // Create default escalation rules for each template
            $this->createEscalationRules($template);
        }

        $this->command->info('Emergency templates seeded successfully!');
    }

    private function createEscalationRules($template)
    {
        $escalationRules = [];

        // Define escalation rules based on severity
        switch ($template->severity) {
            case 'critical':
                $escalationRules = [
                    [
                        'delay_minutes' => 5,
                        'escalation_type' => 'notify_all_contacts',
                        'escalation_message' => 'CRITICAL: No response received. Notifying all emergency contacts.',
                        'priority_order' => 1
                    ],
                    [
                        'delay_minutes' => 15,
                        'escalation_type' => 'contact_manager',
                        'escalation_message' => 'CRITICAL: Escalating to manager. No response from emergency contacts.',
                        'priority_order' => 2
                    ],
                    [
                        'delay_minutes' => 30,
                        'escalation_type' => 'contact_emergency_services',
                        'escalation_message' => 'CRITICAL: Escalating to emergency services. No response received.',
                        'priority_order' => 3
                    ]
                ];
                break;

            case 'high':
                $escalationRules = [
                    [
                        'delay_minutes' => 15,
                        'escalation_type' => 'notify_all_contacts',
                        'escalation_message' => 'HIGH PRIORITY: No response received. Notifying all emergency contacts.',
                        'priority_order' => 1
                    ],
                    [
                        'delay_minutes' => 45,
                        'escalation_type' => 'contact_manager',
                        'escalation_message' => 'HIGH PRIORITY: Escalating to manager. No response from emergency contacts.',
                        'priority_order' => 2
                    ]
                ];
                break;

            case 'medium':
                $escalationRules = [
                    [
                        'delay_minutes' => 30,
                        'escalation_type' => 'notify_all_contacts',
                        'escalation_message' => 'MEDIUM PRIORITY: No response received. Notifying all emergency contacts.',
                        'priority_order' => 1
                    ],
                    [
                        'delay_minutes' => 90,
                        'escalation_type' => 'contact_manager',
                        'escalation_message' => 'MEDIUM PRIORITY: Escalating to manager. No response from emergency contacts.',
                        'priority_order' => 2
                    ]
                ];
                break;

            case 'low':
                $escalationRules = [
                    [
                        'delay_minutes' => 60,
                        'escalation_type' => 'notify_all_contacts',
                        'escalation_message' => 'LOW PRIORITY: No response received. Notifying all emergency contacts.',
                        'priority_order' => 1
                    ]
                ];
                break;
        }

        foreach ($escalationRules as $ruleData) {
            EmergencyEscalationRule::updateOrCreate(
                [
                    'emergency_template_id' => $template->id,
                    'priority_order' => $ruleData['priority_order']
                ],
                array_merge($ruleData, [
                    'is_active' => true
                ])
            );
        }
    }
}