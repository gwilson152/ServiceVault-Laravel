<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'key' => 'ticket_created_default',
                'name' => 'New Ticket Created',
                'template_type' => 'ticket_created',
                'subject' => 'New Ticket Created: {{ticket_number}} - {{ticket_title}}',
                'body_text' => "Hello {{customer_name}},\n\nYour support ticket has been created successfully.\n\nTicket Details:\n- Number: {{ticket_number}}\n- Title: {{ticket_title}}\n- Priority: {{ticket_priority}}\n- Status: {{ticket_status}}\n- Category: {{ticket_category}}\n\nDescription:\n{{ticket_description}}\n\nWe will review your request and get back to you soon.\n\nBest regards,\n{{account_name}} Support Team",
                'body_html' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
    <h2 style="color: #333; border-bottom: 2px solid #007cba; padding-bottom: 10px;">New Ticket Created</h2>
    
    <p>Hello <strong>{{customer_name}}</strong>,</p>
    
    <p>Your support ticket has been created successfully.</p>
    
    <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <h3 style="margin-top: 0; color: #007cba;">Ticket Details</h3>
        <ul style="list-style: none; padding: 0; margin: 0;">
            <li style="margin: 5px 0;"><strong>Number:</strong> {{ticket_number}}</li>
            <li style="margin: 5px 0;"><strong>Title:</strong> {{ticket_title}}</li>
            <li style="margin: 5px 0;"><strong>Priority:</strong> <span style="text-transform: capitalize;">{{ticket_priority}}</span></li>
            <li style="margin: 5px 0;"><strong>Status:</strong> <span style="text-transform: capitalize;">{{ticket_status}}</span></li>
            <li style="margin: 5px 0;"><strong>Category:</strong> <span style="text-transform: capitalize;">{{ticket_category}}</span></li>
        </ul>
    </div>
    
    <div style="margin: 20px 0;">
        <h4 style="color: #333;">Description:</h4>
        <div style="background-color: #f8f9fa; padding: 10px; border-left: 4px solid #007cba; font-family: monospace;">
            {{ticket_description}}
        </div>
    </div>
    
    <p>We will review your request and get back to you soon.</p>
    
    <p>Best regards,<br><strong>{{account_name}} Support Team</strong></p>
    
    <hr style="margin: 30px 0; border: none; border-top: 1px solid #ddd;">
    <p style="font-size: 12px; color: #666;">This is an automated message from {{system_name}}. Please do not reply directly to this email.</p>
</div>',
                'is_default' => true,
                'priority' => 100,
                'description' => 'Default template for new ticket creation notifications',
                'variables' => [
                    'customer_name', 'ticket_number', 'ticket_title', 'ticket_priority', 
                    'ticket_status', 'ticket_category', 'ticket_description', 'account_name', 'system_name'
                ],
            ],
            [
                'key' => 'ticket_assigned_default',
                'name' => 'Ticket Assigned',
                'template_type' => 'ticket_assigned',
                'subject' => 'Ticket {{ticket_number}} assigned to {{agent_name}}',
                'body_text' => "Hello {{customer_name}},\n\nYour ticket {{ticket_number}} - \"{{ticket_title}}\" has been assigned to {{agent_name}}.\n\nOur agent will be in touch with you shortly to assist with your request.\n\nTicket Details:\n- Priority: {{ticket_priority}}\n- Category: {{ticket_category}}\n- Agent: {{agent_name}} ({{agent_email}})\n\nBest regards,\n{{account_name}} Support Team",
                'body_html' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
    <h2 style="color: #333; border-bottom: 2px solid #28a745; padding-bottom: 10px;">Ticket Assigned</h2>
    
    <p>Hello <strong>{{customer_name}}</strong>,</p>
    
    <p>Your ticket <strong>{{ticket_number}}</strong> - "{{ticket_title}}" has been assigned to <strong>{{agent_name}}</strong>.</p>
    
    <div style="background-color: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #28a745;">
        <h3 style="margin-top: 0; color: #155724;">Agent Information</h3>
        <p style="margin: 5px 0;"><strong>Name:</strong> {{agent_name}}</p>
        <p style="margin: 5px 0;"><strong>Email:</strong> {{agent_email}}</p>
    </div>
    
    <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <h4 style="margin-top: 0; color: #333;">Ticket Details</h4>
        <ul style="list-style: none; padding: 0; margin: 0;">
            <li style="margin: 5px 0;"><strong>Priority:</strong> <span style="text-transform: capitalize;">{{ticket_priority}}</span></li>
            <li style="margin: 5px 0;"><strong>Category:</strong> <span style="text-transform: capitalize;">{{ticket_category}}</span></li>
        </ul>
    </div>
    
    <p>Our agent will be in touch with you shortly to assist with your request.</p>
    
    <p>Best regards,<br><strong>{{account_name}} Support Team</strong></p>
    
    <hr style="margin: 30px 0; border: none; border-top: 1px solid #ddd;">
    <p style="font-size: 12px; color: #666;">This is an automated message from {{system_name}}.</p>
</div>',
                'is_default' => true,
                'priority' => 100,
                'description' => 'Default template for ticket assignment notifications',
                'variables' => [
                    'customer_name', 'ticket_number', 'ticket_title', 'ticket_priority', 
                    'ticket_category', 'agent_name', 'agent_email', 'account_name', 'system_name'
                ],
            ],
            [
                'key' => 'comment_added_default',
                'name' => 'New Comment Added',
                'template_type' => 'comment_added',
                'subject' => 'New comment on ticket {{ticket_number}}',
                'body_text' => "Hello,\n\nA new comment has been added to ticket {{ticket_number}} - \"{{ticket_title}}\".\n\nFrom: {{comment_author}}\nDate: {{comment_date:M j, Y g:i A}}\n\nComment:\n{{comment_content}}\n\nYou can view the full ticket and respond by replying to this email.\n\nBest regards,\n{{account_name}} Support Team",
                'body_html' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
    <h2 style="color: #333; border-bottom: 2px solid #17a2b8; padding-bottom: 10px;">New Comment Added</h2>
    
    <p>Hello,</p>
    
    <p>A new comment has been added to ticket <strong>{{ticket_number}}</strong> - "{{ticket_title}}".</p>
    
    <div style="background-color: #d1ecf1; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #17a2b8;">
        <p style="margin: 5px 0;"><strong>From:</strong> {{comment_author}}</p>
        <p style="margin: 5px 0;"><strong>Date:</strong> {{comment_date:M j, Y g:i A}}</p>
    </div>
    
    <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <h4 style="margin-top: 0; color: #333;">Comment:</h4>
        <div style="background-color: white; padding: 10px; border: 1px solid #ddd; border-radius: 3px; font-family: Arial, sans-serif; line-height: 1.5;">
            {{comment_content}}
        </div>
    </div>
    
    <p>You can view the full ticket and respond by replying to this email.</p>
    
    <p>Best regards,<br><strong>{{account_name}} Support Team</strong></p>
    
    <hr style="margin: 30px 0; border: none; border-top: 1px solid #ddd;">
    <p style="font-size: 12px; color: #666;">This is an automated message from {{system_name}}.</p>
</div>',
                'is_default' => true,
                'priority' => 100,
                'description' => 'Default template for new comment notifications',
                'variables' => [
                    'ticket_number', 'ticket_title', 'comment_author', 'comment_date', 
                    'comment_content', 'account_name', 'system_name'
                ],
            ],
            [
                'key' => 'command_confirmation_default',
                'name' => 'Email Command Confirmation',
                'template_type' => 'command_confirmation',
                'subject' => 'Command executed successfully',
                'body_text' => "Hello,\n\nYour email command has been processed successfully.\n\nCommand: {{command}}\nResult: {{result}}\n\n{{#if ticket_number}}Related Ticket: {{ticket_number}}{{/if}}\n\nYou can continue to use email commands by including them in the subject line of your emails (e.g., time:30, priority:high, status:resolved).\n\nBest regards,\n{{account_name}} Support Team",
                'body_html' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
    <h2 style="color: #333; border-bottom: 2px solid #28a745; padding-bottom: 10px;">✓ Command Executed Successfully</h2>
    
    <p>Hello,</p>
    
    <p>Your email command has been processed successfully.</p>
    
    <div style="background-color: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #28a745;">
        <h3 style="margin-top: 0; color: #155724;">Execution Details</h3>
        <p style="margin: 5px 0;"><strong>Command:</strong> <code style="background-color: #f8f9fa; padding: 2px 4px; border-radius: 3px;">{{command}}</code></p>
        <p style="margin: 5px 0;"><strong>Result:</strong> {{result}}</p>
        {{#if ticket_number}}<p style="margin: 5px 0;"><strong>Related Ticket:</strong> {{ticket_number}}</p>{{/if}}
    </div>
    
    <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <h4 style="margin-top: 0; color: #333;">Available Email Commands</h4>
        <p style="margin: 5px 0;">You can use these commands in your email subject line:</p>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li><code>time:30</code> - Add 30 minutes to the ticket</li>
            <li><code>priority:high</code> - Set ticket priority</li>
            <li><code>status:resolved</code> - Update ticket status</li>
            <li><code>assign:agent@company.com</code> - Assign to specific agent</li>
            <li><code>due:2025-12-31</code> - Set due date</li>
            <li><code>category:support</code> - Set ticket category</li>
        </ul>
    </div>
    
    <p>Best regards,<br><strong>{{account_name}} Support Team</strong></p>
    
    <hr style="margin: 30px 0; border: none; border-top: 1px solid #ddd;">
    <p style="font-size: 12px; color: #666;">This is an automated message from {{system_name}}.</p>
</div>',
                'is_default' => true,
                'priority' => 100,
                'description' => 'Default template for successful email command execution',
                'variables' => [
                    'command', 'result', 'ticket_number', 'account_name', 'system_name'
                ],
            ],
            [
                'key' => 'command_error_default',
                'name' => 'Email Command Error',
                'template_type' => 'command_error',
                'subject' => 'Command execution failed',
                'body_text' => "Hello,\n\nThere was an error processing your email command.\n\nCommand: {{command}}\nError: {{result}}\n\nPlease check the command syntax and try again, or contact support for assistance.\n\nValid command formats:\n- time:30 (add 30 minutes)\n- priority:high (set priority)\n- status:resolved (update status)\n- assign:agent@company.com (assign to agent)\n- due:2025-12-31 (set due date)\n- category:support (set category)\n\nBest regards,\n{{account_name}} Support Team",
                'body_html' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
    <h2 style="color: #333; border-bottom: 2px solid #dc3545; padding-bottom: 10px;">⚠ Command Execution Failed</h2>
    
    <p>Hello,</p>
    
    <p>There was an error processing your email command.</p>
    
    <div style="background-color: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #dc3545;">
        <h3 style="margin-top: 0; color: #721c24;">Error Details</h3>
        <p style="margin: 5px 0;"><strong>Command:</strong> <code style="background-color: #f5f5f5; padding: 2px 4px; border-radius: 3px;">{{command}}</code></p>
        <p style="margin: 5px 0;"><strong>Error:</strong> {{result}}</p>
    </div>
    
    <div style="background-color: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ffc107;">
        <h4 style="margin-top: 0; color: #856404;">Valid Command Formats</h4>
        <ul style="margin: 10px 0; padding-left: 20px; color: #856404;">
            <li><code>time:30</code> - Add 30 minutes to the ticket</li>
            <li><code>priority:high</code> - Set priority (low, normal, medium, high, urgent)</li>
            <li><code>status:resolved</code> - Update status (open, in_progress, resolved, etc.)</li>
            <li><code>assign:agent@company.com</code> - Assign to specific agent</li>
            <li><code>due:2025-12-31</code> - Set due date (YYYY-MM-DD format)</li>
            <li><code>category:support</code> - Set category (support, maintenance, development, etc.)</li>
        </ul>
    </div>
    
    <p>Please check the command syntax and try again, or contact support for assistance.</p>
    
    <p>Best regards,<br><strong>{{account_name}} Support Team</strong></p>
    
    <hr style="margin: 30px 0; border: none; border-top: 1px solid #ddd;">
    <p style="font-size: 12px; color: #666;">This is an automated message from {{system_name}}.</p>
</div>',
                'is_default' => true,
                'priority' => 100,
                'description' => 'Default template for failed email command execution',
                'variables' => [
                    'command', 'result', 'account_name', 'system_name'
                ],
            ],
            [
                'key' => 'ticket_resolved_default',
                'name' => 'Ticket Resolved',
                'template_type' => 'ticket_resolved',
                'subject' => 'Ticket {{ticket_number}} has been resolved',
                'body_text' => "Hello {{customer_name}},\n\nGreat news! Your ticket {{ticket_number}} - \"{{ticket_title}}\" has been resolved.\n\nResolution Summary:\nTicket was resolved by {{agent_name}} on {{ticket_resolved_at:M j, Y g:i A}}.\n\nIf you are satisfied with the resolution, no further action is needed. The ticket will be automatically closed in 24 hours.\n\nIf you need additional assistance or are not satisfied with the resolution, please reply to this email and we will reopen the ticket.\n\nThank you for using our support services!\n\nBest regards,\n{{account_name}} Support Team",
                'body_html' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
    <h2 style="color: #333; border-bottom: 2px solid #28a745; padding-bottom: 10px;">🎉 Ticket Resolved</h2>
    
    <p>Hello <strong>{{customer_name}}</strong>,</p>
    
    <p>Great news! Your ticket <strong>{{ticket_number}}</strong> - "{{ticket_title}}" has been resolved.</p>
    
    <div style="background-color: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #28a745;">
        <h3 style="margin-top: 0; color: #155724;">Resolution Summary</h3>
        <p style="margin: 5px 0;">Ticket was resolved by <strong>{{agent_name}}</strong> on {{ticket_resolved_at:M j, Y g:i A}}.</p>
    </div>
    
    <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <h4 style="margin-top: 0; color: #333;">What happens next?</h4>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>If you are satisfied with the resolution, no further action is needed</li>
            <li>The ticket will be automatically closed in 24 hours</li>
            <li>If you need additional assistance, simply reply to this email</li>
        </ul>
    </div>
    
    <p>Thank you for using our support services!</p>
    
    <p>Best regards,<br><strong>{{account_name}} Support Team</strong></p>
    
    <hr style="margin: 30px 0; border: none; border-top: 1px solid #ddd;">
    <p style="font-size: 12px; color: #666;">This is an automated message from {{system_name}}.</p>
</div>',
                'is_default' => true,
                'priority' => 100,
                'description' => 'Default template for ticket resolution notifications',
                'variables' => [
                    'customer_name', 'ticket_number', 'ticket_title', 'agent_name', 
                    'ticket_resolved_at', 'account_name', 'system_name'
                ],
            ],
            [
                'key' => 'auto_response_default',
                'name' => 'Auto Response',
                'template_type' => 'auto_response',
                'subject' => 'We received your email - {{ticket_number}}',
                'body_text' => "Hello,\n\nThank you for contacting us! We have received your email and {{#if ticket_number}}created ticket {{ticket_number}} for your request{{else}}are processing your message{{/if}}.\n\nOur support team will review your request and respond as soon as possible. Typical response times:\n\n- High Priority: Within 2 hours\n- Normal Priority: Within 8 hours\n- Low Priority: Within 24 hours\n\nYou can speed up the process by using email commands in your subject line:\n- time:30 (if you spent 30 minutes on this issue)\n- priority:high (for urgent matters)\n- category:maintenance (to categorize your request)\n\nBest regards,\n{{account_name}} Support Team",
                'body_html' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
    <h2 style="color: #333; border-bottom: 2px solid #007cba; padding-bottom: 10px;">Thank You for Contacting Us</h2>
    
    <p>Hello,</p>
    
    <p>Thank you for contacting us! We have received your email and {{#if ticket_number}}created ticket <strong>{{ticket_number}}</strong> for your request{{else}}are processing your message{{/if}}.</p>
    
    <div style="background-color: #d1ecf1; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #17a2b8;">
        <h3 style="margin-top: 0; color: #0c5460;">Response Times</h3>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li><strong>High Priority:</strong> Within 2 hours</li>
            <li><strong>Normal Priority:</strong> Within 8 hours</li>
            <li><strong>Low Priority:</strong> Within 24 hours</li>
        </ul>
    </div>
    
    <div style="background-color: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ffc107;">
        <h4 style="margin-top: 0; color: #856404;">💡 Speed Up Support with Email Commands</h4>
        <p style="margin: 5px 0; color: #856404;">Include these commands in your email subject line:</p>
        <ul style="margin: 10px 0; padding-left: 20px; color: #856404;">
            <li><code>time:30</code> - If you spent 30 minutes on this issue</li>
            <li><code>priority:high</code> - For urgent matters</li>
            <li><code>category:maintenance</code> - To categorize your request</li>
        </ul>
    </div>
    
    <p>Our support team will review your request and respond as soon as possible.</p>
    
    <p>Best regards,<br><strong>{{account_name}} Support Team</strong></p>
    
    <hr style="margin: 30px 0; border: none; border-top: 1px solid #ddd;">
    <p style="font-size: 12px; color: #666;">This is an automated message from {{system_name}}.</p>
</div>',
                'is_default' => true,
                'priority' => 100,
                'description' => 'Default auto-response template for incoming emails',
                'variables' => [
                    'ticket_number', 'account_name', 'system_name'
                ],
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::updateOrCreate(
                ['key' => $template['key']],
                $template
            );
        }

        $this->command->info('Created ' . count($templates) . ' default email templates');
    }
}
